<?php
/**
 * 번들(묶음서비스) 관리 페이지
 */
include __DIR__ . '/_init.php';

$pageTitle = __('bundles.title') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
$currentLocale = $config['locale'] ?? 'ko';
$currency = $config['currency'] ?? 'KRW';
?>
<!DOCTYPE html>
<html lang="<?= $currentLocale ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= $baseUrl ?>/resources/css/admin-common.css">
</head>
<body class="bg-zinc-100 dark:bg-zinc-900 min-h-screen">
<div class="flex min-h-screen">
    <?php include BASE_PATH . '/resources/views/admin/partials/admin-sidebar.php'; ?>
    <div class="flex-1 flex flex-col min-h-screen sidebar-main-content">
        <?php include BASE_PATH . '/resources/views/admin/partials/admin-topbar.php'; ?>
        <main class="flex-1 p-6">
            <!-- 헤더 -->
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white"><?= __('bundles.title') ?></h1>
                <button onclick="openForm()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <?= __('bundles.create') ?>
                </button>
            </div>
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4 mb-6 text-center">
                <p class="text-blue-700 dark:text-blue-300 text-sm"><?= __('bundles.description') ?></p>
            </div>

            <!-- 번들 목록 -->
            <div id="bundleList" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                <div class="text-center py-12 text-zinc-400 col-span-full"><?= __('bundles.loading') ?>...</div>
            </div>

            <!-- 빈 상태 -->
            <div id="emptyState" class="hidden text-center py-16">
                <svg class="w-16 h-16 mx-auto text-zinc-300 dark:text-zinc-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                <p class="text-zinc-500 dark:text-zinc-400 mb-4"><?= __('bundles.empty') ?></p>
                <button onclick="openForm()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"><?= __('bundles.create_first') ?></button>
            </div>
        </main>
    </div>
</div>

<!-- 번들 생성/수정 모달 -->
<div id="bundleModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50" onclick="closeForm()"></div>
    <div class="absolute inset-y-0 right-0 w-full max-w-lg bg-white dark:bg-zinc-800 shadow-xl flex flex-col">
        <div class="flex items-center justify-between p-4 border-b dark:border-zinc-700">
            <h2 id="modalTitle" class="text-lg font-semibold dark:text-white"><?= __('bundles.create') ?></h2>
            <button onclick="closeForm()" class="p-1 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded">
                <svg class="w-5 h-5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="flex-1 overflow-y-auto p-4 space-y-4">
            <input type="hidden" id="fmId" value="">
            <!-- 번들명 -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('bundles.name') ?> <span class="text-red-500">*</span></label>
                <input type="text" id="fmName" class="w-full px-3 py-2 border rounded-lg dark:bg-zinc-700 dark:border-zinc-600 dark:text-white focus:ring-2 focus:ring-blue-500" placeholder="<?= __('bundles.name_placeholder') ?>">
            </div>
            <!-- 설명 -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('bundles.desc') ?></label>
                <textarea id="fmDesc" rows="2" class="w-full px-3 py-2 border rounded-lg dark:bg-zinc-700 dark:border-zinc-600 dark:text-white focus:ring-2 focus:ring-blue-500" placeholder="<?= __('bundles.desc_placeholder') ?>"></textarea>
            </div>
            <!-- 서비스 선택 -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('bundles.select_services') ?> <span class="text-red-500">*</span></label>
                <div id="serviceCheckList" class="border dark:border-zinc-600 rounded-lg max-h-48 overflow-y-auto p-2 space-y-1">
                    <?php foreach ($services as $svc): ?>
                    <label class="flex items-center gap-2 p-2 rounded hover:bg-zinc-50 dark:hover:bg-zinc-700 cursor-pointer">
                        <input type="checkbox" class="svc-check rounded" value="<?= $svc['id'] ?>" data-price="<?= $svc['price'] ?>" data-duration="<?= $svc['duration'] ?>" data-name="<?= htmlspecialchars($svc['name']) ?>">
                        <span class="flex-1 text-sm dark:text-zinc-200"><?= htmlspecialchars($svc['name']) ?></span>
                        <span class="text-xs text-zinc-400"><?= number_format((float)$svc['price']) ?><?= $currency ?> / <?= $svc['duration'] ?><?= __('bundles.min') ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
                <div class="mt-2 flex items-center justify-between text-sm">
                    <span class="text-zinc-500 dark:text-zinc-400"><?= __('bundles.original_price') ?>: <strong id="fmOrigPrice">0</strong> <?= $currency ?></span>
                    <span class="text-zinc-500 dark:text-zinc-400"><?= __('bundles.total_duration') ?>: <strong id="fmTotalDur">0</strong><?= __('bundles.min') ?></span>
                </div>
            </div>
            <!-- 번들 가격 -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('bundles.bundle_price') ?> (<?= $currency ?>)</label>
                <input type="number" id="fmPrice" min="0" step="100" class="w-full px-3 py-2 border rounded-lg dark:bg-zinc-700 dark:border-zinc-600 dark:text-white focus:ring-2 focus:ring-blue-500" placeholder="0">
                <p id="fmDiscount" class="text-xs text-green-600 mt-1 hidden"></p>
            </div>
            <!-- 표시 순서, 활성 -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('bundles.display_order') ?></label>
                    <input type="number" id="fmOrder" min="0" value="0" class="w-full px-3 py-2 border rounded-lg dark:bg-zinc-700 dark:border-zinc-600 dark:text-white focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('bundles.status') ?></label>
                    <select id="fmActive" class="w-full px-3 py-2 border rounded-lg dark:bg-zinc-700 dark:border-zinc-600 dark:text-white focus:ring-2 focus:ring-blue-500">
                        <option value="1"><?= __('bundles.active') ?></option>
                        <option value="0"><?= __('bundles.inactive') ?></option>
                    </select>
                </div>
            </div>
        </div>
        <div class="p-4 border-t dark:border-zinc-700 flex gap-2">
            <button onclick="closeForm()" class="flex-1 px-4 py-2 border dark:border-zinc-600 rounded-lg text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700"><?= __('bundles.cancel') ?></button>
            <button onclick="saveBundle()" id="btnSave" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"><?= __('bundles.save') ?></button>
        </div>
    </div>
</div>

<?php include __DIR__ . '/_js.php'; ?>
</body>
</html>
