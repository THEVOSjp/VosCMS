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

    // ─── API 요청 처리 ───
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        header('Content-Type: application/json; charset=utf-8');
        $action = $_POST['action'];

        try {
            switch ($action) {
                // ── 서비스 CRUD ──
                case 'create_service':
                    $id = bin2hex(random_bytes(16)); // UUID-like
                    $id = substr($id, 0, 8) . '-' . substr($id, 8, 4) . '-' . substr($id, 12, 4) . '-' . substr($id, 16, 4) . '-' . substr($id, 20, 12);
                    $name = trim($_POST['name'] ?? '');
                    $slug = trim($_POST['slug'] ?? '');
                    $categoryId = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
                    $description = trim($_POST['description'] ?? '');
                    $price = floatval($_POST['price'] ?? 0);
                    $duration = intval($_POST['duration'] ?? 30);
                    $bufferTime = intval($_POST['buffer_time'] ?? 0);
                    $isActive = isset($_POST['is_active']) ? 1 : 0;

                    if (empty($name)) {
                        echo json_encode(['success' => false, 'message' => __('services.fields.name') . ' required']);
                        exit;
                    }
                    if (empty($slug)) {
                        $slug = preg_replace('/[^a-z0-9-]/', '-', strtolower($name));
                        $slug = preg_replace('/-+/', '-', trim($slug, '-'));
                    }

                    $maxSort = $pdo->query("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM {$prefix}services")->fetchColumn();
                    $stmt = $pdo->prepare("INSERT INTO {$prefix}services (id, category_id, name, slug, description, price, duration, buffer_time, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$id, $categoryId, $name, $slug, $description, $price, $duration, $bufferTime, $maxSort, $isActive]);

                    echo json_encode(['success' => true, 'message' => __('services.success.created'), 'id' => $id]);
                    exit;

                case 'update_service':
                    $id = $_POST['id'];
                    $name = trim($_POST['name'] ?? '');
                    $slug = trim($_POST['slug'] ?? '');
                    $categoryId = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
                    $description = trim($_POST['description'] ?? '');
                    $price = floatval($_POST['price'] ?? 0);
                    $duration = intval($_POST['duration'] ?? 30);
                    $bufferTime = intval($_POST['buffer_time'] ?? 0);
                    $isActive = isset($_POST['is_active']) ? 1 : 0;

                    $stmt = $pdo->prepare("UPDATE {$prefix}services SET category_id=?, name=?, slug=?, description=?, price=?, duration=?, buffer_time=?, is_active=? WHERE id=?");
                    $stmt->execute([$categoryId, $name, $slug, $description, $price, $duration, $bufferTime, $isActive, $id]);

                    echo json_encode(['success' => true, 'message' => __('services.success.updated')]);
                    exit;

                case 'delete_service':
                    $id = $_POST['id'];
                    // 예약 확인
                    $cnt = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}reservations WHERE service_id = ?");
                    $cnt->execute([$id]);
                    if ($cnt->fetchColumn() > 0) {
                        echo json_encode(['success' => false, 'message' => __('services.error.has_reservations')]);
                        exit;
                    }
                    $pdo->prepare("DELETE FROM {$prefix}services WHERE id = ?")->execute([$id]);
                    echo json_encode(['success' => true, 'message' => __('services.success.deleted')]);
                    exit;

                case 'toggle_service':
                    $id = $_POST['id'];
                    $stmt = $pdo->prepare("UPDATE {$prefix}services SET is_active = NOT is_active WHERE id = ?");
                    $stmt->execute([$id]);
                    echo json_encode(['success' => true, 'message' => 'OK']);
                    exit;

                default:
                    echo json_encode(['success' => false, 'message' => 'Unknown action']);
                    exit;
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    // ─── 데이터 로드 ───
    // 카테고리 목록
    $categories = $pdo->query("SELECT * FROM {$prefix}service_categories ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);

    // 다국어 표시 룰: 선택언어 → 영어 → 기본언어 → DB 원본
    $currentLocale = $config['locale'] ?? 'ko';
    $defaultLocale = $config['default_language'] ?? 'ko';
    $catLocaleChain = array_unique(array_filter([$currentLocale, 'en', $defaultLocale]));

    $catPlaceholders = implode(',', array_fill(0, count($catLocaleChain), '?'));
    // 카테고리 + 서비스 번역 로드
    $trStmt = $pdo->prepare("SELECT lang_key, locale, content FROM {$prefix}translations WHERE locale IN ({$catPlaceholders}) AND (lang_key LIKE 'category.%' OR lang_key LIKE 'service.%')");
    $trStmt->execute(array_values($catLocaleChain));

    $catAllTranslations = [];
    $svcAllTranslations = [];
    while ($tr = $trStmt->fetch(PDO::FETCH_ASSOC)) {
        if (str_starts_with($tr['lang_key'], 'category.')) {
            $catAllTranslations[$tr['lang_key']][$tr['locale']] = $tr['content'];
        } else {
            $svcAllTranslations[$tr['lang_key']][$tr['locale']] = $tr['content'];
        }
    }

    /**
     * 카테고리 항목의 번역된 텍스트 가져오기
     */
    function getCategoryTranslated($catId, $field, $default) {
        global $catAllTranslations, $catLocaleChain;
        $key = "category.{$catId}.{$field}";
        if (isset($catAllTranslations[$key])) {
            foreach ($catLocaleChain as $loc) {
                if (!empty($catAllTranslations[$key][$loc])) {
                    return $catAllTranslations[$key][$loc];
                }
            }
        }
        return $default;
    }

    /**
     * 서비스 항목의 번역된 텍스트 가져오기
     * 폴백: 선택언어 → 영어 → 기본언어 → DB 원본
     */
    function getServiceTranslated($svcId, $field, $default) {
        global $svcAllTranslations, $catLocaleChain;
        $key = "service.{$svcId}.{$field}";
        if (isset($svcAllTranslations[$key])) {
            foreach ($catLocaleChain as $loc) {
                if (!empty($svcAllTranslations[$key][$loc])) {
                    return $svcAllTranslations[$key][$loc];
                }
            }
        }
        return $default;
    }

    // 서비스 목록 (카테고리 조인)
    $filterCategory = $_GET['category'] ?? '';
    $filterStatus = $_GET['status'] ?? '';

    $sql = "SELECT s.*, c.name as category_name FROM {$prefix}services s LEFT JOIN {$prefix}service_categories c ON s.category_id = c.id WHERE 1=1";
    $params = [];

    if (!empty($filterCategory)) {
        $sql .= " AND s.category_id = ?";
        $params[] = $filterCategory;
    }
    if ($filterStatus === 'active') {
        $sql .= " AND s.is_active = 1";
    } elseif ($filterStatus === 'inactive') {
        $sql .= " AND s.is_active = 0";
    }
    $sql .= " ORDER BY s.sort_order ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 서비스 번역 맵 (JS용)
    $svcTranslatedMap = [];
    foreach ($services as $svc) {
        $svcTranslatedMap[$svc['id']] = [
            'name' => getServiceTranslated($svc['id'], 'name', $svc['name']),
            'description' => getServiceTranslated($svc['id'], 'description', $svc['description'] ?? ''),
        ];
    }

    // 통계
    $totalServices = count($services);
    $activeServices = 0;
    foreach ($services as $s) { if ($s['is_active']) $activeServices++; }
    $totalCategories = count($categories);

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
        <?php include __DIR__ . '/partials/admin-sidebar.php'; ?>

        <!-- Main Content -->
        <main class="flex-1 ml-64">
            <!-- Top Bar -->
            <?php
            $pageHeaderTitle = __('services.title');
            include __DIR__ . '/partials/admin-topbar.php';
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
                        <button onclick="openServiceModal()"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            <?= __('services.create') ?>
                        </button>
                    </div>

                    <!-- 서비스 목록 테이블 -->
                    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
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
                                        <th class="text-left px-4 py-3 font-medium text-zinc-600 dark:text-zinc-300"><?= __('services.fields.name') ?></th>
                                        <th class="text-left px-4 py-3 font-medium text-zinc-600 dark:text-zinc-300"><?= __('services.fields.category') ?></th>
                                        <th class="text-right px-4 py-3 font-medium text-zinc-600 dark:text-zinc-300"><?= __('services.fields.price') ?></th>
                                        <th class="text-center px-4 py-3 font-medium text-zinc-600 dark:text-zinc-300"><?= __('services.fields.duration') ?></th>
                                        <th class="text-center px-4 py-3 font-medium text-zinc-600 dark:text-zinc-300"><?= __('services.fields.is_active') ?></th>
                                        <th class="text-center px-4 py-3 font-medium text-zinc-600 dark:text-zinc-300"><?= __('services.actions') ?></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                    <?php foreach ($services as $svc): ?>
                                    <tr id="svc-<?= htmlspecialchars($svc['id']) ?>" class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition-colors">
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

    <!-- JavaScript include -->
    <?php include __DIR__ . '/services-js.php'; ?>
</body>
</html>
