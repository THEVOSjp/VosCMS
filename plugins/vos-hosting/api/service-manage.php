<?php
/**
 * 서비스 관리 API
 *
 * POST /api/service-manage.php
 * Body (JSON): { action, subscription_id, ... }
 */
if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');

if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__, 3));

$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') !== false) {
            [$k, $v] = explode('=', $line, 2);
            $_ENV[trim($k)] = trim($v, " \t\n\r\0\x0B\"'");
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST only']);
    exit;
}

// 세션 — 코어와 동일한 save_path 사용 (BASE_PATH/storage/sessions)
if (session_status() === PHP_SESSION_NONE) {
    $_sessionDir = BASE_PATH . '/storage/sessions';
    if (is_dir($_sessionDir)) ini_set('session.save_path', $_sessionDir);
    session_start();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'message' => '요청 데이터가 없습니다.']);
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
    echo json_encode(['success' => false, 'message' => 'DB 연결 실패']);
    exit;
}

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit;
}

$action = $input['action'] ?? '';
$subId = (int)($input['subscription_id'] ?? 0);

// 구독 소유자 확인 헬퍼
function getOwnedSubscription($pdo, $prefix, $subId, $userId) {
    $stmt = $pdo->prepare("SELECT * FROM {$prefix}subscriptions WHERE id = ? AND user_id = ?");
    $stmt->execute([$subId, $userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

switch ($action) {

    // ===== 자동연장 토글 (recurring만) =====
    case 'toggle_auto_renew':
        $sub = getOwnedSubscription($pdo, $prefix, $subId, $userId);
        if (!$sub) { echo json_encode(['success' => false, 'message' => '구독을 찾을 수 없습니다.']); exit; }

        $sc = $sub['service_class'] ?? 'recurring';
        if ($sc !== 'recurring') {
            echo json_encode(['success' => false, 'message' => '유료 정기 서비스만 자동연장 설정이 가능합니다.']);
            exit;
        }

        $autoRenew = !empty($input['auto_renew']) ? 1 : 0;
        $pdo->prepare("UPDATE {$prefix}subscriptions SET auto_renew = ? WHERE id = ? AND user_id = ?")
            ->execute([$autoRenew, $subId, $userId]);

        echo json_encode(['success' => true, 'auto_renew' => $autoRenew]);
        break;

    // ===== 무료 서비스 연장 신청 =====
    case 'request_renewal':
        $sub = getOwnedSubscription($pdo, $prefix, $subId, $userId);
        if (!$sub) { echo json_encode(['success' => false, 'message' => '구독을 찾을 수 없습니다.']); exit; }

        $sc = $sub['service_class'] ?? 'recurring';
        if ($sc !== 'free') {
            echo json_encode(['success' => false, 'message' => '무료 서비스만 연장 신청이 가능합니다.']);
            exit;
        }

        // order_logs에 연장 신청 기록
        $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'renewal_request', ?, 'user', ?)")
            ->execute([
                $sub['order_id'],
                json_encode(['subscription_id' => $subId, 'label' => $sub['label'], 'type' => $sub['type']]),
                $userId
            ]);

        echo json_encode(['success' => true, 'message' => '연장 신청이 접수되었습니다. 관리자 확인 후 처리됩니다.']);
        break;

    // ===== 메인 도메인 설정 =====
    case 'set_primary_domain':
        $domain = $input['domain'] ?? '';
        if (!$domain) { echo json_encode(['success' => false, 'message' => '도메인을 지정해주세요.']); exit; }

        $sub = getOwnedSubscription($pdo, $prefix, $subId, $userId);
        if (!$sub || $sub['type'] !== 'domain') {
            echo json_encode(['success' => false, 'message' => '도메인 구독을 찾을 수 없습니다.']);
            exit;
        }

        $meta = json_decode($sub['metadata'] ?? '{}', true) ?: [];
        if (!in_array($domain, $meta['domains'] ?? [])) {
            echo json_encode(['success' => false, 'message' => '해당 도메인이 구독에 포함되어 있지 않습니다.']);
            exit;
        }

        // 같은 주문의 모든 도메인 구독에서 primary_domain 초기화
        $resetStmt = $pdo->prepare("SELECT id, metadata FROM {$prefix}subscriptions WHERE order_id = ? AND type = 'domain'");
        $resetStmt->execute([$sub['order_id']]);
        while ($row = $resetStmt->fetch(PDO::FETCH_ASSOC)) {
            $rm = json_decode($row['metadata'] ?? '{}', true) ?: [];
            unset($rm['primary_domain']);
            $pdo->prepare("UPDATE {$prefix}subscriptions SET metadata = ? WHERE id = ?")
                ->execute([json_encode($rm, JSON_UNESCAPED_UNICODE), $row['id']]);
        }

        // 해당 구독에 primary_domain 설정
        $meta['primary_domain'] = $domain;
        $pdo->prepare("UPDATE {$prefix}subscriptions SET metadata = ? WHERE id = ?")
            ->execute([json_encode($meta, JSON_UNESCAPED_UNICODE), $subId]);

        // 주문의 domain 필드도 업데이트
        $pdo->prepare("UPDATE {$prefix}orders SET domain = ? WHERE id = ?")
            ->execute([$domain, $sub['order_id']]);

        echo json_encode(['success' => true, 'message' => '메인 도메인이 설정되었습니다.']);
        break;

    // ===== 1회성 서비스 완료 처리 (관리자용) =====
    case 'mark_complete':
        // 관리자 권한 체크
        $userStmt = $pdo->prepare("SELECT role FROM {$prefix}users WHERE id = ?");
        $userStmt->execute([$userId]);
        $userRole = $userStmt->fetchColumn();
        if ($userRole !== 'admin') {
            echo json_encode(['success' => false, 'message' => '관리자만 완료 처리할 수 있습니다.']);
            exit;
        }

        $targetSubId = (int)($input['subscription_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM {$prefix}subscriptions WHERE id = ?");
        $stmt->execute([$targetSubId]);
        $sub = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$sub || ($sub['service_class'] ?? '') !== 'one_time') {
            echo json_encode(['success' => false, 'message' => '1회성 서비스를 찾을 수 없습니다.']);
            exit;
        }

        $pdo->prepare("UPDATE {$prefix}subscriptions SET completed_at = NOW(), status = 'active' WHERE id = ?")
            ->execute([$targetSubId]);

        $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'service_completed', ?, 'admin', ?)")
            ->execute([$sub['order_id'], json_encode(['subscription_id' => $targetSubId, 'label' => $sub['label']]), $userId]);

        echo json_encode(['success' => true, 'message' => '서비스가 완료 처리되었습니다.']);
        break;

    // ===== 메일 비밀번호 변경 =====
    case 'change_mail_password':
        $address = $input['address'] ?? '';
        $newPassword = $input['password'] ?? '';
        if (!$address || strlen($newPassword) < 8) {
            echo json_encode(['success' => false, 'message' => '메일 주소와 8자 이상 비밀번호를 입력해주세요.']);
            exit;
        }

        $sub = getOwnedSubscription($pdo, $prefix, $subId, $userId);
        if (!$sub) { echo json_encode(['success' => false, 'message' => '구독을 찾을 수 없습니다.']); exit; }

        require_once BASE_PATH . '/rzxlib/Core/Helpers/Encryption.php';
        require_once BASE_PATH . '/rzxlib/Core/Helpers/functions.php';

        $meta = json_decode($sub['metadata'] ?? '{}', true) ?: [];
        $found = false;
        foreach ($meta['mail_accounts'] ?? [] as &$ma) {
            if (($ma['address'] ?? '') === $address) {
                $ma['password'] = encrypt($newPassword);
                $found = true;
                break;
            }
        }
        unset($ma);

        if (!$found) {
            echo json_encode(['success' => false, 'message' => '해당 메일 계정을 찾을 수 없습니다.']);
            exit;
        }

        $pdo->prepare("UPDATE {$prefix}subscriptions SET metadata = ? WHERE id = ?")
            ->execute([json_encode($meta, JSON_UNESCAPED_UNICODE), $subId]);

        // 호스팅 구독 metadata에도 동일 주소가 있으면 동기화
        $hostingSubs = $pdo->prepare("SELECT id, metadata FROM {$prefix}subscriptions WHERE order_id = ? AND type = 'hosting'");
        $hostingSubs->execute([$sub['order_id']]);
        while ($hs = $hostingSubs->fetch(PDO::FETCH_ASSOC)) {
            $hm = json_decode($hs['metadata'] ?? '{}', true) ?: [];
            $updated = false;
            foreach ($hm['mail_accounts'] ?? [] as &$hma) {
                if (($hma['address'] ?? '') === $address) {
                    $hma['password'] = encrypt($newPassword);
                    $updated = true;
                    break;
                }
            }
            unset($hma);
            if ($updated) {
                $pdo->prepare("UPDATE {$prefix}subscriptions SET metadata = ? WHERE id = ?")
                    ->execute([json_encode($hm, JSON_UNESCAPED_UNICODE), $hs['id']]);
            }
        }

        $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'mail_password_changed', ?, 'user', ?)")
            ->execute([$sub['order_id'], json_encode(['address' => $address]), $userId]);

        echo json_encode(['success' => true, 'message' => '비밀번호가 변경되었습니다.']);
        break;

    // ===== 미구현 스텁 =====
    case 'add_domain':
    case 'add_mail_account':
    case 'upgrade_plan':
    case 'add_service':
        echo json_encode(['success' => false, 'message' => '준비 중인 기능입니다.']);
        break;

    default:
        echo json_encode(['success' => false, 'message' => '알 수 없는 액션입니다.']);
        break;
}
