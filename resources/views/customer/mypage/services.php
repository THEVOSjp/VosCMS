<?php
/**
 * 마이페이지 — 서비스 관리
 * 구독 서비스 목록, 자동연장 토글, 만기 안내
 */
if (!$isLoggedIn) {
    header("Location: {$baseUrl}/login");
    exit;
}

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

// AJAX: 자동연장 토글
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json; charset=utf-8');
    $input = json_decode(file_get_contents('php://input'), true);
    if (($input['action'] ?? '') === 'toggle_auto_renew') {
        $subId = (int)($input['subscription_id'] ?? 0);
        $autoRenew = $input['auto_renew'] ? 1 : 0;
        $pdo->prepare("UPDATE {$prefix}subscriptions SET auto_renew = ? WHERE id = ? AND user_id = ?")
            ->execute([$autoRenew, $subId, $currentUser['id']]);
        echo json_encode(['success' => true]);
    }
    exit;
}

// 내 주문 목록
$ordersStmt = $pdo->prepare("SELECT * FROM {$prefix}orders WHERE user_id = ? ORDER BY created_at DESC");
$ordersStmt->execute([$currentUser['id']]);
$orders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);

// 내 구독 목록
$subsStmt = $pdo->prepare("SELECT s.*, o.order_number FROM {$prefix}subscriptions s JOIN {$prefix}orders o ON s.order_id = o.id WHERE s.user_id = ? ORDER BY s.expires_at ASC");
$subsStmt->execute([$currentUser['id']]);
$subscriptions = $subsStmt->fetchAll(PDO::FETCH_ASSOC);

// 통화 포맷
$_dispSymbols = ['KRW'=>'₩','USD'=>'$','JPY'=>'¥','CNY'=>'¥','EUR'=>'€'];
$fmtPrice = function($amount, $currency = 'JPY') use ($_dispSymbols) {
    $sym = $_dispSymbols[$currency] ?? $currency;
    $pre = in_array($currency, ['USD','JPY','CNY','EUR']);
    $f = number_format((int)$amount);
    return $pre ? $sym . $f : $f . $sym;
};

// 만기 7일 이내 구독
$soonExpiring = array_filter($subscriptions, function($s) {
    return $s['status'] === 'active' && strtotime($s['expires_at']) <= strtotime('+7 days');
});

