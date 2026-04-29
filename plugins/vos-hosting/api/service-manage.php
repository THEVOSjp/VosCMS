<?php
/**
 * 서비스 관리 API
 *
 * POST /api/service-manage.php
 * Body (JSON): { action, subscription_id, ... }
 */
if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');

if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__, 3));

if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require_once BASE_PATH . '/vendor/autoload.php';
}

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

// mx1 메일 동기화 즉시 트리거 (백그라운드 — fire & forget)
function triggerMailSyncToMx1() {
    $script = BASE_PATH . '/scripts/mail-sync-to-mx1.php';
    if (!file_exists($script)) return;
    $dbName = $_ENV['DB_DATABASE'] ?? 'voscms_prod';
    // 백그라운드 실행 (응답 지연 방지)
    $cmd = sprintf('/usr/bin/php8.3 %s %s > /dev/null 2>&1 &', escapeshellarg($script), escapeshellarg($dbName));
    @exec($cmd);
}

// 구독 소유자 확인 헬퍼
function getOwnedSubscription($pdo, $prefix, $subId, $userId) {
    $stmt = $pdo->prepare("SELECT * FROM {$prefix}subscriptions WHERE id = ? AND user_id = ?");
    $stmt->execute([$subId, $userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

switch ($action) {

    // ===== 사이트 백업 (web 파일 + .env + DB 덤프 zip) =====
    case 'request_backup':
        $sub = getOwnedSubscription($pdo, $prefix, $subId, $userId);
        if (!$sub || $sub['type'] !== 'hosting') {
            echo json_encode(['success' => false, 'message' => '호스팅 구독을 찾을 수 없습니다.']);
            exit;
        }
        $oSt = $pdo->prepare("SELECT order_number FROM {$prefix}orders WHERE id = ?");
        $oSt->execute([$sub['order_id']]);
        $orderNumber = $oSt->fetchColumn();
        if (!$orderNumber) {
            echo json_encode(['success' => false, 'message' => '주문을 찾을 수 없습니다.']);
            exit;
        }
        $docroot = '/var/www/customers/' . $orderNumber . '/public_html';
        if (!is_dir($docroot)) {
            echo json_encode(['success' => false, 'message' => '호스팅 디렉토리가 없습니다.']);
            exit;
        }

        // metadata.server.db 에서 DB 정보 (또는 docroot 의 .env)
        $hMeta = json_decode($sub['metadata'] ?? '{}', true) ?: [];
        $dbInfo = $hMeta['server']['db'] ?? [];
        if (empty($dbInfo['db_pass']) && file_exists($docroot . '/.env')) {
            $envVars = [];
            foreach (file($docroot . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) continue;
                [$k, $v] = explode('=', $line, 2);
                $envVars[trim($k)] = trim($v, " \t\n\r\0\x0B\"'");
            }
            $dbInfo = [
                'db_host' => $envVars['DB_HOST'] ?? '127.0.0.1',
                'db_name' => $envVars['DB_DATABASE'] ?? '',
                'db_user' => $envVars['DB_USERNAME'] ?? '',
                'db_pass' => $envVars['DB_PASSWORD'] ?? '',
            ];
        }

        // 백업 디렉토리 (호스팅 디렉토리 외부 — root:www-data 만 접근 가능)
        $backupDir = '/var/www/customers/' . $orderNumber . '/backups';
        if (!is_dir($backupDir)) {
            @mkdir($backupDir, 0750, true);
        }
        // 7일 넘은 기존 백업 정리
        foreach (glob($backupDir . '/backup-*.zip') ?: [] as $oldFile) {
            if (filemtime($oldFile) < time() - 7 * 86400) {
                @unlink($oldFile);
            }
        }

        // DB 덤프
        $ts = date('Ymd-His');
        $sqlFile = $backupDir . "/db-{$ts}.sql";
        if (!empty($dbInfo['db_pass'])) {
            $cmd = sprintf(
                '/usr/bin/mysqldump --single-transaction --quick --no-tablespaces --routines --triggers -h%s -u%s -p%s %s > %s 2>&1',
                escapeshellarg($dbInfo['db_host'] ?? 'localhost'),
                escapeshellarg($dbInfo['db_user']),
                escapeshellarg($dbInfo['db_pass']),
                escapeshellarg($dbInfo['db_name']),
                escapeshellarg($sqlFile)
            );
            exec($cmd, $output, $exit);
            if ($exit !== 0) {
                echo json_encode(['success' => false, 'message' => 'DB 덤프 실패: ' . implode("\n", $output)]);
                exit;
            }
        }

        // zip 패키징 (public_html 전체 + sql)
        $zipFile = $backupDir . "/backup-{$orderNumber}-{$ts}.zip";
        $cmd = sprintf(
            'cd %s && /usr/bin/zip -rq %s public_html backups/db-%s.sql 2>&1',
            escapeshellarg('/var/www/customers/' . $orderNumber),
            escapeshellarg($zipFile),
            escapeshellarg($ts)
        );
        exec($cmd, $zout, $zexit);
        @unlink($sqlFile);

        if (!file_exists($zipFile) || filesize($zipFile) === 0) {
            echo json_encode(['success' => false, 'message' => '백업 파일 생성 실패: ' . implode("\n", $zout)]);
            exit;
        }

        // 서명된 다운로드 URL (HMAC, 10분 만료)
        $secret = $_ENV['APP_KEY'] ?? 'voscms-default-secret';
        $expires = time() + 600;
        $filename = basename($zipFile);
        $sig = hash_hmac('sha256', "{$orderNumber}|{$filename}|{$expires}|{$userId}", $secret);
        $downloadUrl = sprintf(
            '/plugins/vos-hosting/api/backup-download.php?o=%s&f=%s&e=%d&u=%d&s=%s',
            urlencode($orderNumber), urlencode($filename), $expires, (int)$userId, $sig
        );

        $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'site_backup_created', ?, 'customer', ?)")
            ->execute([$sub['order_id'], json_encode(['filename' => $filename, 'size_bytes' => filesize($zipFile)], JSON_UNESCAPED_UNICODE), (string)$userId]);

        echo json_encode([
            'success' => true,
            'download_url' => $downloadUrl,
            'filename' => $filename,
            'size_bytes' => filesize($zipFile),
        ]);
        exit;

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
        // ??  연산자 + foreach reference 는 원본 array 에 안 먹음 — 직접 인덱스 갱신
        if (!empty($meta['mail_accounts']) && is_array($meta['mail_accounts'])) {
            foreach ($meta['mail_accounts'] as $_i => $ma) {
                if (($ma['address'] ?? '') === $address) {
                    $meta['mail_accounts'][$_i]['password'] = mail_password_hash($newPassword);
                    $found = true;
                    break;
                }
            }
        }

        if (!$found) {
            echo json_encode(['success' => false, 'message' => '해당 메일 계정을 찾을 수 없습니다.']);
            exit;
        }

        $pdo->prepare("UPDATE {$prefix}subscriptions SET metadata = ? WHERE id = ?")
            ->execute([json_encode($meta, JSON_UNESCAPED_UNICODE), $subId]);

        // 같은 주문의 mail + hosting 모든 metadata.mail_accounts 동기화
        $allSubs = $pdo->prepare("SELECT id, metadata FROM {$prefix}subscriptions WHERE order_id = ? AND id != ? AND (type = 'mail' OR type = 'hosting')");
        $allSubs->execute([$sub['order_id'], $subId]);
        while ($hs = $allSubs->fetch(PDO::FETCH_ASSOC)) {
            $hm = json_decode($hs['metadata'] ?? '{}', true) ?: [];
            $updated = false;
            if (!empty($hm['mail_accounts']) && is_array($hm['mail_accounts'])) {
                foreach ($hm['mail_accounts'] as $_i => $hma) {
                    if (($hma['address'] ?? '') === $address) {
                        $hm['mail_accounts'][$_i]['password'] = mail_password_hash($newPassword);
                        $updated = true;
                        break;
                    }
                }
            }
            if ($updated) {
                $pdo->prepare("UPDATE {$prefix}subscriptions SET metadata = ? WHERE id = ?")
                    ->execute([json_encode($hm, JSON_UNESCAPED_UNICODE), $hs['id']]);
            }
        }

        $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'mail_password_changed', ?, 'user', ?)")
            ->execute([$sub['order_id'], json_encode(['address' => $address]), $userId]);

        triggerMailSyncToMx1();
        echo json_encode(['success' => true, 'applied' => true, 'message' => '비밀번호가 변경되었습니다.']);
        break;

    // ===== 메일 계정 추가 (기본 메일 — 호스팅 무료 한도 내) =====
    case 'add_mail_account':
        $orderId = (int)($input['order_id'] ?? 0);
        $address = strtolower(trim($input['address'] ?? ''));
        $newPassword = $input['password'] ?? '';

        if (!$orderId || !$address || strlen($newPassword) < 8) {
            echo json_encode(['success' => false, 'message' => '주문, 메일주소, 8자 이상 비밀번호가 필요합니다.']);
            exit;
        }
        if (!filter_var($address, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => '메일 주소 형식이 올바르지 않습니다.']);
            exit;
        }

        // 주문 소유자 확인
        $orderStmt = $pdo->prepare("SELECT * FROM {$prefix}orders WHERE id = ? AND user_id = ?");
        $orderStmt->execute([$orderId, $userId]);
        $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
        if (!$order) { echo json_encode(['success' => false, 'message' => '주문을 찾을 수 없습니다.']); exit; }

        // 호스팅 구독 확인 (활성 상태여야 무료 메일 사용 가능)
        $hostStmt = $pdo->prepare("SELECT * FROM {$prefix}subscriptions WHERE order_id = ? AND type = 'hosting' AND status = 'active' LIMIT 1");
        $hostStmt->execute([$orderId]);
        $hostSub = $hostStmt->fetch(PDO::FETCH_ASSOC);
        if (!$hostSub) { echo json_encode(['success' => false, 'message' => '활성 호스팅 구독이 필요합니다.']); exit; }

        // 도메인 일치 확인
        $orderDomain = strtolower($order['domain'] ?? '');
        $addrDomain = substr(strrchr($address, '@'), 1);
        if (!$orderDomain || $addrDomain !== $orderDomain) {
            echo json_encode(['success' => false, 'message' => '주문 도메인과 메일 도메인이 일치하지 않습니다.']);
            exit;
        }

        // 무료 메일 한도 (호스팅 플랜 free_mail_count, 기본 5)
        $freeLimit = 5;
        $planStmt = $pdo->prepare("SELECT `value` FROM {$prefix}settings WHERE `key` = 'service_hosting_plans' LIMIT 1");
        $planStmt->execute();
        $plansJson = $planStmt->fetchColumn();
        $plans = json_decode($plansJson ?: '[]', true) ?: [];
        $hostCapacity = $order['hosting_capacity'] ?? '';
        foreach ($plans as $p) {
            if (($p['capacity'] ?? '') === $hostCapacity) {
                if (isset($p['free_mail_count']) && is_numeric($p['free_mail_count'])) {
                    $freeLimit = (int)$p['free_mail_count'];
                }
                break;
            }
        }

        // 기존 mail 구독 + 호스팅 구독의 mail_accounts 모두 집계 (중복 / 한도 체크)
        $allAddrs = [];
        $hostMeta = json_decode($hostSub['metadata'] ?? '{}', true) ?: [];
        foreach ($hostMeta['mail_accounts'] ?? [] as $ma) {
            if (!empty($ma['address'])) $allAddrs[] = strtolower($ma['address']);
        }
        $mailSubStmt = $pdo->prepare("SELECT id, label, metadata FROM {$prefix}subscriptions WHERE order_id = ? AND type = 'mail' ORDER BY id");
        $mailSubStmt->execute([$orderId]);
        $mailSubs = $mailSubStmt->fetchAll(PDO::FETCH_ASSOC);

        $basicMailSub = null;
        foreach ($mailSubs as $ms) {
            $isBiz = stripos($ms['label'], '비즈니스') !== false || stripos($ms['label'], 'ビジネス') !== false || stripos($ms['label'], 'Business') !== false;
            if (!$isBiz && $basicMailSub === null) $basicMailSub = $ms;
            $msMeta = json_decode($ms['metadata'] ?? '{}', true) ?: [];
            foreach ($msMeta['mail_accounts'] ?? [] as $ma) {
                if (!empty($ma['address'])) $allAddrs[] = strtolower($ma['address']);
            }
        }

        if (in_array($address, $allAddrs, true)) {
            echo json_encode(['success' => false, 'message' => '이미 사용 중인 메일 주소입니다.']);
            exit;
        }
        if (count($allAddrs) >= $freeLimit) {
            echo json_encode(['success' => false, 'message' => "무료 메일 한도({$freeLimit}개)에 도달했습니다."]);
            exit;
        }

        require_once BASE_PATH . '/rzxlib/Core/Helpers/Encryption.php';
        require_once BASE_PATH . '/rzxlib/Core/Helpers/functions.php';

        $newAccount = ['address' => $address, 'password' => mail_password_hash($newPassword)];

        if ($basicMailSub) {
            // 기존 기본 메일 구독에 추가
            $msMeta = json_decode($basicMailSub['metadata'] ?? '{}', true) ?: [];
            $msMeta['mail_accounts'] = $msMeta['mail_accounts'] ?? [];
            $msMeta['mail_accounts'][] = $newAccount;
            $pdo->prepare("UPDATE {$prefix}subscriptions SET metadata = ? WHERE id = ?")
                ->execute([json_encode($msMeta, JSON_UNESCAPED_UNICODE), $basicMailSub['id']]);
            $targetSubId = $basicMailSub['id'];
        } else {
            // 기본 메일 구독 자동 생성 (호스팅과 동일 만료/자동연장)
            $newMeta = ['mail_accounts' => [$newAccount]];
            $insertStmt = $pdo->prepare("INSERT INTO {$prefix}subscriptions
                (order_id, user_id, type, service_class, label, started_at, expires_at, auto_renew, status, metadata, currency, billing_amount, unit_price)
                VALUES (?, ?, 'mail', 'free', '기본 메일', ?, ?, ?, 'active', ?, ?, 0, 0)");
            $insertStmt->execute([
                $orderId, $userId,
                $hostSub['started_at'], $hostSub['expires_at'],
                (int)($hostSub['auto_renew'] ?? 1),
                json_encode($newMeta, JSON_UNESCAPED_UNICODE),
                $hostSub['currency'] ?? 'JPY'
            ]);
            $targetSubId = (int)$pdo->lastInsertId();
        }

        // 로그
        $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'mail_account_added', ?, 'user', ?)")
            ->execute([$orderId, json_encode(['address' => $address, 'subscription_id' => $targetSubId]), $userId]);

        triggerMailSyncToMx1();
        echo json_encode(['success' => true, 'message' => '메일 계정이 추가되었습니다.', 'subscription_id' => $targetSubId]);
        break;

    // ===== 메일 주소 변경 (오타 수정용) =====
    case 'change_mail_address':
        $oldAddress = strtolower(trim($input['old_address'] ?? ''));
        $newAddress = strtolower(trim($input['new_address'] ?? ''));
        if (!$subId || !$oldAddress || !$newAddress) {
            echo json_encode(['success' => false, 'message' => '구독 + 기존 주소 + 새 주소 필요']); exit;
        }
        if (!filter_var($newAddress, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => '올바른 메일 주소 형식이 아닙니다.']); exit;
        }
        if ($oldAddress === $newAddress) {
            echo json_encode(['success' => false, 'message' => '동일한 주소입니다.']); exit;
        }
        // 도메인 일치 확인
        $oldDomain = substr(strrchr($oldAddress, '@'), 1);
        $newDomain = substr(strrchr($newAddress, '@'), 1);
        if ($oldDomain !== $newDomain) {
            echo json_encode(['success' => false, 'message' => '도메인은 변경할 수 없습니다.']); exit;
        }

        $sub = getOwnedSubscription($pdo, $prefix, $subId, $userId);
        if (!$sub) { echo json_encode(['success' => false, 'message' => '구독을 찾을 수 없습니다.']); exit; }

        $meta = json_decode($sub['metadata'] ?? '{}', true) ?: [];
        $found = false;
        if (!empty($meta['mail_accounts']) && is_array($meta['mail_accounts'])) {
            foreach ($meta['mail_accounts'] as $_i => $ma) {
                if (strtolower($ma['address'] ?? '') === $oldAddress) {
                    $meta['mail_accounts'][$_i]['address'] = $newAddress;
                    $found = true;
                    break;
                }
            }
        }
        if (!$found) {
            echo json_encode(['success' => false, 'message' => '해당 메일 계정을 찾을 수 없습니다.']); exit;
        }

        // 같은 주문의 다른 메일/호스팅 구독 metadata 에 동일 주소 있는지 — 중복 검사
        $allCheck = $pdo->prepare("SELECT id, metadata FROM {$prefix}subscriptions WHERE order_id = ? AND (type = 'mail' OR type = 'hosting')");
        $allCheck->execute([$sub['order_id']]);
        while ($row = $allCheck->fetch(PDO::FETCH_ASSOC)) {
            if ((int)$row['id'] === $subId) continue;
            $rm = json_decode($row['metadata'] ?? '{}', true) ?: [];
            foreach ($rm['mail_accounts'] ?? [] as $rma) {
                if (strtolower($rma['address'] ?? '') === $newAddress) {
                    echo json_encode(['success' => false, 'message' => '이미 사용 중인 메일 주소입니다.']); exit;
                }
            }
        }

        $pdo->prepare("UPDATE {$prefix}subscriptions SET metadata = ? WHERE id = ?")
            ->execute([json_encode($meta, JSON_UNESCAPED_UNICODE), $subId]);

        // 같은 주문의 mail + hosting 모든 metadata 동기화
        $hSt = $pdo->prepare("SELECT id, metadata FROM {$prefix}subscriptions WHERE order_id = ? AND id != ? AND (type = 'mail' OR type = 'hosting')");
        $hSt->execute([$sub['order_id'], $subId]);
        while ($hs = $hSt->fetch(PDO::FETCH_ASSOC)) {
            $hm = json_decode($hs['metadata'] ?? '{}', true) ?: [];
            $changed = false;
            if (!empty($hm['mail_accounts']) && is_array($hm['mail_accounts'])) {
                foreach ($hm['mail_accounts'] as $_i => $hma) {
                    if (strtolower($hma['address'] ?? '') === $oldAddress) {
                        $hm['mail_accounts'][$_i]['address'] = $newAddress;
                        $changed = true;
                    }
                }
            }
            if ($changed) {
                $pdo->prepare("UPDATE {$prefix}subscriptions SET metadata = ? WHERE id = ?")
                    ->execute([json_encode($hm, JSON_UNESCAPED_UNICODE), $hs['id']]);
            }
        }

        $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'mail_address_changed', ?, 'user', ?)")
            ->execute([$sub['order_id'], json_encode(['old' => $oldAddress, 'new' => $newAddress], JSON_UNESCAPED_UNICODE), $userId]);

        triggerMailSyncToMx1();
        echo json_encode(['success' => true, 'message' => '메일 주소가 변경되었습니다.']);
        break;

    // ===== 메일 계정 삭제 =====
    case 'delete_mail_account':
        $address = strtolower(trim($input['address'] ?? ''));
        if (!$subId || !$address) {
            echo json_encode(['success' => false, 'message' => '구독과 메일주소가 필요합니다.']);
            exit;
        }

        $sub = getOwnedSubscription($pdo, $prefix, $subId, $userId);
        if (!$sub) { echo json_encode(['success' => false, 'message' => '구독을 찾을 수 없습니다.']); exit; }

        $meta = json_decode($sub['metadata'] ?? '{}', true) ?: [];
        $accounts = $meta['mail_accounts'] ?? [];
        $found = false;
        $remaining = [];
        foreach ($accounts as $ma) {
            if (strtolower($ma['address'] ?? '') === $address) { $found = true; continue; }
            $remaining[] = $ma;
        }
        if (!$found) { echo json_encode(['success' => false, 'message' => '해당 메일 계정을 찾을 수 없습니다.']); exit; }

        $meta['mail_accounts'] = $remaining;
        $pdo->prepare("UPDATE {$prefix}subscriptions SET metadata = ? WHERE id = ?")
            ->execute([json_encode($meta, JSON_UNESCAPED_UNICODE), $subId]);

        // 호스팅 구독 metadata에도 동일 주소가 있으면 동기화 제거
        $hostingSubs = $pdo->prepare("SELECT id, metadata FROM {$prefix}subscriptions WHERE order_id = ? AND type = 'hosting'");
        $hostingSubs->execute([$sub['order_id']]);
        while ($hs = $hostingSubs->fetch(PDO::FETCH_ASSOC)) {
            $hm = json_decode($hs['metadata'] ?? '{}', true) ?: [];
            $hAccounts = $hm['mail_accounts'] ?? [];
            $changed = false;
            $newH = [];
            foreach ($hAccounts as $hma) {
                if (strtolower($hma['address'] ?? '') === $address) { $changed = true; continue; }
                $newH[] = $hma;
            }
            if ($changed) {
                $hm['mail_accounts'] = $newH;
                $pdo->prepare("UPDATE {$prefix}subscriptions SET metadata = ? WHERE id = ?")
                    ->execute([json_encode($hm, JSON_UNESCAPED_UNICODE), $hs['id']]);
            }
        }

        $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'mail_account_deleted', ?, 'user', ?)")
            ->execute([$sub['order_id'], json_encode(['address' => $address, 'subscription_id' => $subId]), $userId]);

        triggerMailSyncToMx1();
        echo json_encode(['success' => true, 'message' => '메일 계정이 삭제되었습니다.']);
        break;

    // ===== 비즈니스 메일 업그레이드 신청 =====
    case 'request_bizmail_upgrade':
        $orderId = (int)($input['order_id'] ?? 0);
        $reqAddress = trim($input['address'] ?? '');   // 선택: 특정 주소 업그레이드
        if (!$orderId) { echo json_encode(['success' => false, 'message' => '주문이 필요합니다.']); exit; }

        $orderStmt = $pdo->prepare("SELECT id FROM {$prefix}orders WHERE id = ? AND user_id = ?");
        $orderStmt->execute([$orderId, $userId]);
        if (!$orderStmt->fetchColumn()) {
            echo json_encode(['success' => false, 'message' => '주문을 찾을 수 없습니다.']);
            exit;
        }

        $logDetail = ['requested_at' => date('c')];
        if ($reqAddress) $logDetail['address'] = $reqAddress;

        $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'bizmail_upgrade_request', ?, 'user', ?)")
            ->execute([$orderId, json_encode($logDetail, JSON_UNESCAPED_UNICODE), $userId]);

        echo json_encode(['success' => true, 'message' => '비즈니스 메일 업그레이드 신청이 접수되었습니다.']);
        break;

    // ===== admin: 신규 도메인 마이그레이션 (임시 → 정식) =====
    case 'admin_migrate_new_domain':
        // 관리자 권한 체크
        $userStmt = $pdo->prepare("SELECT role FROM {$prefix}users WHERE id = ?");
        $userStmt->execute([$userId]);
        if ($userStmt->fetchColumn() !== 'admin') {
            echo json_encode(['success' => false, 'message' => '관리자 권한 필요']);
            exit;
        }

        $orderId = (int)($input['order_id'] ?? 0);
        if (!$orderId) { echo json_encode(['success' => false, 'message' => 'order_id 필요']); exit; }

        try {
            $provisioner = new \RzxLib\Core\Mail\MailDomainProvisioner($pdo);
            $result = $provisioner->completeNewDomainAcquisition($orderId);
            $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'mail_domain_migrated', ?, 'admin', ?)")
                ->execute([$orderId, json_encode($result, JSON_UNESCAPED_UNICODE), $userId]);

            // 고객에게 「메일 사용 가능」 알림
            try {
                (new \RzxLib\Core\Mail\MailNotifier($pdo))->notifyCustomerMailReady($orderId);
            } catch (\Throwable $ne) { error_log("[notifier] order $orderId: " . $ne->getMessage()); }

            echo json_encode(['success' => true, 'result' => $result]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // ===== admin: 보유 도메인 활성화 (NS 변경 후 자동 셋업) =====
    case 'admin_activate_existing_domain':
        $userStmt = $pdo->prepare("SELECT role FROM {$prefix}users WHERE id = ?");
        $userStmt->execute([$userId]);
        if ($userStmt->fetchColumn() !== 'admin') {
            echo json_encode(['success' => false, 'message' => '관리자 권한 필요']);
            exit;
        }

        $orderId = (int)($input['order_id'] ?? 0);
        if (!$orderId) { echo json_encode(['success' => false, 'message' => 'order_id 필요']); exit; }

        try {
            $provisioner = new \RzxLib\Core\Mail\MailDomainProvisioner($pdo);
            $result = $provisioner->activateExistingDomain($orderId);
            $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'mail_domain_activated', ?, 'admin', ?)")
                ->execute([$orderId, json_encode($result, JSON_UNESCAPED_UNICODE), $userId]);

            // 고객에게 「메일 사용 가능」 알림
            try {
                (new \RzxLib\Core\Mail\MailNotifier($pdo))->notifyCustomerMailReady($orderId);
            } catch (\Throwable $ne) { error_log("[notifier] order $orderId: " . $ne->getMessage()); }

            echo json_encode(['success' => true, 'result' => $result]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // ===== 부가서비스 — 웹 용량 추가 (즉시 결제 + 활성화) =====
    case 'pay_storage_addon':
        $hostSubId = (int)($input['subscription_id'] ?? 0);
        $capacity = trim($input['capacity'] ?? '');
        $unitPrice = (int)($input['unit_price'] ?? 0);
        if (!$hostSubId || $capacity === '' || $unitPrice <= 0) {
            echo json_encode(['success' => false, 'message' => '구독 + 용량 + 단가 필요']);
            exit;
        }

        $sub = getOwnedSubscription($pdo, $prefix, $hostSubId, $userId);
        if (!$sub || $sub['type'] !== 'hosting' || $sub['status'] !== 'active') {
            echo json_encode(['success' => false, 'message' => '활성 호스팅 구독을 찾을 수 없습니다.']);
            exit;
        }

        // 단가 검증 (rzx_settings 의 service_hosting_storage)
        $stOptStmt = $pdo->prepare("SELECT `value` FROM {$prefix}settings WHERE `key` = 'service_hosting_storage' LIMIT 1");
        $stOptStmt->execute();
        $stOpts = json_decode($stOptStmt->fetchColumn() ?: '[]', true) ?: [];
        $validUnit = 0;
        foreach ($stOpts as $opt) {
            if (($opt['capacity'] ?? '') === $capacity) { $validUnit = (int)($opt['price'] ?? 0); break; }
        }
        if ($validUnit <= 0 || $validUnit !== $unitPrice) {
            echo json_encode(['success' => false, 'message' => '잘못된 용량/단가입니다.']);
            exit;
        }

        // Calendar 일할 계산: 첫 달 일할 + 정상 N개월
        $nowTs = time();
        $daysInMonth = (int)date('t', $nowTs);
        $dayOfMonth = (int)date('j', $nowTs);
        $firstMonthDays = max(1, $daysInMonth - $dayOfMonth + 1);
        $firstMonthAmount = (int)round($validUnit * $firstMonthDays / 30);

        // 다음달 1일 ~ 호스팅 만료일까지 calendar 월수
        $billingStartTs = strtotime('first day of next month', $nowTs);
        $expiresTs = strtotime($sub['expires_at']);
        $normalMonths = max(0,
            ((int)date('Y', $expiresTs) - (int)date('Y', $billingStartTs)) * 12
            + ((int)date('n', $expiresTs) - (int)date('n', $billingStartTs))
            + 1
        );
        $normalAmount = $validUnit * $normalMonths;
        $totalAmount = $firstMonthAmount + $normalAmount;
        // total_months 는 표시용 (첫 달 일할 + 정상 N개월)
        $months = $normalMonths + ($firstMonthDays > 0 ? 1 : 0);
        $currency = $sub['currency'] ?? 'JPY';

        $customerId = $sub['payment_customer_id'] ?? '';
        $cardToken = trim($input['card_token'] ?? '');

        // 카드 미등록 + 토큰 미전송 → 결제 불가
        if (!$customerId && !$cardToken) {
            echo json_encode(['success' => false, 'message' => '카드 정보가 필요합니다.']);
            exit;
        }

        require_once BASE_PATH . '/rzxlib/Modules/Payment/PaymentManager.php';
        try {
            $payMgr = new \RzxLib\Modules\Payment\PaymentManager($pdo, $prefix);
            $gateway = $payMgr->gateway();
            $gwName = $payMgr->getGatewayName();
            if (!method_exists($gateway, 'chargeCustomer')) {
                echo json_encode(['success' => false, 'message' => '현재 게이트웨이는 저장 카드 결제를 지원하지 않습니다.']);
                exit;
            }

            // card_token 이 있으면 → 신규 Customer 생성 (기존 카드 만료/거절 시 교체 포함)
            $newCustomerCreated = false;
            $oldCustomerId = $customerId;
            if ($cardToken) {
                if (!method_exists($gateway, 'createCustomer')) {
                    echo json_encode(['success' => false, 'message' => '현재 게이트웨이는 카드 등록을 지원하지 않습니다.']);
                    exit;
                }
                $emailStmt = $pdo->prepare("SELECT email FROM {$prefix}users WHERE id = ?");
                $emailStmt->execute([$userId]);
                $userEmail = $emailStmt->fetchColumn() ?: '';
                $cardHolder = trim($input['card_holder'] ?? '');
                $custMeta = [
                    'order_id' => $sub['order_id'],
                    'addon' => 'storage',
                    'replaced_from' => $oldCustomerId ?: 'none',
                ];
                if ($cardHolder !== '') $custMeta['card_holder'] = $cardHolder;
                $custResult = $gateway->createCustomer($cardToken, $userEmail, $custMeta);
                if (!($custResult['success'] ?? false)) {
                    echo json_encode(['success' => false, 'message' => '카드 등록 실패: ' . ($custResult['message'] ?? ''), 'card_error' => true]);
                    exit;
                }
                $customerId = $custResult['customer_id'];
                $newCustomerCreated = true;
            }

            $description = "VosCMS Storage Addon +{$capacity} ({$months}m) order#{$sub['order_id']}";
            $payResult = $gateway->chargeCustomer($customerId, $totalAmount, strtolower($currency), $description);
        } catch (\Throwable $e) {
            error_log("[pay_storage_addon] gateway error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => '결제 처리 중 오류가 발생했습니다.']);
            exit;
        }

        if (!$payResult->isSuccessful()) {
            $msg = $payResult->failureMessage ?? '결제에 실패했습니다.';
            // 카드 자체 문제 → 클라이언트에서 새 카드 입력 유도
            $cardErrorCodes = [
                'card_declined', 'expired_card', 'incorrect_card_data', 'invalid_card_data',
                'invalid_expiry_month', 'invalid_expiry_year', 'invalid_cvc', 'invalid_number',
                'processing_error', 'unacceptable_brand', 'invalid_card', 'card_flagged',
            ];
            $code = (string)($payResult->failureCode ?? '');
            $isCardError = in_array($code, $cardErrorCodes, true);
            // 신규 customer 생성됐는데 charge 실패 시 — 그 customer 삭제 (오프냔 방지)
            if ($newCustomerCreated && $customerId) {
                try { $gateway->deleteCustomer($customerId); } catch (\Throwable $de) {}
            }
            echo json_encode([
                'success' => false,
                'message' => $msg,
                'card_error' => $isCardError,
                'failure_code' => $code,
            ]);
            exit;
        }

        // 트랜잭션 — payment + addon subscription + hosting metadata
        $pdo->beginTransaction();
        try {
            // 1) 결제 기록
            $payUuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
            $payStmt = $pdo->prepare("INSERT INTO {$prefix}payments
                (uuid, user_id, order_id, payment_key, gateway, method, method_detail, amount, status, paid_at, raw_response)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'paid', NOW(), ?)");
            $payStmt->execute([
                $payUuid, $userId, $sub['order_id'],
                $payResult->paymentKey, $gwName, $payResult->method,
                json_encode($payResult->methodDetail ?? []),
                $totalAmount, json_encode($payResult->raw ?? []),
            ]);

            // 2) addon subscription INSERT (status='active', expires_at = hosting.expires_at)
            $now = date('Y-m-d H:i:s');
            $label = "추가 용량 +" . $capacity;
            $billingStart = date('Y-m-01 00:00:00', $billingStartTs);
            $addonMeta = [
                'addon_type' => 'storage',
                'capacity' => $capacity,
                'unit_price' => $validUnit,
                'first_month_days' => $firstMonthDays,
                'first_month_amount' => $firstMonthAmount,
                'normal_months' => $normalMonths,
                'normal_amount' => $normalAmount,
                'parent_hosting_sub_id' => $hostSubId,
                'payment_key' => $payResult->paymentKey,
                'paid_at' => $now,
            ];
            $insertStmt = $pdo->prepare("INSERT INTO {$prefix}subscriptions
                (order_id, user_id, type, service_class, label, unit_price, quantity, billing_amount,
                 billing_cycle, billing_months, currency, started_at, billing_start, expires_at,
                 payment_customer_id, payment_gateway, auto_renew, status, metadata)
                VALUES (?, ?, 'addon', 'recurring', ?, ?, 1, ?, 'monthly', 1, ?, ?, ?, ?, ?, ?, 1, 'active', ?)");
            $insertStmt->execute([
                $sub['order_id'], $userId, $label, $validUnit, $validUnit,
                $currency, $now, $billingStart, $sub['expires_at'],
                $customerId, $gwName,
                json_encode($addonMeta, JSON_UNESCAPED_UNICODE),
            ]);
            $newSubId = (int)$pdo->lastInsertId();

            // 3) hosting metadata.extra_storage 누적 + (신규 카드인 경우) customer_id 저장
            $hMeta = json_decode($sub['metadata'] ?? '{}', true) ?: [];
            $hMeta['extra_storage'] = $hMeta['extra_storage'] ?? [];
            $hMeta['extra_storage'][] = [
                'capacity' => $capacity,
                'addon_sub_id' => $newSubId,
                'added_at' => $now,
            ];
            if ($newCustomerCreated) {
                $pdo->prepare("UPDATE {$prefix}subscriptions SET metadata = ?, payment_customer_id = ?, payment_gateway = ? WHERE id = ?")
                    ->execute([json_encode($hMeta, JSON_UNESCAPED_UNICODE), $customerId, $gwName, $hostSubId]);
            } else {
                $pdo->prepare("UPDATE {$prefix}subscriptions SET metadata = ? WHERE id = ?")
                    ->execute([json_encode($hMeta, JSON_UNESCAPED_UNICODE), $hostSubId]);
            }

            // 4) 로그
            $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'storage_addon_paid', ?, 'user', ?)")
                ->execute([$sub['order_id'], json_encode([
                    'capacity' => $capacity, 'unit_price' => $validUnit, 'months' => $months,
                    'total' => $totalAmount, 'addon_sub_id' => $newSubId,
                    'payment_key' => $payResult->paymentKey,
                ], JSON_UNESCAPED_UNICODE), $userId]);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log("[pay_storage_addon] DB error: " . $e->getMessage());
            // 결제는 이미 성공 — 환불 시도
            try { $gateway->refund($payResult->paymentKey, $totalAmount, 'DB rollback'); } catch (\Throwable $re) {}
            echo json_encode(['success' => false, 'message' => '서비스 등록 중 오류가 발생하여 결제를 환불 처리했습니다.']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'subscription_id' => $newSubId,
            'amount_charged' => $totalAmount,
            'months' => $months,
            'message' => "+{$capacity} 용량이 추가되었습니다.",
        ]);
        break;

    // ===== 미구현 스텁 =====
    case 'add_domain':
    case 'upgrade_plan':
    case 'add_service':
        echo json_encode(['success' => false, 'message' => '준비 중인 기능입니다.']);
        break;

    default:
        echo json_encode(['success' => false, 'message' => '알 수 없는 액션입니다.']);
        break;
}
