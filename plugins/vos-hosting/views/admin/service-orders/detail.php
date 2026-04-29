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
            $meta['server'] = $serverData;
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

        case 'admin_delete_addon':
            $aSt = $pdo->prepare("SELECT * FROM {$prefix}subscriptions WHERE id = ? AND type = 'addon'");
            $aSt->execute([$subId]);
            $aSub = $aSt->fetch(PDO::FETCH_ASSOC);
            if (!$aSub) { echo json_encode(['success' => false, 'message' => __('services.admin_orders.alert_sub_not_found')]); exit; }

            $aMeta = json_decode($aSub['metadata'] ?? '{}', true) ?: [];
            $parentHostId = (int)($aMeta['parent_hosting_sub_id'] ?? 0);
            try {
                $pdo->beginTransaction();
                $pdo->prepare("UPDATE {$prefix}subscriptions SET status = 'cancelled', auto_renew = 0 WHERE id = ?")
                    ->execute([$subId]);
                if ($parentHostId) {
                    $pSt = $pdo->prepare("SELECT metadata FROM {$prefix}subscriptions WHERE id = ?");
                    $pSt->execute([$parentHostId]);
                    $pRow = $pSt->fetch(PDO::FETCH_ASSOC);
                    if ($pRow) {
                        $pMeta = json_decode($pRow['metadata'] ?? '{}', true) ?: [];
                        if (!empty($pMeta['extra_storage'])) {
                            $pMeta['extra_storage'] = array_values(array_filter($pMeta['extra_storage'], function($e) use ($subId) {
                                return (int)($e['addon_sub_id'] ?? 0) !== $subId;
                            }));
                            $pdo->prepare("UPDATE {$prefix}subscriptions SET metadata = ? WHERE id = ?")
                                ->execute([json_encode($pMeta, JSON_UNESCAPED_UNICODE), $parentHostId]);
                        }
                    }
                }
                $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'admin_delete_addon', ?, 'admin', ?)")
                    ->execute([$aSub['order_id'], json_encode(['subscription_id' => $subId, 'label' => $aSub['label']], JSON_UNESCAPED_UNICODE), $_SESSION['user_id'] ?? '']);
                $pdo->commit();
            } catch (\Throwable $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]); exit;
            }
            echo json_encode(['success' => true, 'message' => __('services.admin_orders.alert_addon_deleted')]);
            exit;
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

// 주문 로그
$logsStmt = $pdo->prepare("SELECT * FROM {$prefix}order_logs WHERE order_id = ? ORDER BY created_at DESC LIMIT 30");
$logsStmt->execute([$order['id']]);
$orderLogs = $logsStmt->fetchAll(PDO::FETCH_ASSOC);

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
            <div class="px-5 py-4 border-b border-gray-100 dark:border-zinc-700">
                <h2 class="text-sm font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars(__('services.admin_orders.activity_log')) ?></h2>
            </div>
            <div class="p-4 space-y-3 max-h-[600px] overflow-y-auto">
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
                        'admin_delete_addon' => [__('services.admin_orders.act_admin_delete_addon'), 'red'],
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
        </div>
    </div>
</div>

<script>
var adminUrl = '<?= $adminUrl ?>';
var orderId = <?= (int)$order['id'] ?>;

function ajaxPost(data) {
    return fetch(window.location.href, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify(data)
    }).then(function(r) { return r.json(); });
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
