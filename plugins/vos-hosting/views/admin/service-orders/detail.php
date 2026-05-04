<?php
/**
 * 관리자 — 서비스 주문 상세 관리
 * 1회성 상태 변경, 구독 상태 관리
 */
if (!function_exists('__')) require_once BASE_PATH . '/rzxlib/Core/Helpers/lang.php';

// plugin lang (services) 로드
$_svcLangFile = BASE_PATH . '/plugins/vos-hosting/lang/' . \RzxLib\Core\I18n\Translator::getLocale() . '/services.php';
if (!file_exists($_svcLangFile)) {
    $_svcLangFile = BASE_PATH . '/plugins/vos-hosting/lang/en/services.php';
}
if (file_exists($_svcLangFile)) {
    \RzxLib\Core\I18n\Translator::merge('services', require $_svcLangFile);
}

$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$pdo = \RzxLib\Core\Database\Connection::getInstance()->getPdo();

// POST 핸들러에서 사용하는 헬퍼 — POST 진입 전에 정의 필수 (조건부 정의는 파일 끝 도달 전엔 미등록)
if (!function_exists('_vh_admin_human_bytes')) {
    function _vh_admin_human_bytes(int $b): string {
        $u = ['B','KB','MB','GB','TB']; $i = 0;
        while ($b >= 1024 && $i < count($u) - 1) { $b /= 1024; $i++; }
        return number_format($b, $i > 0 ? 2 : 0) . ' ' . $u[$i];
    }
}