$typeLabels = [
    'hosting' => '웹 호스팅', 'domain' => '도메인', 'maintenance' => '유지보수',
    'mail' => '메일', 'support' => '기술 지원',
];
$statusLabels = [
    'active' => ['활성', 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'],
    'expired' => ['만료', 'bg-gray-100 text-gray-500 dark:bg-zinc-700 dark:text-zinc-400'],
    'cancelled' => ['해지', 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400'],
    'suspended' => ['정지', 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'],
    'pending' => ['대기', 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400'],
];
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="lg:flex lg:gap-8">
        <?php $sidebarActive = 'services'; include BASE_PATH . '/resources/views/components/mypage-sidebar.php'; ?>

        <div class="flex-1 min-w-0">
    <h1 class="text-xl font-bold text-zinc-900 dark:text-white mb-6">서비스 관리</h1>

    <?php if (!empty($soonExpiring)): ?>
    <!-- 만기 안내 배너 -->
    <div class="mb-6 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-amber-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
            <div>
                <p class="text-sm font-medium text-amber-800 dark:text-amber-200">다음 서비스가 곧 만료됩니다.</p>
                <?php foreach ($soonExpiring as $s): ?>
                <p class="text-xs text-amber-600 dark:text-amber-300 mt-1">
                    <?= htmlspecialchars($s['label']) ?> — <?= date('Y-m-d', strtotime($s['expires_at'])) ?>
                    <?php if ($s['auto_renew']): ?><span class="text-green-600">(자동 갱신 예정)</span><?php else: ?><span class="text-red-500">(자동 갱신 OFF)</span><?php endif; ?>
                </p>
                <?php endforeach; ?>
                <p class="text-xs text-amber-500 mt-2">자동 연장을 원하지 않으시면 아래에서 OFF로 변경하세요.</p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (empty($subscriptions)): ?>
    <div class="text-center py-16">
        <svg class="w-16 h-16 text-zinc-300 dark:text-zinc-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
        <p class="text-zinc-400 dark:text-zinc-500 mb-4">이용 중인 서비스가 없습니다.</p>
        <a href="<?= $baseUrl ?>/service/order" class="px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium">서비스 신청</a>
    </div>
    <?php else: ?>
    <!-- 구독 서비스 목록 -->
    <div class="space-y-3">
        <?php foreach ($subscriptions as $sub):
            $st = $statusLabels[$sub['status']] ?? ['알 수 없음', 'bg-gray-100 text-gray-500'];
            $typeLabel = $typeLabels[$sub['type']] ?? $sub['type'];
            $isExpiringSoon = $sub['status'] === 'active' && strtotime($sub['expires_at']) <= strtotime('+7 days');
            $daysLeft = max(0, (int)ceil((strtotime($sub['expires_at']) - time()) / 86400));
        ?>
        <div class="bg-white dark:bg-zinc-800 rounded-xl border <?= $isExpiringSoon ? 'border-amber-300 dark:border-amber-700' : 'border-gray-200 dark:border-zinc-700' ?> p-4">
            <div class="flex items-start justify-between gap-4">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-[10px] px-2 py-0.5 rounded-full font-medium <?= $st[1] ?>"><?= $st[0] ?></span>
                        <span class="text-[10px] text-zinc-400"><?= htmlspecialchars($typeLabel) ?></span>
                        <?php if ($isExpiringSoon): ?>
                        <span class="text-[10px] px-2 py-0.5 bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 rounded-full font-medium"><?= $daysLeft ?>일 후 만기</span>
                        <?php endif; ?>
                    </div>
                    <p class="text-sm font-semibold text-zinc-900 dark:text-white"><?= htmlspecialchars($sub['label']) ?></p>
                    <?php
                    $meta = json_decode($sub['metadata'] ?? '{}', true) ?: [];
                    ?>
                    <?php if ($sub['type'] === 'domain' && !empty($meta['domains'])): ?>
                    <div class="flex flex-wrap gap-1.5 mt-1">
                        <?php foreach ($meta['domains'] as $dm): ?>
                        <span class="text-xs px-2 py-0.5 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded font-mono"><?= htmlspecialchars($dm) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php elseif ($sub['type'] === 'hosting'): ?>
                    <?php if (!empty($meta['capacity'])): ?>
                    <p class="text-xs text-zinc-500 mt-0.5">용량: <?= htmlspecialchars($meta['capacity']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($meta['mail_accounts'])): ?>
                    <div class="flex flex-wrap gap-1.5 mt-1">
                        <?php foreach ($meta['mail_accounts'] as $ma): ?>
                        <span class="text-xs px-2 py-0.5 bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded font-mono"><?= htmlspecialchars($ma['address'] ?? '') ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <?php elseif ($sub['type'] === 'mail'): ?>
                    <p class="text-xs text-zinc-500 mt-0.5"><?= (int)($meta['accounts'] ?? $sub['quantity']) ?>계정 × <?= $fmtPrice($sub['unit_price'], $sub['currency']) ?>/계정</p>
                    <?php if (!empty($meta['mail_accounts'])): ?>
                    <div class="flex flex-wrap gap-1.5 mt-1">
                        <?php foreach ($meta['mail_accounts'] as $ma): ?>
                        <span class="text-xs px-2 py-0.5 bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded font-mono"><?= htmlspecialchars($ma['address'] ?? '') ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <?php elseif ($sub['type'] === 'maintenance'): ?>
                    <p class="text-xs text-zinc-500 mt-0.5">월 <?= $fmtPrice($sub['unit_price'], $sub['currency']) ?></p>
                    <?php endif; ?>
                    <div class="flex flex-wrap items-center gap-3 mt-1.5 text-xs text-zinc-400">
                        <span class="flex items-center gap-1"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg><?= date('Y-m-d', strtotime($sub['started_at'])) ?> ~ <?= date('Y-m-d', strtotime($sub['expires_at'])) ?></span>
                        <span class="flex items-center gap-1"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1"/></svg><?= $fmtPrice($sub['billing_amount'], $sub['currency']) ?> / <?= $sub['billing_months'] ?>개월</span>
                        <span class="text-zinc-300">#<?= htmlspecialchars($sub['order_number']) ?></span>
                    </div>
                </div>

                <?php if ($sub['status'] === 'active'): ?>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs text-zinc-400">자동연장</span>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" class="sr-only peer auto-renew-toggle" data-sub-id="<?= $sub['id'] ?>" <?= $sub['auto_renew'] ? 'checked' : '' ?>>
                        <div class="w-9 h-5 bg-zinc-200 rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script>
document.querySelectorAll('.auto-renew-toggle').forEach(function(toggle) {
    toggle.addEventListener('change', function() {
        var subId = this.dataset.subId;
        var autoRenew = this.checked;
        fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ action: 'toggle_auto_renew', subscription_id: subId, auto_renew: autoRenew })
        }).then(function(r) { return r.json(); }).then(function(d) {
            if (!d.success) alert('변경에 실패했습니다.');
        });
    });
});
</script>
    </div>
</div>
