<?php
/**
 * License Manager - Admin API Handler
 */
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB error']);
    exit;
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'change_status':
        $licenseId = (int)($_POST['license_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        if (!in_array($status, ['active', 'suspended', 'revoked'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid status']);
            exit;
        }
        $pdo->prepare("UPDATE vcs_licenses SET status = ?, updated_at = NOW() WHERE id = ?")->execute([$status, $licenseId]);
        $pdo->prepare("INSERT INTO vcs_license_logs (license_id, action, domain, ip_address, details, created_at) VALUES (?, ?, (SELECT domain FROM vcs_licenses WHERE id = ?), ?, ?, NOW())")
            ->execute([$licenseId, $status === 'active' ? 'activate' : $status, $licenseId, $_SERVER['REMOTE_ADDR'] ?? '', json_encode(['by' => 'admin', 'admin_id' => $_SESSION['admin_id']])]);
        echo json_encode(['success' => true]);
        break;

    case 'change_plan':
        $licenseId = (int)($_POST['license_id'] ?? 0);
        $plan = $_POST['plan'] ?? '';
        if (!in_array($plan, ['free', 'standard', 'professional', 'enterprise'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid plan']);
            exit;
        }
        $pdo->prepare("UPDATE vcs_licenses SET plan = ?, updated_at = NOW() WHERE id = ?")->execute([$plan, $licenseId]);
        $pdo->prepare("INSERT INTO vcs_license_logs (license_id, action, domain, ip_address, details, created_at) VALUES (?, 'plan_change', (SELECT domain FROM vcs_licenses WHERE id = ?), ?, ?, NOW())")
            ->execute([$licenseId, $licenseId, $_SERVER['REMOTE_ADDR'] ?? '', json_encode(['plan' => $plan, 'by' => 'admin'])]);
        echo json_encode(['success' => true]);
        break;

    case 'change_domain':
        $licenseId = (int)($_POST['license_id'] ?? 0);
        $newDomain = strtolower(trim($_POST['domain'] ?? ''));
        $newDomain = preg_replace('#^https?://#', '', $newDomain);
        $newDomain = preg_replace('#^www\.#', '', $newDomain);
        $newDomain = rtrim($newDomain, '/');

        if (!$newDomain) {
            echo json_encode(['success' => false, 'message' => 'Domain required']);
            exit;
        }
        // 중복 확인
        $chk = $pdo->prepare("SELECT id FROM vcs_licenses WHERE domain = ? AND id != ?");
        $chk->execute([$newDomain, $licenseId]);
        if ($chk->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Domain already registered']);
            exit;
        }

        $oldDomain = $pdo->prepare("SELECT domain FROM vcs_licenses WHERE id = ?");
        $oldDomain->execute([$licenseId]);
        $old = $oldDomain->fetchColumn();

        $hashSecret = 'VosCMS_2026_LicenseServer_!@#SecretKey';
        $newHash = hash('sha256', $newDomain . $hashSecret);

        $pdo->prepare("UPDATE vcs_licenses SET domain = ?, domain_hash = ?, updated_at = NOW() WHERE id = ?")->execute([$newDomain, $newHash, $licenseId]);
        $pdo->prepare("INSERT INTO vcs_license_logs (license_id, action, domain, ip_address, details, created_at) VALUES (?, 'domain_change', ?, ?, ?, NOW())")
            ->execute([$licenseId, $newDomain, $_SERVER['REMOTE_ADDR'] ?? '', json_encode(['old_domain' => $old, 'new_domain' => $newDomain, 'by' => 'admin'])]);
        echo json_encode(['success' => true]);
        break;

    case 'add_plugin':
        $licenseId = (int)($_POST['license_id'] ?? 0);
        $pluginId = trim($_POST['plugin_id'] ?? '');
        if (!$pluginId) { echo json_encode(['success' => false, 'message' => 'Plugin ID required']); exit; }

        $pdo->prepare("INSERT INTO vcs_license_plugins (license_id, plugin_id, status, purchased_at) VALUES (?, ?, 'active', NOW()) ON DUPLICATE KEY UPDATE status = 'active', purchased_at = NOW()")
            ->execute([$licenseId, $pluginId]);
        $pdo->prepare("INSERT INTO vcs_license_logs (license_id, action, domain, details, created_at) VALUES (?, 'plugin_add', (SELECT domain FROM vcs_licenses WHERE id = ?), ?, NOW())")
            ->execute([$licenseId, $licenseId, json_encode(['plugin_id' => $pluginId, 'by' => 'admin'])]);
        echo json_encode(['success' => true]);
        break;

    case 'revoke_plugin':
        $plId = (int)($_POST['plugin_license_id'] ?? 0);
        $pdo->prepare("UPDATE vcs_license_plugins SET status = 'revoked' WHERE id = ?")->execute([$plId]);
        echo json_encode(['success' => true]);
        break;

    case 'dev_status':
        $devId = (int)($_POST['developer_id'] ?? 0);
        $devAction = $_POST['status'] ?? '';
        $statusMap = ['activate' => 'active', 'suspend' => 'suspended', 'ban' => 'banned'];
        $newStatus = $statusMap[$devAction] ?? '';
        if (!$newStatus) { echo json_encode(['success' => false, 'message' => 'Invalid action']); exit; }
        $pdo->prepare("UPDATE vcs_developers SET status = ?, updated_at = NOW() WHERE id = ?")->execute([$newStatus, $devId]);
        echo json_encode(['success' => true]);
        break;

    case 'process_payout':
        $devId = (int)($_POST['developer_id'] ?? 0);
        $method = trim($_POST['method'] ?? 'bank');
        $reference = trim($_POST['reference'] ?? '');

        $dev = $pdo->prepare("SELECT pending_balance FROM vcs_developers WHERE id = ?");
        $dev->execute([$devId]);
        $balance = (float)$dev->fetchColumn();

        if ($balance <= 0) { echo json_encode(['success' => false, 'message' => 'No pending balance']); exit; }

        // 지급 기록
        $pdo->prepare(
            "INSERT INTO vcs_developer_payouts (developer_id, amount, currency, method, reference, status, period_start, period_end, processed_at, created_at)
             VALUES (?, ?, 'USD', ?, ?, 'completed', DATE_SUB(CURDATE(), INTERVAL 1 MONTH), CURDATE(), NOW(), NOW())"
        )->execute([$devId, $balance, $method, $reference ?: null]);

        // 잔액 업데이트
        $pdo->prepare("UPDATE vcs_developers SET total_paid = total_paid + ?, pending_balance = 0, updated_at = NOW() WHERE id = ?")
            ->execute([$balance, $devId]);

        // 매출 상태 업데이트
        $pdo->prepare("UPDATE vcs_developer_earnings SET status = 'paid', paid_at = NOW() WHERE developer_id = ? AND status = 'pending'")->execute([$devId]);

        echo json_encode(['success' => true, 'amount' => $balance]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