// AJAX 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json; charset=utf-8');
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    $subId = (int)($input['subscription_id'] ?? 0);

    switch ($action) {
        case 'update_onetime_status':
            $newStatus = $input['status'] ?? '';
            $allowed = ['pending', 'active', 'suspended', 'cancelled'];
            if (!in_array($newStatus, $allowed) && $newStatus !== 'completed') {
                echo json_encode(['success' => false, 'message' => __('services.admin_orders.alert_invalid_status')]);
                exit;
            }
            $sub = $pdo->prepare("SELECT * FROM {$prefix}subscriptions WHERE id = ?");
            $sub->execute([$subId]);
            $sub = $sub->fetch(PDO::FETCH_ASSOC);
            if (!$sub || ($sub['service_class'] ?? '') !== 'one_time') {
                echo json_encode(['success' => false, 'message' => __('services.admin_orders.alert_one_time_not_found')]);
                exit;
            }
            if ($newStatus === 'completed') {
                $pdo->prepare("UPDATE {$prefix}subscriptions SET status='active', completed_at=NOW() WHERE id=?")->execute([$subId]);
            } else {
                $pdo->prepare("UPDATE {$prefix}subscriptions SET status=?, completed_at=NULL WHERE id=?")->execute([$newStatus, $subId]);
            }
            $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'status_change', ?, 'admin', ?)")
                ->execute([$sub['order_id'], json_encode(['subscription_id' => $subId, 'label' => $sub['label'], 'new_status' => $newStatus]), $_SESSION['user_id'] ?? '']);
            echo json_encode(['success' => true]);
            exit;

        case 'update_order_status':
            $newStatus = $input['status'] ?? '';
            $orderId = (int)($input['order_id'] ?? 0);
            $allowed = ['pending', 'paid', 'active', 'expired', 'cancelled', 'failed'];
            if (!in_array($newStatus, $allowed)) {
                echo json_encode(['success' => false, 'message' => __('services.admin_orders.alert_invalid_status')]);
                exit;
            }
            $pdo->prepare("UPDATE {$prefix}orders SET status=? WHERE id=?")->execute([$newStatus, $orderId]);
            $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'status_change', ?, 'admin', ?)")
                ->execute([$orderId, json_encode(['new_status' => $newStatus]), $_SESSION['user_id'] ?? '']);
            echo json_encode(['success' => true]);
            exit;
        case 'update_server_info':
            $serverData = $input['server'] ?? [];
            $stmt = $pdo->prepare("SELECT * FROM {$prefix}subscriptions WHERE id = ?");
            $stmt->execute([$subId]);
            $sub = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$sub) { echo json_encode(['success' => false, 'message' => __('services.admin_orders.alert_sub_not_found')]); exit; }
            $meta = json_decode($sub['metadata'] ?? '{}', true) ?: [];
            // 입력으로 받은 키만 부분 갱신 (home/docroot/vhost/fpm_pool/username 등 자동 채움 항목 보존)
            $meta['server'] = array_merge($meta['server'] ?? [], $serverData);
            $pdo->prepare("UPDATE {$prefix}subscriptions SET metadata = ? WHERE id = ?")
                ->execute([json_encode($meta, JSON_UNESCAPED_UNICODE), $subId]);
            $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'server_info_updated', ?, 'admin', ?)")
                ->execute([$sub['order_id'], json_encode(['subscription_id' => $subId]), $_SESSION['user_id'] ?? '']);
            echo json_encode(['success' => true]);
            exit;

        case 'update_order_memo':
            $oid = (int)($input['order_id'] ?? 0);
            $memo = $input['memo'] ?? '';
            $pdo->prepare("UPDATE {$prefix}orders SET admin_notes = ? WHERE id = ?")
                ->execute([$memo, $oid]);
            echo json_encode(['success' => true]);
            exit;

        case 'update_addon_memo':
            $memo = $input['memo'] ?? '';
            $stmt = $pdo->prepare("SELECT * FROM {$prefix}subscriptions WHERE id = ?");
            $stmt->execute([$subId]);
            $sub = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$sub) { echo json_encode(['success' => false, 'message' => __('services.admin_orders.alert_sub_not_found')]); exit; }
            $meta = json_decode($sub['metadata'] ?? '{}', true) ?: [];
            $meta['admin_memo'] = $memo;
            $pdo->prepare("UPDATE {$prefix}subscriptions SET metadata = ? WHERE id = ?")
                ->execute([json_encode($meta, JSON_UNESCAPED_UNICODE), $subId]);
            echo json_encode(['success' => true]);
            exit;

        case 'send_setup_email':
            $oid = (int)($input['order_id'] ?? 0);
            $orderStmt = $pdo->prepare("SELECT * FROM {$prefix}orders WHERE id = ?");
            $orderStmt->execute([$oid]);
            $ord = $orderStmt->fetch(PDO::FETCH_ASSOC);
            if (!$ord) { echo json_encode(['success' => false, 'message' => __('services.admin_orders.alert_order_not_found')]); exit; }
            // TODO: 실제 이메일 발송 구현 (메일 템플릿 + SMTP)
            $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'setup_email_sent', ?, 'admin', ?)")
                ->execute([$oid, json_encode(['email' => $ord['applicant_email']]), $_SESSION['user_id'] ?? '']);
            echo json_encode(['success' => true, 'message' => __('services.admin_orders.alert_setup_email_sent')]);
            exit;

        // 관리자 무료 용량 추가 — 호스팅 만료일까지 동기화, billing_amount=0
        case 'admin_add_storage_addon':
            $hostSubId = (int)($input['subscription_id'] ?? 0);
            $capacity = trim($input['capacity'] ?? '');
            $unitPrice = (int)($input['unit_price'] ?? 0);
            if (!$hostSubId || $capacity === '') {
                echo json_encode(['success' => false, 'message' => __('services.admin_orders.alert_invalid_input')]); exit;
            }
            $hSt = $pdo->prepare("SELECT * FROM {$prefix}subscriptions WHERE id = ? AND type = 'hosting'");
            $hSt->execute([$hostSubId]);
            $hSub = $hSt->fetch(PDO::FETCH_ASSOC);
            if (!$hSub) { echo json_encode(['success' => false, 'message' => __('services.admin_orders.alert_sub_not_found')]); exit; }

            $now = date('Y-m-d H:i:s');
            $label = "추가 용량 +" . $capacity;
            $addonMeta = [
                'addon_type' => 'storage',
                'capacity' => $capacity,
                'unit_price' => $unitPrice,
                'parent_hosting_sub_id' => $hostSubId,
                'admin_granted' => true,
                'granted_by' => $_SESSION['user_id'] ?? '',
                'granted_at' => $now,
            ];
            try {
                $pdo->beginTransaction();
                $insSt = $pdo->prepare("INSERT INTO {$prefix}subscriptions
                    (order_id, user_id, type, service_class, label, unit_price, quantity, billing_amount,
                     billing_cycle, billing_months, currency, started_at, expires_at,
                     auto_renew, status, metadata)
                    VALUES (?, ?, 'addon', 'recurring', ?, ?, 1, 0, 'monthly', 1, ?, ?, ?, 1, 'active', ?)");
                $insSt->execute([
                    $hSub['order_id'], $hSub['user_id'], $label, $unitPrice,
                    $hSub['currency'] ?? 'JPY', $now, $hSub['expires_at'],
                    json_encode($addonMeta, JSON_UNESCAPED_UNICODE),
                ]);
                $newSubId = (int)$pdo->lastInsertId();

                $hMeta = json_decode($hSub['metadata'] ?? '{}', true) ?: [];
                $hMeta['extra_storage'] = $hMeta['extra_storage'] ?? [];
                $hMeta['extra_storage'][] = [
                    'capacity' => $capacity,
                    'addon_sub_id' => $newSubId,
                    'added_at' => $now,
                    'admin_granted' => true,
                ];
                $pdo->prepare("UPDATE {$prefix}subscriptions SET metadata = ? WHERE id = ?")
                    ->execute([json_encode($hMeta, JSON_UNESCAPED_UNICODE), $hostSubId]);

                $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'admin_add_storage_addon', ?, 'admin', ?)")
                    ->execute([$hSub['order_id'], json_encode(['capacity' => $capacity, 'addon_sub_id' => $newSubId], JSON_UNESCAPED_UNICODE), $_SESSION['user_id'] ?? '']);
                $pdo->commit();
            } catch (\Throwable $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]); exit;
            }
            echo json_encode(['success' => true, 'subscription_id' => $newSubId, 'message' => __('services.admin_orders.alert_addon_added')]);
            exit;

        // 부가서비스 삭제 (소프트 — status=cancelled, hosting metadata.extra_storage 에서 제거)
        case 'admin_run_voscms_install':
            // install_info 를 가진 addon 에 대해 HostingProvisioner.installVoscms() 직접 호출
            $aSt = $pdo->prepare("SELECT * FROM {$prefix}subscriptions WHERE id = ? AND type = 'addon'");
            $aSt->execute([$subId]);
            $aSub = $aSt->fetch(PDO::FETCH_ASSOC);
            if (!$aSub) { echo json_encode(['success' => false, 'message' => __('services.admin_orders.alert_sub_not_found')]); exit; }
            $aMeta = json_decode($aSub['metadata'] ?? '{}', true) ?: [];
            if (empty($aMeta['install_info'])) {
                echo json_encode(['success' => false, 'message' => 'install_info 없음 (설치 지원 addon 만 가능)']); exit;
            }
            if (!empty($aMeta['install_completed_at'])) {
                echo json_encode(['success' => false, 'message' => '이미 설치 완료됨 (' . $aMeta['install_completed_at'] . ')']); exit;
            }

            // 주문 + 호스팅 subscription 로드
            $oSt = $pdo->prepare("SELECT id, order_number, domain FROM {$prefix}orders WHERE id = ? LIMIT 1");
            $oSt->execute([$aSub['order_id']]);
            $ord = $oSt->fetch(PDO::FETCH_ASSOC);
            if (!$ord) { echo json_encode(['success' => false, 'message' => '주문 없음']); exit; }

            $hSt = $pdo->prepare("SELECT id, metadata FROM {$prefix}subscriptions WHERE order_id = ? AND type = 'hosting' LIMIT 1");
            $hSt->execute([$aSub['order_id']]);
            $hSub = $hSt->fetch(PDO::FETCH_ASSOC);
            if (!$hSub) { echo json_encode(['success' => false, 'message' => '호스팅 subscription 없음']); exit; }
            $hMeta = json_decode($hSub['metadata'] ?? '{}', true) ?: [];
            $dbInfo = $hMeta['server']['db'] ?? null;

            // DB 정보 폴백 — 고객 docroot 의 .env
            if (!$dbInfo || empty($dbInfo['db_user']) || empty($dbInfo['db_pass'])) {
                $custEnv = '/var/www/customers/' . $ord['order_number'] . '/public_html/.env';
                if (file_exists($custEnv)) {
                    $envVars = [];
                    foreach (file($custEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                        $line = trim($line);
                        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) continue;
                        [$k, $v] = explode('=', $line, 2);
                        $envVars[trim($k)] = trim($v, " \t\n\r\0\x0B\"'");
                    }
                    $dbInfo = [
                        'db_host' => $envVars['DB_HOST'] ?? '127.0.0.1',
                        'db_port' => $envVars['DB_PORT'] ?? '3306',
                        'db_name' => $envVars['DB_DATABASE'] ?? '',
                        'db_user' => $envVars['DB_USERNAME'] ?? '',
                        'db_pass' => $envVars['DB_PASSWORD'] ?? '',
                        'db_prefix' => $envVars['DB_PREFIX'] ?? 'rzx_',
                    ];
                }
            }
            if (!$dbInfo || empty($dbInfo['db_user']) || empty($dbInfo['db_pass'])) {
                echo json_encode(['success' => false, 'message' => '호스팅 DB 정보 없음 — 호스팅 프로비저닝 필요']); exit;
            }

            // 자동 설치 실행
            try {
                @set_time_limit(600);
                $provisioner = new \RzxLib\Core\Hosting\HostingProvisioner($pdo);
                $installResult = $provisioner->installVoscms($ord['order_number'], $ord['domain'], $dbInfo, $aMeta['install_info']);

                if (!empty($installResult['success'])) {
                    $aMeta['install_completed_at'] = $installResult['installed_at'] ?? date('c');
                    $aMeta['install_admin_url'] = $installResult['admin_url'] ?? null;
                    $pdo->prepare("UPDATE {$prefix}subscriptions SET metadata = ?, completed_at = NOW() WHERE id = ?")
                        ->execute([json_encode($aMeta, JSON_UNESCAPED_UNICODE), $subId]);
                    $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'voscms_installed', ?, 'admin', ?)")
                        ->execute([$ord['id'], json_encode($installResult, JSON_UNESCAPED_UNICODE), $_SESSION['user_id'] ?? '']);
                    echo json_encode([
                        'success' => true,
                        'message' => 'VosCMS 자동 설치 완료',
                        'admin_url' => $installResult['admin_url'] ?? null,
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => $installResult['error'] ?? '설치 실패',
                        'detail' => $installResult,
                    ]);
                }
            } catch (\Throwable $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;

        // ── 메일 계정 추가 (어드민) ──────────────────────────────
        case 'add_mail_account': {
            $sid = (int)($input['subscription_id'] ?? 0);
            $local = strtolower(trim((string)($input['local'] ?? '')));
            $password = (string)($input['password'] ?? '');
            if (!$sid || !preg_match('/^[a-z0-9._-]{2,32}$/', $local) || strlen($password) < 8) {
                echo json_encode(['success' => false, 'message' => '입력값 확인 (local 2~32자, 비번 8자 이상)']); exit;
            }
            // subscription → order 조회 (POST 핸들러에선 $order 글로벌 미정의)
            $sst = $pdo->prepare("SELECT s.*, o.domain AS _order_domain, o.id AS _order_id FROM {$prefix}subscriptions s JOIN {$prefix}orders o ON o.id = s.order_id WHERE s.id = ? AND s.type = 'mail'");
            $sst->execute([$sid]);
            $sub = $sst->fetch(PDO::FETCH_ASSOC);
            if (!$sub) { echo json_encode(['success' => false, 'message' => 'mail 구독 없음']); exit; }
            $domain = strtolower((string)($sub['_order_domain'] ?? ''));
            if (!$domain) { echo json_encode(['success' => false, 'message' => '주문 도메인 없음']); exit; }
            $address = $local . '@' . $domain;

            $sm = json_decode($sub['metadata'] ?? '{}', true) ?: [];
            $accounts = $sm['mail_accounts'] ?? [];
            foreach ($accounts as $a) {
                if (strcasecmp($a['address'] ?? '', $address) === 0) {
                    echo json_encode(['success' => false, 'message' => '이미 존재하는 메일']); exit;
                }
            }
            $mailServerOk = null;
            try {
                if (class_exists('\RzxLib\Core\Mail\MailAccountManager')) {
                    $mam = new \RzxLib\Core\Mail\MailAccountManager($pdo);
                    $r = $mam->addAccount($address, $password);
                    $mailServerOk = !empty($r['success']);
                }
            } catch (\Throwable $e) { error_log('[admin add mail] '.$e->getMessage()); $mailServerOk = false; }
            $accounts[] = ['address' => $address, 'password' => 1, 'created_at' => date('c'), 'admin_added' => 1];
            $sm['mail_accounts'] = $accounts;
            $sm['accounts'] = count($accounts);
            $pdo->prepare("UPDATE {$prefix}subscriptions SET metadata = ?, quantity = ?, updated_at = NOW() WHERE id = ?")
                ->execute([json_encode($sm, JSON_UNESCAPED_UNICODE), count($accounts), $sid]);
            $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'mail_account_added', ?, 'admin', ?)")
                ->execute([$sub['_order_id'], json_encode(['address' => $address, 'mail_server_ok' => $mailServerOk]), $_SESSION['user_id'] ?? '']);
            echo json_encode(['success' => true, 'address' => $address, 'mail_server_ok' => $mailServerOk]);
            exit;
        }

        // ── 메일 계정 삭제 (어드민) ──────────────────────────────
        case 'delete_mail_account': {
            $sid = (int)($input['subscription_id'] ?? 0);
            $address = strtolower(trim((string)($input['address'] ?? '')));
            if (!$sid || !$address) { echo json_encode(['success' => false, 'message' => 'invalid input']); exit; }
            $sst = $pdo->prepare("SELECT * FROM {$prefix}subscriptions WHERE id = ?");
            $sst->execute([$sid]);
            $sub = $sst->fetch(PDO::FETCH_ASSOC);
            if (!$sub) { echo json_encode(['success' => false, 'message' => 'sub not found']); exit; }
            $sm = json_decode($sub['metadata'] ?? '{}', true) ?: [];
            $accounts = $sm['mail_accounts'] ?? [];
            $kept = [];
            foreach ($accounts as $a) {
                if (strcasecmp($a['address'] ?? '', $address) !== 0) $kept[] = $a;
            }
            try {
                if (class_exists('\RzxLib\Core\Mail\MailAccountManager')) {
                    $mam = new \RzxLib\Core\Mail\MailAccountManager($pdo);
                    @$mam->deleteAccount($address);
                }
            } catch (\Throwable $e) { error_log('[admin delete mail] '.$e->getMessage()); }
            $sm['mail_accounts'] = $kept;
            $sm['accounts'] = count($kept);
            $pdo->prepare("UPDATE {$prefix}subscriptions SET metadata = ?, quantity = ?, updated_at = NOW() WHERE id = ?")
                ->execute([json_encode($sm, JSON_UNESCAPED_UNICODE), count($kept), $sid]);
            $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'mail_account_deleted', ?, 'admin', ?)")
                ->execute([$sub['order_id'], json_encode(['address' => $address]), $_SESSION['user_id'] ?? '']);
            echo json_encode(['success' => true, 'message' => '삭제 완료']);
            exit;
        }

        // ── 메일 비밀번호 변경 (어드민) ───────────────────────────
        case 'change_mail_password': {
            $sid = (int)($input['subscription_id'] ?? 0);
            $address = strtolower(trim((string)($input['address'] ?? '')));
            $password = (string)($input['password'] ?? '');
            if (!$sid || !$address || strlen($password) < 8) { echo json_encode(['success' => false, 'message' => 'invalid input']); exit; }
            $sst = $pdo->prepare("SELECT order_id FROM {$prefix}subscriptions WHERE id = ?");
            $sst->execute([$sid]);
            $oid = (int)$sst->fetchColumn();
            try {
                if (class_exists('\RzxLib\Core\Mail\MailAccountManager')) {
                    $mam = new \RzxLib\Core\Mail\MailAccountManager($pdo);
                    $r = $mam->changePassword($address, $password);
                    if (empty($r['success'])) { echo json_encode(['success' => false, 'message' => $r['message'] ?? '실패']); exit; }
                }
            } catch (\Throwable $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); exit; }
            $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'mail_password_changed', ?, 'admin', ?)")
                ->execute([$oid, json_encode(['address' => $address]), $_SESSION['user_id'] ?? '']);
            echo json_encode(['success' => true, 'message' => '비밀번호 변경 완료']);
            exit;
        }

        // ── 부가서비스 가격 견적 (결제 모달용) ────────────────────
        case 'admin_addon_quote': {
            $oid = (int)($input['order_id'] ?? 0);
            $addonId = strtolower(trim((string)($input['addon_id'] ?? '')));
            $hSt = $pdo->prepare("SELECT * FROM {$prefix}subscriptions WHERE order_id = ? AND type = 'hosting' LIMIT 1");
            $hSt->execute([$oid]);
            $hs = $hSt->fetch(PDO::FETCH_ASSOC);
            if (!$hs) { echo json_encode(['success' => false, 'message' => '호스팅 구독 없음']); exit; }
            $sSt = $pdo->prepare("SELECT `value` FROM {$prefix}settings WHERE `key` = 'service_addons' LIMIT 1");
            $sSt->execute();
            $addons = json_decode($sSt->fetchColumn() ?: '[]', true) ?: [];
            $found = null;
            foreach ($addons as $a) { if (strtolower($a['_id'] ?? '') === $addonId) { $found = $a; break; } }
            if (!$found) { echo json_encode(['success' => false, 'message' => 'addon not found']); exit; }
            $unit = (int)($found['price'] ?? 0);
            $oneTime = !empty($found['one_time']);
            $months = max(1, (int)ceil((strtotime($hs['expires_at']) - time()) / (86400 * 30)));
            $subtotal = $oneTime ? $unit : ($unit * $months);
            $tax = (int)round($subtotal * 0.10);
            $total = $subtotal + $tax;
            echo json_encode([
                'success' => true,
                'unit_price' => $unit,
                'unit' => (string)($found['unit'] ?? ''),
                'one_time' => $oneTime,
                'months' => $oneTime ? 1 : $months,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'currency' => $hs['currency'] ?: 'JPY',
            ]);
            exit;
        }

        // ── 부가서비스 추가 + 결제 처리 ─────────────────────────
        case 'admin_add_addon': {
            $oid = (int)($input['order_id'] ?? 0);
            $addonId = strtolower(trim((string)($input['addon_id'] ?? '')));
            $payMethod = (string)($input['payment_method'] ?? '');
            $cashReceived = (int)($input['cash_received'] ?? 0);
            $freeReason = trim((string)($input['free_reason'] ?? ''));
            if (!in_array($payMethod, ['cash','free'], true)) { echo json_encode(['success' => false, 'message' => '결제 방식: cash 또는 free']); exit; }

            $hSt = $pdo->prepare("SELECT * FROM {$prefix}subscriptions WHERE order_id = ? AND type = 'hosting' LIMIT 1");
            $hSt->execute([$oid]);
            $hs = $hSt->fetch(PDO::FETCH_ASSOC);
            if (!$hs) { echo json_encode(['success' => false, 'message' => '호스팅 구독 없음']); exit; }
            $oSt2 = $pdo->prepare("SELECT user_id, order_number FROM {$prefix}orders WHERE id = ?");
            $oSt2->execute([$oid]);
            $oRow = $oSt2->fetch(PDO::FETCH_ASSOC);

            $sSt = $pdo->prepare("SELECT `value` FROM {$prefix}settings WHERE `key` = 'service_addons' LIMIT 1");
            $sSt->execute();
            $addons = json_decode($sSt->fetchColumn() ?: '[]', true) ?: [];
            $found = null;
            foreach ($addons as $a) { if (strtolower($a['_id'] ?? '') === $addonId) { $found = $a; break; } }
            if (!$found) { echo json_encode(['success' => false, 'message' => 'addon not found']); exit; }

            // 중복 신청 방지 (같은 라벨)
            $exSt = $pdo->prepare("SELECT id FROM {$prefix}subscriptions WHERE order_id = ? AND type = 'addon' AND label = ?");
            $exSt->execute([$oid, $found['label'] ?? '']);
            if ($exSt->fetchColumn()) { echo json_encode(['success' => false, 'message' => '이미 신청된 부가서비스']); exit; }

            $unit = (int)($found['price'] ?? 0);
            $oneTime = !empty($found['one_time']);
            $months = max(1, (int)ceil((strtotime($hs['expires_at']) - time()) / (86400 * 30)));
            $subtotal = $oneTime ? $unit : ($unit * $months);
            $tax = (int)round($subtotal * 0.10);
            $total = $subtotal + $tax;

            if ($payMethod === 'cash' && $cashReceived <= 0) { echo json_encode(['success' => false, 'message' => '받은 금액 입력 필요']); exit; }
            if ($payMethod === 'free' && $freeReason === '') { echo json_encode(['success' => false, 'message' => '무료 사유 필요']); exit; }
            if ($payMethod === 'free') { $subtotal = 0; $tax = 0; $total = 0; }

            $pdo->beginTransaction();
            try {
                $meta = [
                    'admin_created' => 1, 'addon_id' => $addonId,
                    'unit_price' => $unit, 'months' => $months,
                    'paid_method' => $payMethod, 'paid_at' => date('c'),
                ];
                $serviceClass = $oneTime ? 'one_time' : 'recurring';
                $billingCycle = $oneTime ? 'once' : 'monthly';
                $billingMonths = $oneTime ? null : $months;
                $expiresAt = $oneTime ? $hs['expires_at'] : $hs['expires_at'];
                $pdo->prepare("INSERT INTO {$prefix}subscriptions
                    (order_id, user_id, type, service_class, label, unit_price, quantity, billing_amount, billing_cycle, billing_months,
                     currency, started_at, expires_at, status, metadata)
                    VALUES (?, ?, 'addon', ?, ?, ?, 1, ?, ?, ?, ?, NOW(), ?, 'active', ?)")
                    ->execute([
                        $oid, $oRow['user_id'], $serviceClass,
                        (string)($found['label'] ?? ''),
                        $unit, $total, $billingCycle, $billingMonths,
                        $hs['currency'] ?: 'JPY', $expiresAt,
                        json_encode($meta, JSON_UNESCAPED_UNICODE),
                    ]);

                if ($payMethod !== 'free' && $total > 0) {
                    $payUuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                        mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff),
                        mt_rand(0,0x0fff)|0x4000,mt_rand(0,0x3fff)|0x8000,
                        mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff));
                    $pdo->prepare("INSERT INTO {$prefix}payments
                        (uuid, user_id, order_id, payment_key, gateway, method, amount, status, paid_at, metadata, created_at, updated_at)
                        VALUES (?, ?, ?, ?, 'manual', ?, ?, 'paid', NOW(), ?, NOW(), NOW())")
                        ->execute([
                            $payUuid, $oRow['user_id'], $oRow['order_number'],
                            'manual_addon_' . $payUuid, $payMethod, $total,
                            json_encode([
                                'source' => 'admin_add_addon', 'addon_id' => $addonId,
                                'unit' => $unit, 'months' => $months,
                                'cash_received' => $payMethod === 'cash' ? $cashReceived : null,
                                'admin_id' => $_SESSION['user_id'] ?? '',
                            ], JSON_UNESCAPED_UNICODE),
                        ]);
                }
                $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'addon_paid_added', ?, 'admin', ?)")
                    ->execute([
                        $oid,
                        json_encode(['addon_id' => $addonId, 'method' => $payMethod, 'total' => $total], JSON_UNESCAPED_UNICODE),
                        $_SESSION['user_id'] ?? '',
                    ]);
                $pdo->commit();
                echo json_encode(['success' => true, 'message' => '부가서비스 추가 + 결제 완료', 'total' => $total]);
            } catch (\Throwable $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
        }

        // ── 비즈니스 메일 가격 견적 (결제 모달 표시 전 호출) ──────────
        case 'admin_bizmail_quote': {
            $oid = (int)($input['order_id'] ?? 0);
            $accounts = max(1, min(100, (int)($input['accounts'] ?? 1)));
            $hSt = $pdo->prepare("SELECT * FROM {$prefix}subscriptions WHERE order_id = ? AND type = 'hosting' LIMIT 1");
            $hSt->execute([$oid]);
            $hs = $hSt->fetch(PDO::FETCH_ASSOC);
            if (!$hs) { echo json_encode(['success' => false, 'message' => '호스팅 구독 없음']); exit; }
            // settings 에서 단가
            $sSt = $pdo->prepare("SELECT `value` FROM {$prefix}settings WHERE `key` = 'service_addons' LIMIT 1");
            $sSt->execute();
            $addons = json_decode($sSt->fetchColumn() ?: '[]', true) ?: [];
            $unit = 0;
            foreach ($addons as $a) { if (($a['_id'] ?? '') === 'bizmail') { $unit = (int)($a['price'] ?? 0); break; } }
            // 호스팅 남은 개월 (현재 → 호스팅 만료) — 동기 처리
            $remainMonths = max(1, (int)ceil((strtotime($hs['expires_at']) - time()) / (86400 * 30)));
            $subtotal = $unit * $accounts * $remainMonths;
            $tax = (int)round($subtotal * 0.10);
            $total = $subtotal + $tax;
            echo json_encode([
                'success' => true,
                'unit_price' => $unit,
                'accounts' => $accounts,
                'months' => $remainMonths,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'currency' => $hs['currency'] ?: 'JPY',
            ]);
            exit;
        }

        // ── 비즈니스 메일 구독 추가 + 결제 처리 ────────────────────
        case 'admin_add_bizmail_sub': {
            $oid = (int)($input['order_id'] ?? 0);
            $accounts = max(1, min(100, (int)($input['accounts'] ?? 1)));
            $payMethod = (string)($input['payment_method'] ?? '');
            $cashReceived = (int)($input['cash_received'] ?? 0);
            $freeReason = trim((string)($input['free_reason'] ?? ''));

            if (!in_array($payMethod, ['cash','free'], true)) {
                echo json_encode(['success' => false, 'message' => '결제 방식: cash 또는 free']); exit;
            }
            $exSt = $pdo->prepare("SELECT id FROM {$prefix}subscriptions WHERE order_id = ? AND type = 'mail' AND label LIKE '%비즈니스%'");
            $exSt->execute([$oid]);
            if ($exSt->fetchColumn()) { echo json_encode(['success' => false, 'message' => '이미 비즈니스 메일 구독 있음']); exit; }

            $hSt = $pdo->prepare("SELECT * FROM {$prefix}subscriptions WHERE order_id = ? AND type = 'hosting' LIMIT 1");
            $hSt->execute([$oid]);
            $hs = $hSt->fetch(PDO::FETCH_ASSOC);
            if (!$hs) { echo json_encode(['success' => false, 'message' => '호스팅 구독 없음']); exit; }
            $oSt2 = $pdo->prepare("SELECT user_id, order_number FROM {$prefix}orders WHERE id = ?");
            $oSt2->execute([$oid]);
            $oRow = $oSt2->fetch(PDO::FETCH_ASSOC);

            // 단가
            $sSt = $pdo->prepare("SELECT `value` FROM {$prefix}settings WHERE `key` = 'service_addons' LIMIT 1");
            $sSt->execute();
            $addons = json_decode($sSt->fetchColumn() ?: '[]', true) ?: [];
            $unit = 0;
            foreach ($addons as $a) { if (($a['_id'] ?? '') === 'bizmail') { $unit = (int)($a['price'] ?? 0); break; } }
            $remainMonths = max(1, (int)ceil((strtotime($hs['expires_at']) - time()) / (86400 * 30)));
            $subtotal = $unit * $accounts * $remainMonths;
            $tax = (int)round($subtotal * 0.10);
            $total = $subtotal + $tax;

            // 결제 처리
            if ($payMethod === 'cash' && $cashReceived <= 0) {
                echo json_encode(['success' => false, 'message' => '받은 금액 입력 필요']); exit;
            }
            if ($payMethod === 'free' && $freeReason === '') {
                echo json_encode(['success' => false, 'message' => '무료 처리 사유 필요']); exit;
            }
            // free 면 청구 0 처리
            if ($payMethod === 'free') { $subtotal = 0; $tax = 0; $total = 0; }

            $pdo->beginTransaction();
            try {
                $meta = [
                    'accounts' => 0, 'mail_accounts' => [], 'admin_created' => 1,
                    'unit_price' => $unit, 'qty' => $accounts, 'months' => $remainMonths,
                    'paid_method' => $payMethod, 'paid_at' => date('c'),
                ];
                $pdo->prepare("INSERT INTO {$prefix}subscriptions
                    (order_id, user_id, type, service_class, label, unit_price, quantity, billing_amount, billing_cycle, billing_months,
                     currency, started_at, expires_at, status, metadata)
                    VALUES (?, ?, 'mail', 'recurring', '비즈니스 메일', ?, ?, ?, 'monthly', ?, ?, NOW(), ?, 'active', ?)")
                    ->execute([
                        $oid, $oRow['user_id'],
                        $unit, $accounts, $total, $remainMonths,
                        $hs['currency'] ?: 'JPY', $hs['expires_at'],
                        json_encode($meta, JSON_UNESCAPED_UNICODE),
                    ]);

                // free 가 아닐 때만 payment 전표
                if ($payMethod !== 'free' && $total > 0) {
                    $payUuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                        mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff),
                        mt_rand(0,0x0fff)|0x4000,mt_rand(0,0x3fff)|0x8000,
                        mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff));
                    $payKey = 'manual_bizmail_' . $payUuid;
                    $pdo->prepare("INSERT INTO {$prefix}payments
                        (uuid, user_id, order_id, payment_key, gateway, method, amount, status, paid_at, metadata, created_at, updated_at)
                        VALUES (?, ?, ?, ?, 'manual', ?, ?, 'paid', NOW(), ?, NOW(), NOW())")
                        ->execute([
                            $payUuid, $oRow['user_id'], $oRow['order_number'],
                            $payKey, $payMethod, $total,
                            json_encode([
                                'source' => 'admin_add_bizmail_sub',
                                'unit' => $unit, 'qty' => $accounts, 'months' => $remainMonths,
                                'cash_received' => $payMethod === 'cash' ? $cashReceived : null,
                                'admin_id' => $_SESSION['user_id'] ?? '',
                            ], JSON_UNESCAPED_UNICODE),
                        ]);
                }

                $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'bizmail_sub_added', ?, 'admin', ?)")
                    ->execute([
                        $oid,
                        json_encode([
                            'method' => $payMethod, 'accounts' => $accounts, 'total' => $total,
                            'unit' => $unit, 'months' => $remainMonths,
                            'cash_received' => $payMethod === 'cash' ? $cashReceived : null,
                            'free_reason' => $payMethod === 'free' ? $freeReason : null,
                        ], JSON_UNESCAPED_UNICODE),
                        $_SESSION['user_id'] ?? '',
                    ]);
                $pdo->commit();
                echo json_encode(['success' => true, 'message' => '비즈니스 메일 구독 추가 + 결제 완료', 'total' => $total]);
            } catch (\Throwable $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
        }

        // ── 주문 활동 로그 추가 로드 (이전 로그) ──────────────────────
        case 'load_more_logs': {
            $oid = (int)($input['order_id'] ?? 0);
            $beforeId = (int)($input['before_id'] ?? PHP_INT_MAX);
            $limit = max(10, min(100, (int)($input['limit'] ?? 50)));
            $st = $pdo->prepare("SELECT * FROM {$prefix}order_logs WHERE order_id = ? AND id < ? ORDER BY id DESC LIMIT {$limit}");
            $st->execute([$oid, $beforeId]);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC);
            // 라벨 매핑 (메인 렌더와 동일, i18n 적용)
            $_lblMap = [
                'created' => [__('services.admin_orders.act_created'),'blue'],
                'paid' => [__('services.admin_orders.act_paid'),'green'],
                'failed' => [__('services.admin_orders.act_failed'),'red'],
                'status_change' => [__('services.admin_orders.act_status_change'),'purple'],
                'mail_provisioned' => [__('services.admin_orders.act_mail_provisioned'),'green'],
                'mail_provision_failed' => [__('services.admin_orders.act_mail_provision_failed'),'red'],
                'hosting_provisioned' => [__('services.admin_orders.act_hosting_provisioned'),'green'],
                'hosting_provision_failed' => [__('services.admin_orders.act_hosting_provision_failed'),'red'],
                'hosting_provision_skipped' => [__('services.admin_orders.act_hosting_provision_skipped'),'zinc'],
                'hosting_deprovisioned' => [__('services.admin_orders.act_hosting_deprovisioned'),'red'],
                'admin_created' => [__('services.admin_orders.act_admin_created'),'blue'],
                'admin_delete_addon' => [__('services.admin_orders.act_admin_delete_addon'),'red'],
                'voscms_installed' => [__('services.admin_orders.act_voscms_installed'),'green'],
                'voscms_reinstalled' => [__('services.admin_orders.act_voscms_reinstalled'),'violet'],
                'vhost_toggle' => [__('services.admin_orders.act_vhost_toggle'),'amber'],
                'ssl_renew' => [__('services.admin_orders.act_ssl_renew'),'emerald'],
                'db_pw_reset' => [__('services.admin_orders.act_db_pw_reset'),'amber'],
                'reprovision_triggered' => [__('services.admin_orders.act_reprovision_triggered'),'violet'],
                'server_info_updated' => [__('services.admin_orders.act_server_info_updated'),'zinc'],
                'setup_email_sent' => [__('services.admin_orders.act_setup_email_sent'),'blue'],
                'admin_add_storage_addon' => [__('services.admin_orders.act_admin_add_storage_addon'),'violet'],
            ];
            foreach ($rows as &$r) {
                $r['label'] = $_lblMap[$r['action']][0] ?? $r['action'];
                $r['color'] = $_lblMap[$r['action']][1] ?? 'zinc';
            }
            unset($r);
            $tcSt = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}order_logs WHERE order_id = ?");
            $tcSt->execute([$oid]);
            echo json_encode(['success' => true, 'logs' => $rows, 'total' => (int)$tcSt->fetchColumn()], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // ── 호스팅 실시간 상태 (SSL/disk/db/nginx) ─────────────────────
        case 'hosting_status': {
            $oid = (int)($input['order_id'] ?? 0);
            $oSt = $pdo->prepare("SELECT * FROM {$prefix}orders WHERE id = ?");
            $oSt->execute([$oid]);
            $ord = $oSt->fetch(PDO::FETCH_ASSOC);
            if (!$ord) { echo json_encode(['success' => false, 'message' => 'order not found']); exit; }
            $orderNumber = $ord['order_number'];
            $domain = (string)$ord['domain'];
            $username = 'vos_' . preg_replace('/[^A-Za-z0-9]/', '', $orderNumber);
            $home = '/var/www/customers/' . $orderNumber;
            $vhost = '/etc/nginx/sites-available/' . $domain . '.conf';
            $vhostEnabled = '/etc/nginx/sites-enabled/' . $domain . '.conf';

            $r = ['success' => true, 'order_number' => $orderNumber, 'domain' => $domain];
            // SSL
            $sslOut = @shell_exec('sudo /usr/bin/certbot certificates --cert-name ' . escapeshellarg($domain) . ' 2>&1');
            $ssl = ['present' => false];
            if ($sslOut && preg_match('/Expiry Date:\s*([\d\-:\sUTC+]+)\s*\(VALID:\s*(\d+)\s*days\)/', $sslOut, $m)) {
                $ssl = ['present' => true, 'expiry' => trim($m[1]), 'days_left' => (int)$m[2]];
            } elseif ($sslOut && preg_match('/INVALID|EXPIRED/i', $sslOut)) {
                $ssl = ['present' => true, 'expired' => true];
            }
            $r['ssl'] = $ssl;
            // disk
            $diskBytes = null;
            $du = @shell_exec('sudo /usr/bin/du -sb ' . escapeshellarg($home) . ' 2>/dev/null');
            if ($du && preg_match('/^(\d+)/', $du, $m)) $diskBytes = (int)$m[1];
            $r['disk'] = ['home' => $home, 'bytes' => $diskBytes, 'human' => $diskBytes !== null ? _vh_admin_human_bytes($diskBytes) : null];
            // db
            $dbName = $username;
            $dbu = ['name' => $dbName, 'bytes' => null];
            try {
                $sst = $pdo->prepare("SELECT IFNULL(SUM(data_length+index_length),0) FROM information_schema.tables WHERE table_schema=?");
                $sst->execute([$dbName]);
                $b = (int)$sst->fetchColumn();
                $tcSt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=?");
                $tcSt->execute([$dbName]);
                $dbu = ['name' => $dbName, 'bytes' => $b, 'human' => _vh_admin_human_bytes($b), 'table_count' => (int)$tcSt->fetchColumn()];
            } catch (\Throwable $e) { $dbu['error'] = $e->getMessage(); }
            $r['db'] = $dbu;
            // nginx
            $r['nginx'] = ['vhost_path' => $vhost, 'vhost_exists' => file_exists($vhost), 'enabled' => file_exists($vhostEnabled)];
            echo json_encode($r, JSON_UNESCAPED_UNICODE);
            exit;
        }

        // ── nginx vhost 활성/비활성 토글 ───────────────────────────
        case 'toggle_vhost': {
            $oid = (int)($input['order_id'] ?? 0);
            $enable = !empty($input['enable']);
            $oSt = $pdo->prepare("SELECT * FROM {$prefix}orders WHERE id = ?");
            $oSt->execute([$oid]);
            $ord = $oSt->fetch(PDO::FETCH_ASSOC);
            if (!$ord) { echo json_encode(['success' => false, 'message' => 'order not found']); exit; }
            $domain = (string)$ord['domain'];
            $avail = '/etc/nginx/sites-available/' . $domain . '.conf';
            $en = '/etc/nginx/sites-enabled/' . $domain . '.conf';
            if (!file_exists($avail)) { echo json_encode(['success' => false, 'message' => 'vhost 파일 없음']); exit; }
            if ($enable) {
                shell_exec('sudo /usr/bin/ln -sf ' . escapeshellarg($avail) . ' ' . escapeshellarg($en) . ' 2>&1');
            } else {
                shell_exec('sudo /usr/bin/rm ' . escapeshellarg($en) . ' 2>&1');
            }
            shell_exec('sudo /usr/bin/systemctl reload nginx 2>&1');
            $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'vhost_toggle', ?, 'admin', ?)")
                ->execute([$oid, json_encode(['enable' => $enable]), $_SESSION['user_id'] ?? '']);
            echo json_encode(['success' => true, 'enabled' => file_exists($en)]);
            exit;
        }

        // ── SSL 갱신 ──────────────────────────────────────────────
        case 'renew_ssl': {
            $oid = (int)($input['order_id'] ?? 0);
            $oSt = $pdo->prepare("SELECT * FROM {$prefix}orders WHERE id = ?");
            $oSt->execute([$oid]);
            $ord = $oSt->fetch(PDO::FETCH_ASSOC);
            if (!$ord) { echo json_encode(['success' => false, 'message' => 'order not found']); exit; }
            $domain = (string)$ord['domain'];
            $out = shell_exec('sudo /usr/bin/certbot renew --cert-name ' . escapeshellarg($domain) . ' --non-interactive 2>&1');
            $success = $out && (stripos($out, 'success') !== false || stripos($out, 'not yet due') !== false || stripos($out, 'No renewals were attempted') !== false);
            $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'ssl_renew', ?, 'admin', ?)")
                ->execute([$oid, json_encode(['output' => substr((string)$out, 0, 500)]), $_SESSION['user_id'] ?? '']);
            echo json_encode(['success' => $success, 'output' => substr((string)$out, 0, 500)]);
            exit;
        }

        // ── DB 비밀번호 재설정 ─────────────────────────────────────
        case 'reset_db_password': {
            $oid = (int)($input['order_id'] ?? 0);
            $oSt = $pdo->prepare("SELECT * FROM {$prefix}orders WHERE id = ?");
            $oSt->execute([$oid]);
            $ord = $oSt->fetch(PDO::FETCH_ASSOC);
            if (!$ord) { echo json_encode(['success' => false, 'message' => 'order not found']); exit; }
            $orderNumber = $ord['order_number'];
            $username = 'vos_' . preg_replace('/[^A-Za-z0-9]/', '', $orderNumber);
            $newPw = bin2hex(random_bytes(16));

            $dbAdmin = $_ENV['HOSTING_DB_ADMIN_USER'] ?? null;
            $dbAdminPw = $_ENV['HOSTING_DB_ADMIN_PASS'] ?? null;
            if (!$dbAdmin) { echo json_encode(['success' => false, 'message' => 'DB admin 미설정']); exit; }
            try {
                $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', $dbAdmin, $dbAdminPw, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                $st = $admin->prepare("ALTER USER ?@'localhost' IDENTIFIED BY ?");
                $st->execute([$username, $newPw]);
                $admin->exec("FLUSH PRIVILEGES");

                // hosting subscription metadata 갱신
                $hSt = $pdo->prepare("SELECT id, metadata FROM {$prefix}subscriptions WHERE order_id = ? AND type = 'hosting' LIMIT 1");
                $hSt->execute([$oid]);
                if ($hSub = $hSt->fetch(PDO::FETCH_ASSOC)) {
                    $hMeta = json_decode($hSub['metadata'] ?? '{}', true) ?: [];
                    $hMeta['server']['db']['db_pass'] = $newPw;
                    $pdo->prepare("UPDATE {$prefix}subscriptions SET metadata = ? WHERE id = ?")
                        ->execute([json_encode($hMeta, JSON_UNESCAPED_UNICODE), $hSub['id']]);
                }
                $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'db_pw_reset', ?, 'admin', ?)")
                    ->execute([$oid, json_encode(['user' => $username]), $_SESSION['user_id'] ?? '']);
                echo json_encode(['success' => true, 'new_password' => $newPw]);
            } catch (\Throwable $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
        }

        // ── VosCMS 초기화 + 재설치 (DB 초기화 + 파일 재배치 + install 재실행) ─────
        case 'reinstall_voscms': {
            $oid = (int)($input['order_id'] ?? 0);
            $oSt = $pdo->prepare("SELECT * FROM {$prefix}orders WHERE id = ?");
            $oSt->execute([$oid]);
            $ord = $oSt->fetch(PDO::FETCH_ASSOC);
            if (!$ord) { echo json_encode(['success' => false, 'message' => 'order not found']); exit; }

            // install addon 의 install_info 가져오기
            $aSt = $pdo->prepare("SELECT id, metadata FROM {$prefix}subscriptions WHERE order_id = ? AND type = 'addon'");
            $aSt->execute([$oid]);
            $installInfo = null; $installSubId = null;
            while ($r = $aSt->fetch(PDO::FETCH_ASSOC)) {
                $m = json_decode($r['metadata'] ?? '{}', true) ?: [];
                if (($m['addon_id'] ?? '') === 'install' && !empty($m['install_info'])) {
                    $installInfo = $m['install_info']; $installSubId = $r['id']; break;
                }
            }
            if (!$installInfo) { echo json_encode(['success' => false, 'message' => 'install addon 의 install_info 없음']); exit; }

            // hosting sub 의 db 정보
            $hSt = $pdo->prepare("SELECT id, metadata FROM {$prefix}subscriptions WHERE order_id = ? AND type = 'hosting' LIMIT 1");
            $hSt->execute([$oid]);
            $hSub = $hSt->fetch(PDO::FETCH_ASSOC);
            $hMeta = json_decode($hSub['metadata'] ?? '{}', true) ?: [];
            $dbInfo = $hMeta['server']['db'] ?? [];
            if (empty($dbInfo['db_name']) || empty($dbInfo['db_user']) || empty($dbInfo['db_pass'])) {
                echo json_encode(['success' => false, 'message' => 'DB 정보 누락 (server.db)']); exit;
            }

            // 1. DB 초기화 (DROP + CREATE + GRANT)
            $dbAdminUser = $_ENV['HOSTING_DB_ADMIN_USER'] ?? null;
            $dbAdminPass = $_ENV['HOSTING_DB_ADMIN_PASS'] ?? null;
            if (!$dbAdminUser) { echo json_encode(['success' => false, 'message' => 'HOSTING_DB_ADMIN 미설정']); exit; }
            try {
                $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', $dbAdminUser, $dbAdminPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                $dbNameQuoted = '`' . str_replace('`', '``', $dbInfo['db_name']) . '`';
                $admin->exec("DROP DATABASE IF EXISTS {$dbNameQuoted}");
                $admin->exec("CREATE DATABASE {$dbNameQuoted} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $admin->exec("GRANT ALL PRIVILEGES ON {$dbNameQuoted}.* TO " . $admin->quote($dbInfo['db_user']) . "@'localhost'");
                $admin->exec("FLUSH PRIVILEGES");
            } catch (\Throwable $e) {
                echo json_encode(['success' => false, 'message' => 'DB 초기화 실패: ' . $e->getMessage()]); exit;
            }

            // 2. docroot 내용 삭제 후 재생성
            $orderNumber = $ord['order_number'];
            $home = '/var/www/customers/' . $orderNumber;
            $docroot = $home . '/public_html';
            $username = 'vos_' . preg_replace('/[^A-Za-z0-9]/', '', $orderNumber);
            @shell_exec('sudo /usr/bin/rm -rf ' . escapeshellarg($docroot) . ' 2>&1');
            @shell_exec('sudo /usr/bin/mkdir -p ' . escapeshellarg($docroot) . ' 2>&1');
            @shell_exec('sudo /usr/bin/chown -R ' . escapeshellarg($username) . ':www-data ' . escapeshellarg($docroot) . ' 2>&1');
            @shell_exec('sudo /usr/bin/chmod 2775 ' . escapeshellarg($docroot) . ' 2>&1');

            // 3. installVoscms 재호출
            try {
                $prov = new \RzxLib\Core\Hosting\HostingProvisioner($pdo);
                $r = $prov->installVoscms($orderNumber, (string)$ord['domain'], $dbInfo, $installInfo);
                if (!empty($r['success'])) {
                    // install addon metadata 갱신
                    $aMeta = json_decode($pdo->query("SELECT metadata FROM {$prefix}subscriptions WHERE id = " . (int)$installSubId)->fetchColumn() ?: '{}', true) ?: [];
                    $aMeta['install_completed_at'] = $r['installed_at'] ?? date('c');
                    $aMeta['install_admin_url']    = $r['admin_url'] ?? null;
                    $aMeta['reinstalled_at']       = date('c');
                    $pdo->prepare("UPDATE {$prefix}subscriptions SET metadata = ? WHERE id = ?")
                        ->execute([json_encode($aMeta, JSON_UNESCAPED_UNICODE), $installSubId]);
                    $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'voscms_reinstalled', ?, 'admin', ?)")
                        ->execute([$oid, json_encode($r, JSON_UNESCAPED_UNICODE), $_SESSION['user_id'] ?? '']);
                    echo json_encode(['success' => true, 'message' => 'VosCMS 재설치 완료', 'admin_url' => $r['admin_url'] ?? null, 'version' => $r['version'] ?? null]);
                } else {
                    echo json_encode(['success' => false, 'message' => '재설치 실패: ' . ($r['error'] ?? json_encode($r, JSON_UNESCAPED_UNICODE))]);
                }
            } catch (\Throwable $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
        }

        // ── 재프로비저닝 (호스팅 손상 시 재구축) ──────────────────────
        case 'reprovision': {
            $oid = (int)($input['order_id'] ?? 0);
            $oSt = $pdo->prepare("SELECT * FROM {$prefix}orders WHERE id = ?");
            $oSt->execute([$oid]);
            $ord = $oSt->fetch(PDO::FETCH_ASSOC);
            if (!$ord) { echo json_encode(['success' => false, 'message' => 'order not found']); exit; }
            $orderNumber = $ord['order_number'];
            $_runScript = BASE_PATH . '/scripts/run-order-provision.php';
            if (!is_file($_runScript)) { echo json_encode(['success' => false, 'message' => 'runner 스크립트 없음']); exit; }
            $_logFile = '/tmp/voscms-provision-' . preg_replace('/[^A-Za-z0-9_-]/', '', $orderNumber) . '.log';
            $_cmd = sprintf(
                '/usr/bin/php8.3 %s --order=%s --force > %s 2>&1 &',
                escapeshellarg($_runScript),
                escapeshellarg($orderNumber),
                escapeshellarg($_logFile)
            );
            @exec($_cmd);
            $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'reprovision_triggered', '{}', 'admin', ?)")
                ->execute([$oid, $_SESSION['user_id'] ?? '']);
            echo json_encode(['success' => true, 'message' => '백그라운드에서 재프로비저닝 중. 잠시 후 새로고침하세요.']);
            exit;
        }
    }
    echo json_encode(['success' => false, 'message' => __('services.admin_orders.alert_unknown_action')]);
    exit;
}

require_once BASE_PATH . '/rzxlib/Core/Helpers/Encryption.php';
require_once BASE_PATH . '/rzxlib/Core/Helpers/functions.php';

// 주문 로드
$orderStmt = $pdo->prepare("SELECT o.*, u.name as user_name, u.email as user_email, u.role as user_role FROM {$prefix}orders o LEFT JOIN {$prefix}users u ON o.user_id = u.id WHERE o.order_number = ?");
$orderStmt->execute([$adminOrderNumber ?? '']);
$order = $orderStmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    $pageTitle = __('services.detail.not_found_title'); $pageHeaderTitle = __('services.admin_orders.header_title');
    include BASE_PATH . '/resources/views/admin/reservations/_head.php';
    echo '<div class="text-center py-16"><p class="text-zinc-400">' . htmlspecialchars(__('services.detail.not_found')) . '</p><a href="'.$adminUrl.'/service-orders" class="text-blue-600 hover:underline text-sm mt-4 inline-block">' . htmlspecialchars(__('services.admin_orders.back_to_list')) . '</a></div>';
    include BASE_PATH . '/resources/views/admin/reservations/_foot.php';
    return;
}

// 서비스 설정 로드 (라벨 → _id 매핑용)
$_svcSettings = [];
try {
    $_sStmt = $pdo->prepare("SELECT `key`, `value` FROM {$prefix}settings WHERE `key` IN ('service_addons','service_maintenance','service_hosting_plans')");
    $_sStmt->execute();
    while ($_sr = $_sStmt->fetch(PDO::FETCH_ASSOC)) $_svcSettings[$_sr['key']] = $_sr['value'];
} catch (\Throwable $e) {}

$_addonIdByLabel = [];
foreach ((json_decode($_svcSettings['service_addons'] ?? '[]', true) ?: []) as $_a) {
    if (!empty($_a['_id']) && !empty($_a['label'])) $_addonIdByLabel[$_a['label']] = $_a['_id'];
}
$_maintIdByLabel = [];
foreach ((json_decode($_svcSettings['service_maintenance'] ?? '[]', true) ?: []) as $_m) {
    if (!empty($_m['_id']) && !empty($_m['label'])) $_maintIdByLabel[$_m['label']] = $_m['_id'];
}
$_hostingPlansData = json_decode($_svcSettings['service_hosting_plans'] ?? '[]', true) ?: [];

// 라벨 다국어 변환 헬퍼
$_localizeLabel = function($sub) use ($_addonIdByLabel, $_maintIdByLabel, $_hostingPlansData, $order) {
    $label = $sub['label'] ?? '';
    $type = $sub['type'] ?? '';
    if ($type === 'hosting') {
        $meta = json_decode($sub['metadata'] ?? '{}', true) ?: [];
        $cap = $meta['capacity'] ?? ($order['hosting_capacity'] ?? '');
        $planLabelDisplay = '';
        foreach ($_hostingPlansData as $p) {
            if (($p['capacity'] ?? '') === $cap && !empty($p['_id'])) {
                $planLabelDisplay = db_trans("service.hosting.plan.{$p['_id']}.label", null, $p['label'] ?? '');
                break;
            }
        }
        $prefix = rtrim(__('services.order.summary.hosting_label_prefix'));
        $parts = array_filter([$prefix, $planLabelDisplay, $cap], fn($v) => $v !== '' && $v !== null);
        return implode(' ', $parts);
    }
    if ($type === 'addon' && isset($_addonIdByLabel[$label])) {
        return db_trans("service.addon.{$_addonIdByLabel[$label]}.label", null, $label);
    }
    if ($type === 'maintenance') {
        $stripped = preg_replace('/^유지보수\s*/u', '', $label);
        $matchedId = $_maintIdByLabel[$stripped] ?? $_maintIdByLabel[$label] ?? null;
        $name = $matchedId ? db_trans("service.maintenance.{$matchedId}.label", null, $stripped) : $stripped;
        return __('services.order.summary.maint_label_prefix') . $name;
    }
    return $label;
};

// 구독 목록
$subsStmt = $pdo->prepare("SELECT * FROM {$prefix}subscriptions WHERE order_id = ? ORDER BY FIELD(type,'hosting','domain','mail','maintenance','addon'), id");
$subsStmt->execute([$order['id']]);
$subscriptions = $subsStmt->fetchAll(PDO::FETCH_ASSOC);

// 주문 로그 — 초기 50건. 추가는 'load_more_logs' 액션으로 점진 로드
$_logPageSize = 50;
$logsStmt = $pdo->prepare("SELECT * FROM {$prefix}order_logs WHERE order_id = ? ORDER BY id DESC LIMIT {$_logPageSize}");
$logsStmt->execute([$order['id']]);
$orderLogs = $logsStmt->fetchAll(PDO::FETCH_ASSOC);
$_logTotalStmt = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}order_logs WHERE order_id = ?");
$_logTotalStmt->execute([$order['id']]);
$_logTotal = (int)$_logTotalStmt->fetchColumn();

// 타입별 그룹
$servicesByType = [];
foreach ($subscriptions as $sub) {
    $servicesByType[$sub['type']][] = $sub;
}
// 항상 4개 탭 표시 (hosting / domain / mail / addon) — 데이터 없어도 빈 상태로 노출
$_allTabTypes = ['hosting', 'domain', 'mail', 'addon'];
$tabTypes = $_allTabTypes;
$firstTab = $tabTypes[0];

$pageTitle = $order['order_number'] . ' - ' . __('services.admin_orders.page_title');
$pageHeaderTitle = __('services.admin_orders.header_title');
$pageSubTitle = '';

$_dispSymbols = ['KRW'=>'₩','USD'=>'$','JPY'=>'¥','CNY'=>'¥','EUR'=>'€'];
$fmtPrice = function($amount, $currency = 'JPY') use ($_dispSymbols) {
    $sym = $_dispSymbols[$currency] ?? $currency;
    $pre = in_array($currency, ['USD','JPY','CNY','EUR']);
    return $pre ? $sym . number_format((int)$amount) : number_format((int)$amount) . $sym;
};

$statusLabels = [
    'pending' => [__('services.mypage.status_pending'), 'bg-blue-100 text-blue-700'],
    'paid' => [__('services.detail.s_paid'), 'bg-green-100 text-green-700'],
    'active' => [__('services.mypage.status_active'), 'bg-green-100 text-green-700'],
    'expired' => [__('services.mypage.status_expired'), 'bg-gray-100 text-gray-500'],
    'cancelled' => [__('services.mypage.status_cancelled'), 'bg-red-100 text-red-600'],
    'suspended' => [__('services.mypage.status_suspended'), 'bg-amber-100 text-amber-700'],
    'failed' => [__('services.detail.s_failed'), 'bg-red-100 text-red-600'],
];
$typeLabels = [
    'hosting' => __('services.mypage.type_hosting'),
    'domain' => __('services.mypage.type_domain'),
    'mail' => __('services.mypage.type_mail'),
    'maintenance' => __('services.mypage.type_maintenance'),
    'addon' => __('services.mypage.type_addon'),
];
$typeIcons = [
    'hosting' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>',
    'domain' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>',
    'maintenance' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>',
    'mail' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>',
    'addon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>',
];
$ost = $statusLabels[$order['status']] ?? [__('services.mypage.status_unknown'), 'bg-gray-100 text-gray-500'];

$oneTimeStatusOptions = [
    'pending' => [__('services.detail.ot_pending'), 'blue'],
    'active' => [__('services.detail.ot_active'), 'amber'],
    'suspended' => [__('services.detail.ot_suspended'), 'zinc'],
    'cancelled' => [__('services.detail.ot_cancelled'), 'red'],
    'completed' => [__('services.detail.ot_completed'), 'green'],
];

include BASE_PATH . '/resources/views/admin/reservations/_head.php';
?>

<!-- 헤더 -->
<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3">
        <a href="<?= $adminUrl ?>/service-orders" class="text-zinc-400 hover:text-zinc-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-lg font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars($order['order_number']) ?></h1>
            <p class="text-xs text-zinc-400"><?= htmlspecialchars($order['domain'] ?: '-') ?> · <?php
                $_isSysOrder = in_array($order['user_role'] ?? '', ['supervisor', 'admin'], true);
                if ($_isSysOrder) {
                    $_sysName = decrypt($order['user_name'] ?: '') ?: '-';
                    $_sysRoleLbl = $order['user_role'] === 'supervisor' ? __('services.admin_orders.role_supervisor') : __('services.admin_orders.role_admin');
                    $_sysEmail = decrypt($order['user_email'] ?: '') ?: '-';
                    echo htmlspecialchars($_sysName . '(' . $_sysRoleLbl . ': ' . $_sysEmail . ')');
                } else {
                    echo htmlspecialchars(decrypt($order['applicant_name'] ?: '') ?: decrypt($order['user_name'] ?: '') ?: '-');
                }
            ?></p>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <span class="text-[10px] px-2 py-0.5 rounded-full font-medium <?= $ost[1] ?>"><?= htmlspecialchars($ost[0]) ?></span>
        <select id="orderStatusSelect" onchange="updateOrderStatus(this.value)"
                class="text-xs border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg px-2 py-1">
            <?php foreach (['pending','paid','active','expired','cancelled','failed'] as $sv): ?>
            <option value="<?= $sv ?>" <?= $order['status'] === $sv ? 'selected' : '' ?>><?= htmlspecialchars($statusLabels[$sv][0] ?? $sv) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
    <!-- 좌측: 주문 정보 + 구독 (4/5) -->
    <div class="lg:col-span-4 space-y-6">

        <!-- 주문 정보 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 dark:border-zinc-700">
                <h2 class="text-sm font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars(__('services.detail.order_info')) ?> : <?= htmlspecialchars($order['domain'] ?: '-') ?></h2>
            </div>
            <div class="p-5">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                    <div>
                        <p class="text-[10px] text-zinc-400 uppercase mb-0.5"><?= htmlspecialchars(__('services.detail.f_contract_period')) ?></p>
                        <p class="font-medium text-zinc-900 dark:text-white"><?= $order['contract_months'] ?><?= htmlspecialchars(__('services.order.hosting.unit_month')) ?></p>
                        <p class="text-xs text-zinc-400"><?= $order['started_at'] ? date('Y-m-d', strtotime($order['started_at'])) : '-' ?> ~ <?= $order['expires_at'] ? date('Y-m-d', strtotime($order['expires_at'])) : '-' ?></p>
                    </div>
                    <div>
                        <p class="text-[10px] text-zinc-400 uppercase mb-0.5"><?= htmlspecialchars(__('services.admin_orders.f_payment')) ?></p>
                        <p class="font-medium text-zinc-900 dark:text-white"><?= (int)$order['total'] > 0 ? $fmtPrice($order['total'], $order['currency']) : __('services.order.summary.free') ?></p>
                        <p class="text-xs text-zinc-400"><?= $order['payment_method'] === 'free' ? __('services.order.summary.free') : ($order['payment_method'] === 'bank' ? __('services.order.payment.method_bank') : __('services.detail.pay_card')) ?></p>
                    </div>
                    <div>
                        <p class="text-[10px] text-zinc-400 uppercase mb-0.5"><?= htmlspecialchars(__('services.admin_orders.f_applicant')) ?></p>
                        <?php if ($_isSysOrder ?? false): ?>
                        <p class="font-medium text-zinc-900 dark:text-white"><?= htmlspecialchars(decrypt($order['user_name'] ?: '') ?: '-') ?></p>
                        <p class="text-xs text-violet-500 dark:text-violet-400"><?= htmlspecialchars(($order['user_role'] === 'supervisor' ? __('services.admin_orders.role_supervisor') : __('services.admin_orders.role_admin')) . ': ' . (decrypt($order['user_email'] ?: '') ?: '-')) ?></p>
                        <?php else: ?>
                        <p class="font-medium text-zinc-900 dark:text-white"><?= htmlspecialchars(decrypt($order['applicant_name'] ?: '') ?: '-') ?></p>
                        <p class="text-xs text-zinc-400"><?= htmlspecialchars($order['applicant_email'] ?: '') ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <p class="text-[10px] text-zinc-400 uppercase mb-0.5"><?= htmlspecialchars(__('services.order.applicant.phone_label')) ?></p>
                        <p class="font-medium text-zinc-900 dark:text-white"><?php
                            $phoneDisplayConfig = ['value' => $order['applicant_phone'] ?? '', 'id' => 'adminPhone'];
                            include BASE_PATH . '/resources/views/components/phone-display.php';
                        ?></p>
                        <p class="text-xs text-zinc-400"><?= htmlspecialchars($order['applicant_company'] ?: '') ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 서비스 탭 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 overflow-hidden">
            <div class="border-b border-gray-200 dark:border-zinc-700">
                <nav class="flex gap-1 px-4 -mb-px overflow-x-auto">
                    <?php foreach ($tabTypes as $i => $type): ?>
                    <?php
                        if ($type === 'mail') {
                            $_cnt = 0;
                            foreach ($servicesByType[$type] ?? [] as $_ms) {
                                $_mm = json_decode($_ms['metadata'] ?? '{}', true) ?: [];
                                $_cnt += count($_mm['mail_accounts'] ?? []);
                            }
                        } else {
                            $_cnt = count($servicesByType[$type] ?? []);
                        }
                    ?>
                    <button onclick="showTab('<?= $type ?>')" id="atab_<?= $type ?>"
                            class="admin-tab flex items-center gap-1.5 px-4 py-2.5 text-sm font-medium border-b-2 whitespace-nowrap transition <?= $i === 0 ? 'border-blue-600 text-blue-600' : 'border-transparent text-zinc-400 hover:text-zinc-600' ?>">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><?= $typeIcons[$type] ?? $typeIcons['addon'] ?></svg>
                        <?= htmlspecialchars($typeLabels[$type] ?? $type) ?>
                        <span class="text-[10px] px-1.5 py-0.5 <?= $_cnt > 0 ? 'bg-zinc-100 dark:bg-zinc-700 text-zinc-500' : 'bg-zinc-50 dark:bg-zinc-800 text-zinc-300' ?> rounded-full"><?= $_cnt ?></span>
                    </button>
                    <?php endforeach; ?>
                </nav>
            </div>

            <?php foreach ($tabTypes as $type): ?>
            <div id="apanel_<?= $type ?>" class="admin-panel <?= $type !== $firstTab ? 'hidden' : '' ?>">
                <?php
                $subs = $servicesByType[$type] ?? [];
                $partialFile = __DIR__ . '/partials/' . $type . '.php';
                if (file_exists($partialFile)) {
                    include $partialFile;
                } else {
                    include __DIR__ . '/partials/_generic.php';
                }
                ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- 우측: 주문 메모 + 활동 로그 -->
    <div class="space-y-4">
        <!-- 주문 관리자 메모 (항상 표시) -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-zinc-700 flex items-center justify-between">
                <h2 class="text-sm font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars(__('services.admin_orders.admin_memo')) ?></h2>
                <button onclick="saveOrderMemo()" class="px-3 py-1 text-[10px] font-medium text-blue-600 border border-blue-200 rounded-lg hover:bg-blue-50"><?= htmlspecialchars(__('services.admin_orders.btn_save')) ?></button>
            </div>
            <div class="p-4">
                <textarea id="orderMemo" rows="7" placeholder="<?= htmlspecialchars(__('services.admin_orders.memo_placeholder')) ?>"
                    class="w-full px-3 py-2 text-xs border border-zinc-200 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg resize-none"><?= htmlspecialchars($order['admin_notes'] ?? '') ?></textarea>
            </div>
        </div>

        <!-- 활동 로그 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-zinc-700 flex items-center justify-between">
                <h2 class="text-sm font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars(__('services.admin_orders.activity_log')) ?></h2>
                <span class="text-[11px] text-zinc-400" id="logCounter">
                    <?= count($orderLogs) ?> / <?= $_logTotal ?>
                </span>
            </div>
            <div id="activityLogList" class="p-4 space-y-3 max-h-[900px] overflow-y-auto">
                <?php if (empty($orderLogs)): ?>
                <p class="text-xs text-zinc-400 text-center py-4"><?= htmlspecialchars(__('services.admin_orders.empty_logs')) ?></p>
                <?php endif; ?>
                <?php foreach ($orderLogs as $log):
                    $actionLabels = [
                        'created' => [__('services.admin_orders.act_created'), 'blue'],
                        'paid' => [__('services.admin_orders.act_paid'), 'green'],
                        'bank_pending' => [__('services.admin_orders.act_bank_pending'), 'amber'],
                        'failed' => [__('services.admin_orders.act_failed'), 'red'],
                        'status_change' => [__('services.admin_orders.act_status_change'), 'purple'],
                        'renewal_request' => [__('services.admin_orders.act_renewal_request'), 'blue'],
                        'service_completed' => [__('services.admin_orders.act_service_completed'), 'green'],
                        'mail_password_changed' => [__('services.admin_orders.act_mail_password_changed'), 'zinc'],
                        'mail_account_added' => [__('services.admin_orders.act_mail_account_added'), 'green'],
                        'mail_account_deleted' => [__('services.admin_orders.act_mail_account_deleted'), 'red'],
                        'mail_address_changed' => [__('services.admin_orders.act_mail_address_changed'), 'amber'],
                        'mail_provisioned' => [__('services.admin_orders.act_mail_provisioned'), 'green'],
                        'mail_provision_skipped' => [__('services.admin_orders.act_mail_provision_skipped'), 'zinc'],
                        'mail_provision_failed' => [__('services.admin_orders.act_mail_provision_failed'), 'red'],
                        'mail_domain_migrated' => [__('services.admin_orders.act_mail_domain_migrated'), 'green'],
                        'mail_domain_activated' => [__('services.admin_orders.act_mail_domain_activated'), 'green'],
                        'bizmail_upgrade_request' => [__('services.admin_orders.act_bizmail_upgrade_request'), 'amber'],
                        'customer_notified' => [__('services.admin_orders.act_customer_notified'), 'blue'],
                        'admin_add_storage_addon' => [__('services.admin_orders.act_admin_add_storage_addon'), 'violet'],
                        'storage_addon_paid' => [__('services.admin_orders.act_storage_addon_paid'), 'green'],
                        'storage_addon_requested' => [__('services.admin_orders.act_storage_addon_requested'), 'blue'],
                        'setup_email_sent' => [__('services.admin_orders.act_setup_email_sent'), 'blue'],
                        'server_info_updated' => [__('services.admin_orders.act_server_info_updated'), 'zinc'],
                        'bulk_extended' => [__('services.admin_orders.act_bulk_extended'), 'blue'],
                        'hosting_provisioned' => [__('services.admin_orders.act_hosting_provisioned'), 'green'],
                        'hosting_provision_skipped' => [__('services.admin_orders.act_hosting_provision_skipped'), 'zinc'],
                        'hosting_provision_deferred' => [__('services.admin_orders.act_hosting_provision_deferred'), 'amber'],
                        'hosting_provision_failed' => [__('services.admin_orders.act_hosting_provision_failed'), 'red'],
                        'hosting_deprovisioned' => [__('services.admin_orders.act_hosting_deprovisioned'), 'red'],
                        'manual_capacity_period_fix' => [__('services.admin_orders.act_manual_capacity_period_fix'), 'violet'],
                        'domain_added' => [__('services.admin_orders.act_domain_added'), 'green'],
                        'domain_added_payment' => [__('services.admin_orders.act_domain_added_payment'), 'green'],
                        'domain_admin_completed' => [__('services.admin_orders.act_domain_admin_completed'), 'green'],
                        'domain_admin_cancelled' => [__('services.admin_orders.act_domain_admin_cancelled'), 'red'],
                        'sub_migrated_to_added_domain' => [__('services.admin_orders.act_sub_migrated_to_added_domain'), 'violet'],
                        'addon_admin_cancelled' => [__('services.admin_orders.act_addon_admin_cancelled'), 'red'],
                        'addon_paid_added' => [__('services.admin_orders.act_addon_paid_added'), 'green'],
                        'addon_request_pending' => [__('services.admin_orders.act_addon_request_pending'), 'amber'],
                        // 신규 액션 (관리자 대리 등록 + 호스팅 운영)
                        'admin_created' => [__('services.admin_orders.act_admin_created'), 'blue'],
                        'admin_delete_addon' => [__('services.admin_orders.act_admin_delete_addon'), 'red'],
                        'voscms_installed' => [__('services.admin_orders.act_voscms_installed'), 'green'],
                        'vhost_toggle' => [__('services.admin_orders.act_vhost_toggle'), 'amber'],
                        'ssl_renew' => [__('services.admin_orders.act_ssl_renew'), 'emerald'],
                        'db_pw_reset' => [__('services.admin_orders.act_db_pw_reset'), 'amber'],
                        'reprovision_triggered' => [__('services.admin_orders.act_reprovision_triggered'), 'violet'],
                    ];
                    $al = $actionLabels[$log['action']] ?? [$log['action'], 'zinc'];
                    $detail = json_decode($log['detail'] ?? '{}', true) ?: [];
                ?>
                <div class="flex gap-3">
                    <div class="w-2 h-2 rounded-full bg-<?= $al[1] ?>-400 mt-1.5 shrink-0"></div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars($al[0]) ?></p>
                        <?php if (!empty($detail['label'])): ?>
                        <p class="text-[10px] text-zinc-400"><?= htmlspecialchars($detail['label'] ?? '') ?></p>
                        <?php endif; ?>
                        <?php if (!empty($detail['new_status'])): ?>
                        <p class="text-[10px] text-zinc-400">→ <?= htmlspecialchars($detail['new_status']) ?></p>
                        <?php endif; ?>
                        <p class="text-[10px] text-zinc-300 dark:text-zinc-600"><?= date('m-d H:i', strtotime($log['created_at'])) ?> · <?= $log['actor_type'] ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if ($_logTotal > count($orderLogs)): ?>
            <div class="px-4 py-3 border-t border-gray-100 dark:border-zinc-700 text-center">
                <button type="button" id="btnLoadMoreLogs" onclick="loadMoreLogs()" class="text-xs text-blue-600 hover:underline">
                    이전 로그 더 보기 (<?= $_logTotal - count($orderLogs) ?>건 남음)
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
var adminUrl = '<?= $adminUrl ?>';
var orderId = <?= (int)$order['id'] ?>;
var _logLastId = <?= !empty($orderLogs) ? (int)end($orderLogs)['id'] : 0 ?>;
var _logTotal  = <?= (int)$_logTotal ?>;
var _logLoaded = <?= (int)count($orderLogs) ?>;

function ajaxPost(data) {
    return fetch(window.location.href, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify(data)
    }).then(function(r) { return r.json(); });
}

// 활동 로그 — 이전 로그 더 보기 (페이지네이션)
function loadMoreLogs() {
    var btn = document.getElementById('btnLoadMoreLogs');
    if (!btn) return;
    btn.disabled = true; btn.textContent = '불러오는 중…';
    ajaxPost({ action: 'load_more_logs', order_id: orderId, before_id: _logLastId, limit: 50 }).then(function(d) {
        if (!d.success || !d.logs || !d.logs.length) {
            btn.parentElement.remove();
            return;
        }
        var list = document.getElementById('activityLogList');
        d.logs.forEach(function(log) {
            var div = document.createElement('div');
            div.className = 'flex gap-3';
            var color = log.color || 'zinc';
            var label = log.label || log.action;
            div.innerHTML =
                '<div class="w-2 h-2 rounded-full bg-' + color + '-400 mt-1.5 shrink-0"></div>' +
                '<div class="flex-1 min-w-0">' +
                '<p class="text-xs font-medium text-zinc-700 dark:text-zinc-300">' + escapeHtml(label) + '</p>' +
                '<p class="text-[10px] text-zinc-300 dark:text-zinc-600">' +
                formatLogDate(log.created_at) + ' · ' + escapeHtml(log.actor_type || '') +
                '</p></div>';
            list.appendChild(div);
            _logLastId = log.id;
            _logLoaded++;
        });
        // 카운터 갱신
        var counter = document.getElementById('logCounter');
        if (counter) counter.textContent = _logLoaded + ' / ' + (d.total || _logTotal);
        // 더 남았으면 버튼 라벨 갱신, 아니면 제거
        var remaining = (d.total || _logTotal) - _logLoaded;
        if (remaining > 0) {
            btn.disabled = false;
            btn.textContent = '이전 로그 더 보기 (' + remaining + '건 남음)';
        } else {
            btn.parentElement.remove();
        }
    }).catch(function() {
        btn.disabled = false;
        btn.textContent = '재시도';
    });
}
function escapeHtml(s) { return String(s ?? '').replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
function formatLogDate(s) {
    if (!s) return '';
    var d = new Date(String(s).replace(' ', 'T'));
    if (isNaN(d.getTime())) return s;
    var pad = function(n){return n<10?'0'+n:n;};
    return pad(d.getMonth()+1) + '-' + pad(d.getDate()) + ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes());
}

function showTab(type) {
    document.querySelectorAll('.admin-tab').forEach(function(t) {
        t.classList.remove('border-blue-600', 'text-blue-600');
        t.classList.add('border-transparent', 'text-zinc-400');
    });
    document.querySelectorAll('.admin-panel').forEach(function(p) { p.classList.add('hidden'); });
    var tab = document.getElementById('atab_' + type);
    var panel = document.getElementById('apanel_' + type);
    if (tab) { tab.classList.add('border-blue-600', 'text-blue-600'); tab.classList.remove('border-transparent', 'text-zinc-400'); }
    if (panel) panel.classList.remove('hidden');
}

// 1회성 상태 변경 모달
var _otSubId = 0;
var _otStatuses = [
    { value: 'pending',   label: <?= json_encode(__('services.detail.ot_pending'),   JSON_UNESCAPED_UNICODE) ?>, color: 'blue',  desc: <?= json_encode(__('services.admin_orders.ot_pending_desc'),   JSON_UNESCAPED_UNICODE) ?> },
    { value: 'active',    label: <?= json_encode(__('services.detail.ot_active'),    JSON_UNESCAPED_UNICODE) ?>, color: 'amber', desc: <?= json_encode(__('services.admin_orders.ot_active_desc'),    JSON_UNESCAPED_UNICODE) ?> },
    { value: 'suspended', label: <?= json_encode(__('services.detail.ot_suspended'), JSON_UNESCAPED_UNICODE) ?>, color: 'zinc',  desc: <?= json_encode(__('services.admin_orders.ot_suspended_desc'), JSON_UNESCAPED_UNICODE) ?> },
    { value: 'cancelled', label: <?= json_encode(__('services.detail.ot_cancelled'), JSON_UNESCAPED_UNICODE) ?>, color: 'red',   desc: <?= json_encode(__('services.admin_orders.ot_cancelled_desc'), JSON_UNESCAPED_UNICODE) ?> },
    { value: 'completed', label: <?= json_encode(__('services.detail.ot_completed'), JSON_UNESCAPED_UNICODE) ?>, color: 'green', desc: <?= json_encode(__('services.admin_orders.ot_completed_desc'), JSON_UNESCAPED_UNICODE) ?> }
];

function openStatusModal(subId, current, label) {
    _otSubId = subId;
    var html = '<div class="text-center mb-5"><p class="text-sm font-bold text-zinc-900 dark:text-white">' + label + '</p><p class="text-xs text-zinc-400 mt-1">' + <?= json_encode(__('services.admin_orders.modal_select_status'), JSON_UNESCAPED_UNICODE) ?> + '</p></div>'
        + '<div class="grid grid-cols-5 gap-2">';
    _otStatuses.forEach(function(s) {
        var isActive = s.value === current;
        html += '<button onclick="confirmStatusChange(\'' + s.value + '\')" class="flex flex-col items-center gap-1.5 p-3 rounded-xl border-2 transition '
            + (isActive ? 'border-' + s.color + '-500 bg-' + s.color + '-50 dark:bg-' + s.color + '-900/20' : 'border-gray-200 dark:border-zinc-600 hover:border-' + s.color + '-300')
            + '">'
            + '<span class="w-8 h-8 rounded-full bg-' + s.color + '-100 dark:bg-' + s.color + '-900/30 flex items-center justify-center">'
            + '<span class="w-3 h-3 rounded-full bg-' + s.color + '-500"></span></span>'
            + '<span class="text-xs font-medium ' + (isActive ? 'text-' + s.color + '-600' : 'text-zinc-600 dark:text-zinc-300') + '">' + s.label + '</span>'
            + '</button>';
    });
    html += '</div>';
    document.getElementById('statusModalBody').innerHTML = html;
    document.getElementById('statusModal').classList.remove('hidden');
}

function confirmStatusChange(status) {
    ajaxPost({ action: 'update_onetime_status', subscription_id: _otSubId, status: status })
        .then(function(d) {
            if (d.success) location.reload();
            else alert(d.message || <?= json_encode(__('services.admin_orders.alert_change_failed'), JSON_UNESCAPED_UNICODE) ?>);
        });
}

function closeStatusModal() {
    document.getElementById('statusModal').classList.add('hidden');
}

function updateOrderStatus(status) {
    ajaxPost({ action: 'update_order_status', order_id: orderId, status: status })
        .then(function(d) {
            if (d.success) location.reload();
            else alert(d.message || <?= json_encode(__('services.admin_orders.alert_change_failed'), JSON_UNESCAPED_UNICODE) ?>);
        });
}

function saveOrderMemo() {
    var memo = document.getElementById('orderMemo').value;
    ajaxPost({ action: 'update_order_memo', order_id: orderId, memo: memo })
        .then(function(d) {
            if (d.success) alert(<?= json_encode(__('services.admin_orders.alert_saved'), JSON_UNESCAPED_UNICODE) ?>);
            else alert(d.message || <?= json_encode(__('services.admin_orders.alert_change_failed'), JSON_UNESCAPED_UNICODE) ?>);
        });
}
</script>

<!-- 상태 변경 모달 -->
<div id="statusModal" class="hidden fixed inset-0 z-50 flex items-center justify-center">
    <div class="absolute inset-0 bg-black/50" onclick="closeStatusModal()"></div>
    <div class="relative bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl p-6 w-full max-w-md mx-4">
        <button onclick="closeStatusModal()" class="absolute top-3 right-3 text-zinc-400 hover:text-zinc-600 p-1">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <div id="statusModalBody"></div>
    </div>
</div>

<?php include BASE_PATH . '/resources/views/admin/reservations/_foot.php'; ?>
