<?php
/**
 * RezlyX Admin - 서비스 관리 페이지
 *
 * 카테고리 + 서비스 CRUD (범용 예약 서비스 관리)
 */

// 다국어 함수 로드
if (!function_exists('__')) {
    require_once BASE_PATH . '/rzxlib/Core/Helpers/lang.php';
}

$pageTitle = __('services.title') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');

// DB 연결
$settings = [];
try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'],
        $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

    // 설정 로드
    $stmt = $pdo->query("SELECT `key`, `value` FROM {$prefix}settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['key']] = $row['value'];
    }

    // ─── API 요청 처리 (분리 파일) ───
    include __DIR__ . '/services-api.php';

    // ─── 데이터 로드 (분리 파일) ───
    include __DIR__ . '/services-data.php';

    $dbConnected = true;
} catch (PDOException $e) {
    $dbConnected = false;
    $dbError = $e->getMessage();
    $categories = [];
    $services = [];
    $totalServices = $activeServices = $totalCategories = 0;
}

// 통화 설정 (서비스 설정 > 기본설정에서 저장된 값)
$serviceCurrency = $settings['service_currency'] ?? 'KRW';
$priceDisplay = $settings['service_price_display'] ?? 'show';
$currencySymbols = [
    'KRW' => '₩', 'USD' => '$', 'JPY' => '¥', 'EUR' => '€', 'CNY' => '¥'
];
$currency = $currencySymbols[$serviceCurrency] ?? $serviceCurrency;
?>
<!DOCTYPE html>
<html lang="<?php echo $config['locale'] ?? 'ko'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' }
    </script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css">
    <style>
        body { font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, sans-serif; }
    </style>
    <script>
        if (localStorage.getItem('darkMode') === 'true' ||
            (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="bg-zinc-100 dark:bg-zinc-900 min-h-screen transition-colors">
    <div class="flex">
        <!-- Sidebar -->
        <?php include BASE_PATH . '/resources/views/admin/partials/admin-sidebar.php'; ?>

        <!-- Main Content -->
        <main class="flex-1 ml-64">
            <!-- Top Bar -->
            <?php
            $pageHeaderTitle = __('services.title');
            include BASE_PATH . '/resources/views/admin/partials/admin-topbar.php';
            ?>

            <div class="p-6">
                <!-- 알림 메시지 -->
                <div id="alertBox" class="hidden mb-6 p-4 rounded-lg border"></div>

                <!-- 통계 카드 -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-white dark:bg-zinc-800 rounded-xl p-5 border border-zinc-200 dark:border-zinc-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400"><?= __('services.list') ?></p>
                                <p class="text-2xl font-bold mt-1 text-zinc-900 dark:text-white"><?= $totalServices ?></p>
                            </div>
                            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-zinc-800 rounded-xl p-5 border border-zinc-200 dark:border-zinc-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400"><?= __('services.fields.is_active') ?></p>
                                <p class="text-2xl font-bold mt-1 text-green-600"><?= $activeServices ?></p>
                            </div>
                            <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-zinc-800 rounded-xl p-5 border border-zinc-200 dark:border-zinc-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400"><?= __('services.categories.title') ?></p>
                                <p class="text-2xl font-bold mt-1 text-zinc-900 dark:text-white"><?= $totalCategories ?></p>
                            </div>
                            <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ═══ 서비스 목록 ═══ -->
                <div id="panelServices">
                    <!-- 필터 + 추가 버튼 -->
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-4">
                        <div class="flex items-center gap-2">
                            <select id="filterCategory" onchange="applyFilter()"
                                    class="text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 bg-white dark:bg-zinc-800 dark:text-white">
                                <option value=""><?= __('services.fields.category') ?> - <?= __('services.filter_all') ?></option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $filterCategory == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars(getCategoryTranslated($cat['id'], 'name', $cat['name'])) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select id="filterStatus" onchange="applyFilter()"
                                    class="text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 bg-white dark:bg-zinc-800 dark:text-white">
                                <option value=""><?= __('services.fields.is_active') ?> - <?= __('services.filter_all') ?></option>
                                <option value="active" <?= $filterStatus === 'active' ? 'selected' : '' ?>><?= __('services.filter_active') ?></option>
                                <option value="inactive" <?= $filterStatus === 'inactive' ? 'selected' : '' ?>><?= __('services.filter_inactive') ?></option>
                            </select>
                        </div>
                        <div class="flex items-center gap-2">
                            <!-- 뷰 전환 버튼 -->
                            <div class="flex items-center bg-zinc-200 dark:bg-zinc-700 rounded-lg p-0.5">
                                <button onclick="switchView('table')" id="viewBtnTable" title="<?= __('common.table_view') ?>"
                                        class="p-1.5 rounded-md transition-colors bg-white dark:bg-zinc-600 text-zinc-900 dark:text-white shadow-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                                </button>
                                <button onclick="switchView('card')" id="viewBtnCard" title="<?= __('common.card_view') ?>"
                                        class="p-1.5 rounded-md transition-colors text-zinc-500 dark:text-zinc-400 hover:text-zinc-700">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/></svg>
                                </button>
                            </div>
                            <button onclick="openServiceModal()"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <?= __('services.create') ?>
                            </button>
                        </div>
                    </div>

                    <!-- 서비스 목록 테이블 -->
                    <div id="serviceTableView" class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                        <?php if (empty($services)): ?>
                        <div class="p-12 text-center text-zinc-500 dark:text-zinc-400">
                            <svg class="w-12 h-12 mx-auto mb-3 text-zinc-300 dark:text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                            <p><?= __('services.empty') ?></p>
                            <button onclick="openServiceModal()" class="mt-3 text-blue-600 hover:text-blue-700 text-sm font-medium">+ <?= __('services.create') ?></button>
                        </div>
                        <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-zinc-50 dark:bg-zinc-700/50">
                                    <tr>
                                        <th class="w-10 px-2 py-3"></th>
                                        <th class="text-left px-4 py-3 font-medium text-zinc-600 dark:text-zinc-300"><?= __('services.fields.name') ?></th>
                                        <th class="text-left px-4 py-3 font-medium text-zinc-600 dark:text-zinc-300"><?= __('services.fields.category') ?></th>
                                        <th class="text-right px-4 py-3 font-medium text-zinc-600 dark:text-zinc-300"><?= __('services.fields.price') ?></th>
                                        <th class="text-center px-4 py-3 font-medium text-zinc-600 dark:text-zinc-300"><?= __('services.fields.duration') ?></th>
                                        <th class="text-center px-4 py-3 font-medium text-zinc-600 dark:text-zinc-300"><?= __('services.fields.is_active') ?></th>
                                        <th class="text-center px-4 py-3 font-medium text-zinc-600 dark:text-zinc-300"><?= __('services.actions') ?></th>
                                    </tr>
                                </thead>
                                <tbody id="serviceTableBody" class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                    <?php foreach ($services as $svc): ?>
                                    <tr id="svc-<?= htmlspecialchars($svc['id']) ?>" data-id="<?= htmlspecialchars($svc['id']) ?>" class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition-colors">
                                        <td class="px-2 py-3 text-center drag-handle cursor-grab active:cursor-grabbing">
                                            <svg class="w-4 h-4 text-zinc-400 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/></svg>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="font-medium text-zinc-900 dark:text-white"><?= htmlspecialchars(getServiceTranslated($svc['id'], 'name', $svc['name'])) ?></div>
                                            <?php $svcDesc = getServiceTranslated($svc['id'], 'description', $svc['description'] ?? ''); if (!empty($svcDesc)): ?>
                                            <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5 truncate max-w-xs"><?= htmlspecialchars(mb_substr($svcDesc, 0, 60)) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3">
                                            <?php if ($svc['category_name']): ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                                                <?= htmlspecialchars(getCategoryTranslated($svc['category_id'], 'name', $svc['category_name'])) ?>
                                            </span>
                                            <?php else: ?>
                                            <span class="text-zinc-400">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3 text-right font-medium text-zinc-900 dark:text-white">
                                            <?php if ($priceDisplay === 'show'): ?>
                                                <?= $currency ?><?= number_format($svc['price']) ?>
                                            <?php elseif ($priceDisplay === 'contact'): ?>
                                                <span class="text-zinc-500 dark:text-zinc-400 text-xs"><?= __('services.settings.general.price_contact') ?></span>
                                            <?php else: ?>
                                                <span class="text-zinc-400">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3 text-center text-zinc-900 dark:text-white"><?= $svc['duration'] ?><?= __('services.minute') ?></td>
                                        <td class="px-4 py-3 text-center">
                                            <button onclick="toggleService('<?= $svc['id'] ?>')"
                                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors <?= $svc['is_active'] ? 'bg-green-500' : 'bg-zinc-300 dark:bg-zinc-600' ?>"
                                                    data-active="<?= $svc['is_active'] ?>" id="toggle-<?= htmlspecialchars($svc['id']) ?>">
                                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform <?= $svc['is_active'] ? 'translate-x-6' : 'translate-x-1' ?>"></span>
                                            </button>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <div class="flex items-center justify-center gap-1">
                                                <button onclick='editService(<?= json_encode($svc, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                                        class="p-1.5 text-zinc-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors" title="<?= __('services.edit') ?>">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                </button>
                                                <button onclick="deleteService('<?= $svc['id'] ?>')"
                                                        class="p-1.5 text-zinc-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors" title="<?= __('services.categories.delete') ?>">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- 서비스 카드 뷰 -->
                    <div id="serviceCardView" class="hidden grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4" style="grid-auto-rows:auto">
                        <?php if (empty($services)): ?>
                        <div class="col-span-full p-12 text-center text-zinc-500 dark:text-zinc-400 bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700">
                            <svg class="w-12 h-12 mx-auto mb-3 text-zinc-300 dark:text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                            <p><?= __('services.empty') ?></p>
                        </div>
                        <?php else: ?>
                        <?php foreach ($services as $svc):
                            $svcName = htmlspecialchars(getServiceTranslated($svc['id'], 'name', $svc['name']));
                            $svcDesc = htmlspecialchars(mb_substr(getServiceTranslated($svc['id'], 'description', $svc['description'] ?? ''), 0, 80));
                            $catName = $svc['category_name'] ? htmlspecialchars(getCategoryTranslated($svc['category_id'], 'name', $svc['category_name'])) : '';
                            $imgUrl = !empty($svc['image']) ? $baseUrl . '/' . htmlspecialchars($svc['image']) : '';
                        ?>
                        <div data-id="<?= htmlspecialchars($svc['id']) ?>" class="rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden hover:shadow-lg transition-all group relative h-56 cursor-grab active:cursor-grabbing <?= $imgUrl ? '' : 'bg-zinc-100 dark:bg-zinc-800' ?>"
                             <?php if ($imgUrl): ?>style="background-image:url('<?= $imgUrl ?>');background-size:cover;background-position:center"<?php endif; ?>>
                            <!-- 오버레이 -->
                            <div class="absolute inset-0 <?= $imgUrl ? 'bg-gradient-to-t from-black/80 via-black/30 to-black/10' : '' ?>"></div>
                            <?php if (!$imgUrl): ?>
                            <div class="absolute inset-0 flex items-center justify-center opacity-30">
                                <svg class="w-16 h-16 text-zinc-400 dark:text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <?php endif; ?>
                            <!-- 상태 뱃지 -->
                            <div class="absolute top-2 right-2 z-10">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $svc['is_active'] ? 'bg-green-500 text-white' : 'bg-zinc-400 text-white' ?>">
                                    <?= $svc['is_active'] ? __('services.filter_active') : __('services.filter_inactive') ?>
                                </span>
                            </div>
                            <!-- 호버 액션 -->
                            <div class="absolute top-2 left-2 z-10 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button onclick='editService(<?= json_encode($svc, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                        class="p-1.5 bg-white/90 backdrop-blur rounded-lg text-zinc-700 hover:bg-blue-50 hover:text-blue-600 transition-colors" title="<?= __('services.edit') ?>">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <button onclick="deleteService('<?= $svc['id'] ?>')"
                                        class="p-1.5 bg-white/90 backdrop-blur rounded-lg text-zinc-700 hover:bg-red-50 hover:text-red-600 transition-colors" title="<?= __('services.categories.delete') ?>">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                            <!-- 하단 정보 -->
                            <div class="absolute bottom-0 left-0 right-0 p-4 z-10">
                                <?php if ($catName): ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-white/20 backdrop-blur text-white mb-2"><?= $catName ?></span>
                                <?php endif; ?>
                                <h3 class="font-semibold <?= $imgUrl ? 'text-white' : 'text-zinc-900 dark:text-white' ?> text-base"><?= $svcName ?></h3>
                                <?php if ($svcDesc): ?>
                                <p class="text-xs <?= $imgUrl ? 'text-white/70' : 'text-zinc-500 dark:text-zinc-400' ?> mt-1 line-clamp-1"><?= $svcDesc ?></p>
                                <?php endif; ?>
                                <div class="flex items-center justify-between mt-2">
                                    <span class="text-sm <?= $imgUrl ? 'text-white/70' : 'text-zinc-500 dark:text-zinc-400' ?>">
                                        <svg class="w-4 h-4 inline -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <?= $svc['duration'] ?><?= __('services.minute') ?>
                                    </span>
                                    <span class="font-bold <?= $imgUrl ? 'text-white' : 'text-zinc-900 dark:text-white' ?>">
                                        <?php if ($priceDisplay === 'show'): ?>
                                            <?= $currency ?><?= number_format($svc['price']) ?>
                                        <?php elseif ($priceDisplay === 'contact'): ?>
                                            <span class="text-xs font-normal <?= $imgUrl ? 'text-white/70' : 'text-zinc-500' ?>"><?= __('services.settings.general.price_contact') ?></span>
                                        <?php else: ?>-<?php endif; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <!-- 모달 폼 include -->
    <?php include __DIR__ . '/services-form.php'; ?>

    <!-- jQuery + Summernote (다국어 모달 에디터 의존성) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>

    <!-- 다국어 입력 모달 -->
    <?php include __DIR__ . '/components/multilang-modal.php'; ?>

    <!-- SortableJS -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

    <!-- 뷰 전환 + 드래그앤드롭 -->
    <script>
    function switchView(mode) {
        console.log('[Services] switchView:', mode);
        const tableView = document.getElementById('serviceTableView');
        const cardView = document.getElementById('serviceCardView');
        const btnTable = document.getElementById('viewBtnTable');
        const btnCard = document.getElementById('viewBtnCard');
        const activeClass = 'bg-white dark:bg-zinc-600 text-zinc-900 dark:text-white shadow-sm';
        const inactiveClass = 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-700';

        if (mode === 'card') {
            tableView.classList.add('hidden');
            cardView.classList.remove('hidden');
            btnCard.className = 'p-1.5 rounded-md transition-colors ' + activeClass;
            btnTable.className = 'p-1.5 rounded-md transition-colors ' + inactiveClass;
        } else {
            tableView.classList.remove('hidden');
            cardView.classList.add('hidden');
            btnTable.className = 'p-1.5 rounded-md transition-colors ' + activeClass;
            btnCard.className = 'p-1.5 rounded-md transition-colors ' + inactiveClass;
        }
        localStorage.setItem('serviceViewMode', mode);
    }
    // 순서 저장 API → 저장 후 리로드하여 양쪽 뷰 동기화
    function saveOrder(container) {
        const items = container.querySelectorAll('[data-id]');
        const ids = Array.from(items).map(el => el.dataset.id);
        console.log('[Services] Saving order:', ids.length, 'ids:', ids);
        fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=reorder_services&ids=' + encodeURIComponent(JSON.stringify(ids))
        })
        .then(r => {
            console.log('[Services] Response status:', r.status, 'type:', r.headers.get('content-type'));
            return r.text();
        })
        .then(text => {
            console.log('[Services] Response body:', text.substring(0, 200));
            try {
                const data = JSON.parse(text);
                if (data.success) window.location.reload();
                else console.error('[Services] Order failed:', data.message);
            } catch(e) {
                console.error('[Services] Not JSON response, likely HTML page returned');
            }
        })
        .catch(e => console.error('[Services] Order save error:', e));
    }

    // SortableJS 초기화 + 뷰 모드 복원
    document.addEventListener('DOMContentLoaded', function() {
        const saved = localStorage.getItem('serviceViewMode');
        if (saved === 'card') switchView('card');

        // 테이블 드래그
        const tbody = document.getElementById('serviceTableBody');
        if (tbody) {
            Sortable.create(tbody, {
                handle: '.drag-handle',
                animation: 150,
                ghostClass: 'bg-blue-50',
                onEnd: function() { saveOrder(tbody); }
            });
        }

        // 카드 드래그
        const cardView = document.getElementById('serviceCardView');
        if (cardView) {
            Sortable.create(cardView, {
                animation: 150,
                ghostClass: 'opacity-50',
                filter: '.col-span-full',
                onEnd: function() { saveOrder(cardView); }
            });
        }
    });
    </script>

    <!-- JavaScript include -->
    <?php include __DIR__ . '/services-js.php'; ?>
</body>
</html>
