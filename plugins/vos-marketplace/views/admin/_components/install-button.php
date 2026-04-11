<?php
/**
 * 설치/구매 버튼 컴포넌트
 * $item (배열), $hasPurchased (bool), $isInstalled (bool) 필요
 */
$_price = (float)($item['price'] ?? 0);
$_isFree = $_price <= 0;
$_itemId = $item['id'] ?? 0;
?>
<?php if ($isInstalled ?? false): ?>
<span class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/20 rounded-lg">
    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
    <?= __mp('installed') ?>
</span>
<?php elseif ($hasPurchased ?? false): ?>
<button onclick="installItem(<?= $_itemId ?>)"
        class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
    <?= __mp('install_now') ?>
</button>
<?php elseif ($_isFree): ?>
<button onclick="purchaseItem(<?= $_itemId ?>)"
        class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
    <?= __mp('install_now') ?> (<?= __mp('free') ?>)
</button>
<?php else: ?>
<button onclick="purchaseItem(<?= $_itemId ?>)"
        class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17"/></svg>
    <?= __mp('purchase') ?>
</button>
<?php endif; ?>
