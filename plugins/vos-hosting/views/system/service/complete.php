<?php
/**
 * 서비스 주문 완료 페이지
 */
require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
$isLoggedIn = \RzxLib\Core\Auth\Auth::check();
$baseUrl = rtrim($config['app_url'] ?? '', '/');

$orderNumber = $_GET['order'] ?? '';
$order = null;

if ($orderNumber) {
    try {
        $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
        $stmt = $pdo->prepare("SELECT * FROM {$prefix}orders WHERE order_number = ? LIMIT 1");
        $stmt->execute([$orderNumber]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (\Throwable $e) {}
}

$_dispSymbols = ['KRW'=>'₩','USD'=>'$','JPY'=>'¥','CNY'=>'¥','EUR'=>'€'];
$cur = $order['currency'] ?? 'JPY';
$sym = $_dispSymbols[$cur] ?? $cur;
$pre = in_array($cur, ['USD','JPY','CNY','EUR']);
$fmt = function($amount) use ($sym, $pre) {
    $f = number_format((int)$amount);
    return $pre ? $sym . $f : $f . $sym;
};

// plugin lang (services) 로드
$_svcLangFile = BASE_PATH . '/plugins/vos-hosting/lang/' . \RzxLib\Core\I18n\Translator::getLocale() . '/services.php';
if (!file_exists($_svcLangFile)) {
    $_svcLangFile = BASE_PATH . '/plugins/vos-hosting/lang/en/services.php';
}
if (file_exists($_svcLangFile)) {
    \RzxLib\Core\I18n\Translator::merge('services', require $_svcLangFile);
}

// 호스팅 플랜 라벨 다국어 매핑 (DB에는 원본 라벨 저장됨 → _id 찾아서 db_trans)
$_orderHostingLabel = $order['hosting_plan'] ?? '';
if ($order && $_orderHostingLabel) {
    try {
        $_pStmt = $pdo->prepare("SELECT `value` FROM {$prefix}settings WHERE `key` = ?");
        $_pStmt->execute(['service_hosting_plans']);
        $_plans = json_decode($_pStmt->fetchColumn() ?: '[]', true) ?: [];
        foreach ($_plans as $_p) {
            if (($_p['label'] ?? '') === $_orderHostingLabel && !empty($_p['_id'])) {
                $_orderHostingLabel = db_trans("service.hosting.plan.{$_p['_id']}.label", null, $_p['label']);
                break;
            }
        }
    } catch (\Throwable $e) {}
}

$pageTitle = __('services.order.complete.page_title');
include BASE_PATH . '/skins/layouts/' . ($siteSettings['site_layout'] ?? 'modern') . '/header.php';
?>

<div class="max-w-2xl mx-auto px-4 py-16 text-center">
    <?php if ($order && $order['status'] === 'paid'): ?>
    <!-- 결제 완료 -->
    <div class="mb-8">
        <div class="w-20 h-20 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2"><?= htmlspecialchars(__('services.order.complete.paid_title')) ?></h1>
        <p class="text-gray-500 dark:text-zinc-400"><?= htmlspecialchars(__('services.order.complete.paid_desc')) ?></p>
    </div>

    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-gray-200 dark:border-zinc-700 p-6 text-left mb-6">
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div><span class="text-gray-400"><?= htmlspecialchars(__('services.order.complete.order_number')) ?></span><p class="font-bold text-gray-900 dark:text-white"><?= htmlspecialchars($order['order_number']) ?></p></div>
            <div><span class="text-gray-400"><?= htmlspecialchars(__('services.order.complete.payment_amount')) ?></span><p class="font-bold text-blue-600 text-lg"><?= $fmt($order['total']) ?></p></div>
            <div><span class="text-gray-400"><?= htmlspecialchars(__('services.order.complete.hosting')) ?></span><p class="text-gray-900 dark:text-white"><?= htmlspecialchars($_orderHostingLabel ?: '-') ?> <?= htmlspecialchars($order['hosting_capacity'] ?? '') ?></p></div>
            <div><span class="text-gray-400"><?= htmlspecialchars(__('services.order.complete.contract_period')) ?></span><p class="text-gray-900 dark:text-white"><?= (int)$order['contract_months'] ?><?= htmlspecialchars(__('services.order.hosting.unit_month')) ?></p></div>
            <?php if ($order['domain']): ?>
            <div><span class="text-gray-400"><?= htmlspecialchars(__('services.order.complete.domain')) ?></span><p class="text-gray-900 dark:text-white"><?= htmlspecialchars($order['domain']) ?></p></div>
            <?php endif; ?>
            <div><span class="text-gray-400"><?= htmlspecialchars(__('services.order.complete.payment_method')) ?></span><p class="text-gray-900 dark:text-white"><?= $order['payment_method'] === 'card' ? htmlspecialchars(__('services.order.payment.method_card')) : htmlspecialchars(__('services.order.payment.method_bank')) ?></p></div>
        </div>
    </div>

    <?php elseif ($order && $order['status'] === 'pending'): ?>
    <!-- 계좌이체 대기 -->
    <div class="mb-8">
        <div class="w-20 h-20 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2"><?= htmlspecialchars(__('services.order.complete.pending_title')) ?></h1>
        <p class="text-gray-500 dark:text-zinc-400"><?= htmlspecialchars(__('services.order.complete.pending_desc')) ?></p>
    </div>

    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-gray-200 dark:border-zinc-700 p-6 text-left mb-6">
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div><span class="text-gray-400"><?= htmlspecialchars(__('services.order.complete.order_number')) ?></span><p class="font-bold text-gray-900 dark:text-white"><?= htmlspecialchars($order['order_number']) ?></p></div>
            <div><span class="text-gray-400"><?= htmlspecialchars(__('services.order.complete.payment_amount')) ?></span><p class="font-bold text-blue-600 text-lg"><?= $fmt($order['total']) ?></p></div>
        </div>
        <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
            <p class="text-sm text-blue-800 dark:text-blue-200 font-medium"><?= htmlspecialchars(__('services.order.complete.pending_email_notice')) ?></p>
        </div>
    </div>

    <?php else: ?>
    <!-- 주문 없음 -->
    <div class="mb-8">
        <div class="w-20 h-20 bg-gray-100 dark:bg-zinc-700 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M12 2a10 10 0 100 20 10 10 0 000-20z"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2"><?= htmlspecialchars(__('services.order.complete.not_found_title')) ?></h1>
    </div>
    <?php endif; ?>

    <div class="flex items-center justify-center gap-3">
        <a href="<?= $baseUrl ?>/mypage" class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition"><?= htmlspecialchars(__('services.order.complete.btn_mypage')) ?></a>
        <a href="<?= $baseUrl ?>/" class="px-6 py-3 border border-gray-300 dark:border-zinc-600 text-gray-700 dark:text-zinc-300 font-semibold rounded-xl hover:bg-gray-50 dark:hover:bg-zinc-700 transition"><?= htmlspecialchars(__('services.order.complete.btn_home')) ?></a>
    </div>
</div>

<?php include BASE_PATH . '/skins/layouts/' . ($siteSettings['site_layout'] ?? 'modern') . '/footer.php'; ?>

<?php
// 페이지 렌더링 완료 후 백그라운드 자동 프로비저닝 트리거 (FPM 격리)
// run-order-provision.php 의 lock 으로 중복 실행 차단됨 (idempotent)
if (!empty($order) && ($order['status'] ?? '') === 'paid') {
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }
    @ignore_user_abort(true);

    $_runScript = BASE_PATH . '/scripts/run-order-provision.php';
    if (is_file($_runScript)) {
        $_logFile = '/tmp/voscms-provision-' . preg_replace('/[^A-Za-z0-9_-]/', '', $order['order_number']) . '.log';
        $_cmd = sprintf(
            '/usr/bin/php8.3 %s --order=%s > %s 2>&1 &',
            escapeshellarg($_runScript),
            escapeshellarg($order['order_number']),
            escapeshellarg($_logFile)
        );
        @exec($_cmd);
    }
}
?>
