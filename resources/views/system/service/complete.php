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

$pageTitle = '주문 완료';
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
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">결제가 완료되었습니다</h1>
        <p class="text-gray-500 dark:text-zinc-400">서비스가 활성화되었습니다. 감사합니다!</p>
    </div>

    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-gray-200 dark:border-zinc-700 p-6 text-left mb-6">
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div><span class="text-gray-400">주문번호</span><p class="font-bold text-gray-900 dark:text-white"><?= htmlspecialchars($order['order_number']) ?></p></div>
            <div><span class="text-gray-400">결제 금액</span><p class="font-bold text-blue-600 text-lg"><?= $fmt($order['total']) ?></p></div>
            <div><span class="text-gray-400">호스팅</span><p class="text-gray-900 dark:text-white"><?= htmlspecialchars($order['hosting_plan'] ?? '-') ?> <?= htmlspecialchars($order['hosting_capacity'] ?? '') ?></p></div>
            <div><span class="text-gray-400">계약 기간</span><p class="text-gray-900 dark:text-white"><?= (int)$order['contract_months'] ?>개월</p></div>
            <?php if ($order['domain']): ?>
            <div><span class="text-gray-400">도메인</span><p class="text-gray-900 dark:text-white"><?= htmlspecialchars($order['domain']) ?></p></div>
            <?php endif; ?>
            <div><span class="text-gray-400">결제 방법</span><p class="text-gray-900 dark:text-white"><?= $order['payment_method'] === 'card' ? '카드 결제' : '계좌이체' ?></p></div>
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
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">주문이 접수되었습니다</h1>
        <p class="text-gray-500 dark:text-zinc-400">입금 확인 후 서비스가 시작됩니다.</p>
    </div>

    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-gray-200 dark:border-zinc-700 p-6 text-left mb-6">
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div><span class="text-gray-400">주문번호</span><p class="font-bold text-gray-900 dark:text-white"><?= htmlspecialchars($order['order_number']) ?></p></div>
            <div><span class="text-gray-400">결제 금액</span><p class="font-bold text-blue-600 text-lg"><?= $fmt($order['total']) ?></p></div>
        </div>
        <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
            <p class="text-sm text-blue-800 dark:text-blue-200 font-medium">입금 안내가 이메일로 발송되었습니다.</p>
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
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">주문을 찾을 수 없습니다</h1>
    </div>
    <?php endif; ?>

    <div class="flex items-center justify-center gap-3">
        <a href="<?= $baseUrl ?>/mypage" class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition">마이페이지</a>
        <a href="<?= $baseUrl ?>/" class="px-6 py-3 border border-gray-300 dark:border-zinc-600 text-gray-700 dark:text-zinc-300 font-semibold rounded-xl hover:bg-gray-50 dark:hover:bg-zinc-700 transition">홈으로</a>
    </div>
</div>

<?php include BASE_PATH . '/skins/layouts/' . ($siteSettings['site_layout'] ?? 'modern') . '/footer.php'; ?>
