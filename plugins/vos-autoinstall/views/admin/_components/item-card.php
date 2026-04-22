<?php
/**
 * 마켓플레이스 아이템 카드 컴포넌트
 * $item (배열) 필요
 */
$_name = json_decode($item['name'] ?? '{}', true);
$_itemName = $_name[$locale] ?? $_name['en'] ?? $item['slug'] ?? '';
$_desc = json_decode($item['short_description'] ?? $item['description'] ?? '{}', true);
$_itemDesc = $_desc[$locale] ?? $_desc['en'] ?? '';
$_price = (float)($item['price'] ?? 0);
$_salePrice = isset($item['sale_price']) ? (float)$item['sale_price'] : null;
$_saleEnds = $item['sale_ends_at'] ?? null;
$_onSale = $_salePrice !== null && $_saleEnds && strtotime($_saleEnds) > time();
$_effectivePrice = $_onSale ? $_salePrice : $_price;
$_isFree = $_effectivePrice <= 0;
$_currency = $item['currency'] ?? 'JPY';
$_typeLabels = ['plugin' => __mp('plugins'), 'theme' => __mp('themes'), 'widget' => __mp('widgets'), 'skin' => __mp('skins')];
$_typeColors = ['plugin' => 'indigo', 'theme' => 'purple', 'widget' => 'emerald', 'skin' => 'orange'];
$_type = $item['type'] ?? 'plugin';
$_color = $_typeColors[$_type] ?? 'indigo';
$_priceLabel = $_isFree
    ? __mp('free')
    : number_format((int)$_effectivePrice, 0) . ' ' . $_currency;
$_priceInt = (int)$_effectivePrice;
?>
<div class="group bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden hover:shadow-md hover:border-indigo-300 dark:hover:border-indigo-600 transition-all flex flex-col">

    <!-- 배너/아이콘 (클릭 → 상세) -->
    <a href="<?= $adminUrl ?>/autoinstall/item?slug=<?= urlencode($item['slug'] ?? '') ?>" class="block">
        <div class="aspect-[16/9] bg-gradient-to-br from-<?= $_color ?>-50 to-<?= $_color ?>-100 dark:from-<?= $_color ?>-900/20 dark:to-<?= $_color ?>-800/20 flex items-center justify-center relative overflow-hidden">
            <?php if (!empty($item['banner_image'])): ?>
                <img src="<?= htmlspecialchars($item['banner_image']) ?>" alt="" class="w-full h-full object-cover">
            <?php elseif (!empty($item['icon'])): ?>
                <img src="<?= htmlspecialchars($item['icon']) ?>" alt="" class="w-16 h-16 rounded-xl shadow-lg">
            <?php else: ?>
                <svg class="w-12 h-12 text-<?= $_color ?>-400 dark:text-<?= $_color ?>-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            <?php endif; ?>
            <div class="absolute top-2 left-2 flex gap-1.5">
                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-<?= $_color ?>-100 text-<?= $_color ?>-700 dark:bg-<?= $_color ?>-900/40 dark:text-<?= $_color ?>-400">
                    <?= $_typeLabels[$_type] ?? $_type ?>
                </span>
                <?php if ($item['is_featured'] ?? false): ?>
                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-400"><?= __mp('featured') ?></span>
                <?php endif; ?>
            </div>
            <?php if ($item['is_verified'] ?? false): ?>
            <div class="absolute top-2 right-2">
                <svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            </div>
            <?php endif; ?>
        </div>
    </a>

    <!-- 정보 -->
    <div class="p-4 flex flex-col flex-1">
        <a href="<?= $adminUrl ?>/autoinstall/item?slug=<?= urlencode($item['slug'] ?? '') ?>" class="block">
            <h3 class="font-semibold text-zinc-800 dark:text-zinc-200 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors truncate">
                <?= htmlspecialchars($_itemName) ?>
            </h3>
            <?php if ($_itemDesc): ?>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 line-clamp-2"><?= htmlspecialchars(mb_substr(strip_tags($_itemDesc), 0, 80)) ?></p>
            <?php endif; ?>
        </a>

        <div class="flex items-center justify-between mt-3">
            <div>
                <?php if ($_isFree): ?>
                <span class="text-sm font-bold text-green-600 dark:text-green-400"><?= __mp('free') ?></span>
                <?php elseif ($_onSale): ?>
                <span class="text-xs text-zinc-400 line-through mr-1"><?= number_format((int)$_price) ?></span>
                <span class="text-sm font-bold text-red-600 dark:text-red-400"><?= number_format((int)$_salePrice) ?> <?= $_currency ?></span>
                <?php else: ?>
                <span class="text-sm font-bold text-zinc-800 dark:text-zinc-200"><?= number_format((int)$_price) ?> <?= $_currency ?></span>
                <?php endif; ?>
            </div>
            <div class="flex items-center gap-2 text-xs text-zinc-400">
                <?php if (($item['rating_avg'] ?? 0) > 0): ?>
                <span class="flex items-center gap-0.5">
                    <svg class="w-3.5 h-3.5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    <?= number_format((float)($item['rating_avg'] ?? 0), 1) ?>
                </span>
                <?php endif; ?>
                <span class="flex items-center gap-0.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <?= number_format((int)($item['download_count'] ?? 0)) ?>
                </span>
            </div>
        </div>

        <?php if (!empty($item['author_name'])): ?>
        <p class="text-xs text-zinc-400 mt-1"><?= htmlspecialchars($item['author_name']) ?> &middot; v<?= htmlspecialchars($item['latest_version'] ?? '1.0.0') ?></p>
        <?php endif; ?>

        <!-- 구매/설치 버튼 -->
        <div class="mt-3 pt-3 border-t border-zinc-100 dark:border-zinc-700">
            <?php if ($_isFree): ?>
            <button type="button"
                    onclick="mpInstallItem(this)"
                    data-slug="<?= htmlspecialchars($item['slug']) ?>"
                    class="w-full py-1.5 px-3 text-xs font-medium rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white transition-colors">
                설치
            </button>
            <?php else: ?>
            <button type="button"
                    onclick="mpOpenPurchase(this)"
                    data-slug="<?= htmlspecialchars($item['slug']) ?>"
                    data-name="<?= htmlspecialchars($_itemName) ?>"
                    data-price="<?= $_priceInt ?>"
                    data-currency="<?= htmlspecialchars($_currency) ?>"
                    data-price-label="<?= htmlspecialchars($_priceLabel) ?>"
                    class="w-full py-1.5 px-3 text-xs font-medium rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white transition-colors">
                구매 <?= htmlspecialchars($_priceLabel) ?>
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>
