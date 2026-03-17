<?php
/**
 * RezlyX Admin Dashboard
 */

// 다국어 함수 로드
if (!function_exists('__')) {
    require_once BASE_PATH . '/rzxlib/Core/Helpers/lang.php';
}

$pageTitle = __('admin.nav.dashboard') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';

// Get database stats and settings
$settings = [];
try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'],
        $_ENV['DB_PASSWORD']
    );
    $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

    // Today's reservations
    $stmt = $pdo->query("SELECT COUNT(*) FROM {$prefix}reservations WHERE reservation_date = CURDATE()");
    $todayReservations = $stmt->fetchColumn();

    // Total users
    $stmt = $pdo->query("SELECT COUNT(*) FROM {$prefix}users");
    $totalUsers = $stmt->fetchColumn();

    // Total services
    $stmt = $pdo->query("SELECT COUNT(*) FROM {$prefix}services");
    $totalServices = $stmt->fetchColumn();

    // Load settings for language selector
    $stmt = $pdo->query("SELECT `key`, `value` FROM {$prefix}settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['key']] = $row['value'];
    }

    // 이번 달 예약 현황 (풀 캘린더용) - 월 이동 지원
    $calYear = (int)($_GET['cal_year'] ?? date('Y'));
    $calMonth = (int)($_GET['cal_month'] ?? date('m'));
    if ($calMonth < 1) { $calMonth = 12; $calYear--; }
    if ($calMonth > 12) { $calMonth = 1; $calYear++; }
    $calFirstDay = sprintf('%04d-%02d-01', $calYear, $calMonth);
    $calLastDay = date('Y-m-t', strtotime($calFirstDay));

    try {
        $calStmt = $pdo->prepare("SELECT r.*, (SELECT GROUP_CONCAT(rs.service_name ORDER BY rs.sort_order SEPARATOR ', ') FROM {$prefix}reservation_services rs WHERE rs.reservation_id = r.id) as service_name FROM {$prefix}reservations r WHERE r.reservation_date BETWEEN ? AND ? ORDER BY r.start_time ASC");
        $calStmt->execute([$calFirstDay, $calLastDay]);
        $calReservations = $calStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // service_name 컬럼 미존재 시 폴백 (마이그레이션 미적용 환경)
        $calStmt = $pdo->prepare("SELECT r.* FROM {$prefix}reservations r WHERE r.reservation_date BETWEEN ? AND ? ORDER BY r.start_time ASC");
        $calStmt->execute([$calFirstDay, $calLastDay]);
        $calReservations = $calStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    $calByDate = [];
    foreach ($calReservations as $cr) {
        $calByDate[$cr['reservation_date']][] = $cr;
    }

    // 통화 설정
    $currencySymbols = [
        'KRW' => '₩', 'USD' => '$', 'JPY' => '¥', 'EUR' => '€',
        'CNY' => '¥', 'GBP' => '£', 'THB' => '฿', 'VND' => '₫',
        'MNT' => '₮', 'RUB' => '₽', 'TRY' => '₺', 'IDR' => 'Rp',
    ];
    $dashCurrency = $settings['service_currency'] ?? 'KRW';
    $dashCurrencySymbol = $currencySymbols[$dashCurrency] ?? $dashCurrency;
    $dashCurrencyPosition = $settings['service_currency_position'] ?? 'prefix';

    // 서비스 목록 (빠른 예약 추가용)
    $dashServices = $pdo->query("SELECT id, name, duration, price FROM {$prefix}services WHERE is_active = 1 ORDER BY sort_order ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC);

        // 미적용 마이그레이션 확인
    $pendingMigrations = [];
    try {
        // migrations 테이블 존재 여부 확인
        $migTableExists = false;
        $checkStmt = $pdo->query("SHOW TABLES LIKE '{$prefix}migrations'");
        if ($checkStmt->rowCount() > 0) {
            $migTableExists = true;
            $appliedStmt = $pdo->query("SELECT migration FROM {$prefix}migrations");
            $appliedMigs = $appliedStmt->fetchAll(PDO::FETCH_COLUMN);
        } else {
            $appliedMigs = [];
        }

        $migDir = BASE_PATH . '/database/migrations';
        if (is_dir($migDir)) {
            $migFiles = glob($migDir . '/*.sql');
            sort($migFiles);
            foreach ($migFiles as $mf) {
                $migName = basename($mf, '.sql');
                if (!in_array($migName, $appliedMigs)) {
                    $pendingMigrations[] = $migName;
                }
            }
        }
    } catch (Exception $e) {
        // 무시 — migrations 테이블 없으면 전부 pending
    }

    $dbConnected = true;
} catch (Exception $e) {
    $dbConnected = false;
    $dbError = $e->getMessage();
}

// Base URLs for navigation
$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');
?>
<!DOCTYPE html>
<html lang="<?php echo $config['locale'] ?? 'ko'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>

    <?php include __DIR__ . '/partials/pwa-head.php'; ?>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class'
        }
    </script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css">
    <style>
        body { font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, sans-serif; }
    </style>
    <script>
        // Dark mode initialization
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
            $pageHeaderTitle = __('admin.nav.dashboard');
            include __DIR__ . '/partials/admin-topbar.php';
            ?>

            <!-- Dashboard Content -->
            <div class="p-6">
                <!-- Update Available Banner -->
                <?php if (!empty($updateInfo) && !empty($updateInfo['has_update'])): ?>
                <?php $updateUrl = ($config['app_url'] ?? '') . '/' . ($config['admin_path'] ?? 'admin') . '/settings/system/updates'; ?>
                <div class="mb-6 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-xl p-4 shadow-lg">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center text-white">
                            <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center mr-4">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-semibold text-lg">RezlyX v<?= htmlspecialchars($updateInfo['latest'] ?? '') ?> <?= __('system.updates.available_short') ?></p>
                                <p class="text-blue-100 text-sm"><?= __('system.updates.update_recommend') ?></p>
                            </div>
                        </div>
                        <a href="<?= htmlspecialchars($updateUrl) ?>"
                           class="px-5 py-2.5 bg-white text-blue-600 font-semibold rounded-lg hover:bg-blue-50 transition shadow-sm flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            <?= __('system.updates.update_now') ?>
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Pending Migrations Banner -->
                <?php if (!empty($pendingMigrations)): ?>
                <div class="mb-6 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4" id="migrationBanner">
                    <div class="flex items-start">
                        <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/40 rounded-lg flex items-center justify-center mr-4 shrink-0 mt-0.5">
                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="font-semibold text-amber-800 dark:text-amber-300 text-base"><?= __('admin.dashboard.migration_required') ?></p>
                            <p class="text-amber-700 dark:text-amber-400 text-sm mt-1"><?= __('admin.dashboard.migration_desc') ?></p>
                            <ul class="mt-2 space-y-1">
                                <?php foreach ($pendingMigrations as $pm): ?>
                                <li class="flex items-center text-sm text-amber-700 dark:text-amber-400">
                                    <svg class="w-4 h-4 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                    <span class="font-mono text-xs"><?= htmlspecialchars($pm) ?></span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <button onclick="runMigrations(this)" class="mt-3 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg transition inline-flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <?= __('admin.dashboard.run_migration') ?>
                            </button>
                            <span id="migrationResult" class="ml-3 text-sm hidden"></span>
                        </div>
                    </div>
                </div>
                <script>
                async function runMigrations(btn) {
                    console.log('[Dashboard] Running migrations...');
                    btn.disabled = true;
                    btn.innerHTML = '<svg class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><?= __('admin.messages.processing') ?>';
                    const resultSpan = document.getElementById('migrationResult');

                    try {
                        const resp = await fetch(window.location.origin + '/update-api.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: 'action=run_migrations'
                        });
                        const data = await resp.json();
                        console.log('[Dashboard] Migration result:', data);

                        if (data.success) {
                            const applied = data.data?.applied || 0;
                            resultSpan.textContent = '✓ ' + applied + '<?= __('admin.dashboard.migration_applied') ?>';
                            resultSpan.className = 'ml-3 text-sm text-green-700 dark:text-green-400 font-medium';
                            // 성공 시 배너 교체
                            setTimeout(() => {
                                document.getElementById('migrationBanner').innerHTML = `
                                    <div class="flex items-center p-2">
                                        <svg class="w-5 h-5 text-green-600 dark:text-green-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        <span class="text-sm font-medium text-green-800 dark:text-green-400"><?= __('admin.dashboard.migration_complete') ?></span>
                                    </div>`;
                                document.getElementById('migrationBanner').className = 'mb-6 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-4';
                                setTimeout(() => document.getElementById('migrationBanner')?.remove(), 5000);
                            }, 1500);
                        } else {
                            throw new Error(data.error || 'Migration failed');
                        }
                    } catch (e) {
                        console.error('[Dashboard] Migration error:', e);
                        resultSpan.textContent = '✗ ' + e.message;
                        resultSpan.className = 'ml-3 text-sm text-red-600 dark:text-red-400 font-medium';
                        btn.disabled = false;
                        btn.innerHTML = '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg><?= __('admin.dashboard.retry_migration') ?>';
                    }
                }
                </script>
                <?php endif; ?>

                <!-- Status Banner -->
                <?php if ($dbConnected): ?>
                <div class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg p-4 mb-6">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-green-800 font-medium">시스템 정상 작동 중</span>
                        <span class="text-green-600 ml-2 text-sm">데이터베이스 연결 성공</span>
                    </div>
                </div>
                <?php else: ?>
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-red-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-red-800 font-medium">데이터베이스 연결 실패</span>
                        <span class="text-red-600 ml-2 text-sm"><?php echo htmlspecialchars($dbError ?? ''); ?></span>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 transition-colors">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">오늘 예약</p>
                                <p class="text-3xl font-bold text-zinc-900 dark:text-white mt-1"><?php echo $todayReservations ?? 0; ?></p>
                            </div>
                            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 transition-colors">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">전체 회원</p>
                                <p class="text-3xl font-bold text-zinc-900 dark:text-white mt-1"><?php echo $totalUsers ?? 0; ?></p>
                            </div>
                            <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 transition-colors">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">등록 서비스</p>
                                <p class="text-3xl font-bold text-zinc-900 dark:text-white mt-1"><?php echo $totalServices ?? 0; ?></p>
                            </div>
                            <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 transition-colors">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">시스템 버전</p>
                                <?php
                                    $vj = json_decode(file_get_contents(BASE_PATH . '/version.json'), true);
                                    $curVer = $vj['version'] ?? '0.0.0';
                                ?>
                                <p class="text-3xl font-bold text-zinc-900 dark:text-white mt-1"><?= htmlspecialchars($curVer) ?></p>
                            </div>
                            <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 mb-8 transition-colors">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">빠른 작업</h2>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <a href="<?php echo $adminUrl; ?>/reservations/new" class="flex items-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            <span class="font-medium text-zinc-900 dark:text-white">예약 등록</span>
                        </a>
                        <a href="<?php echo $adminUrl; ?>/services/new" class="flex items-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/30 transition">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            <span class="font-medium text-zinc-900 dark:text-white">서비스 추가</span>
                        </a>
                        <a href="<?php echo $adminUrl; ?>/members" class="flex items-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-900/30 transition">
                            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            <span class="font-medium text-zinc-900 dark:text-white">회원 관리</span>
                        </a>
                        <a href="<?php echo $adminUrl; ?>/settings" class="flex items-center p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg hover:bg-orange-100 dark:hover:bg-orange-900/30 transition">
                            <svg class="w-6 h-6 text-orange-600 dark:text-orange-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span class="font-medium text-zinc-900 dark:text-white">시스템 설정</span>
                        </a>
                    </div>
                </div>

                <!-- System Info -->
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 transition-colors">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">시스템 정보</h2>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <span class="text-zinc-500 dark:text-zinc-400">PHP 버전</span>
                            <p class="font-medium text-zinc-900 dark:text-white"><?php echo PHP_VERSION; ?></p>
                        </div>
                        <div>
                            <span class="text-zinc-500 dark:text-zinc-400">환경</span>
                            <p class="font-medium text-zinc-900 dark:text-white"><?php echo $_ENV['APP_ENV'] ?? 'local'; ?></p>
                        </div>
                        <div>
                            <span class="text-zinc-500 dark:text-zinc-400">타임존</span>
                            <p class="font-medium text-zinc-900 dark:text-white"><?php echo $_ENV['APP_TIMEZONE'] ?? 'Asia/Seoul'; ?></p>
                        </div>
                        <div>
                            <span class="text-zinc-500 dark:text-zinc-400">디버그 모드</span>
                            <p class="font-medium text-zinc-900 dark:text-white"><?php echo ($_ENV['APP_DEBUG'] ?? 'false') === 'true' ? '활성화' : '비활성화'; ?></p>
                        </div>
                    </div>
                </div>

                <!-- 예약 현황 캘린더 (풀 캘린더) -->
                <?php if ($dbConnected): ?>
                <?php include __DIR__ . '/dashboard-calendar.php'; ?>
                <?php endif; ?>

            </div>
        </main>
    </div>

    <!-- TopBar scripts are included in admin-topbar.php -->

    <?php include __DIR__ . '/partials/pwa-scripts.php'; ?>
</body>
</html>
