<?php
/**
 * RezlyX Staff Detail Page - 스태프 상세 + 월간 캘린더 + 슬롯 조회
 */

require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
use RzxLib\Core\Auth\Auth;

$baseUrl = $config['app_url'] ?? '';
$staffId = (int)($routeParams['id'] ?? 0);
$currentLocale = $config['locale'] ?? 'ko';
$isLoggedIn = Auth::check();

if (!$staffId) {
    header('Location: ' . $baseUrl . '/staff');
    exit;
}

// 다국어 헬퍼
function getLocalizedVal($name, $nameI18n, $locale) {
    if (!empty($nameI18n)) {
        $i18n = is_string($nameI18n) ? json_decode($nameI18n, true) : $nameI18n;
        if (is_array($i18n) && !empty($i18n[$locale])) return $i18n[$locale];
    }
    return $name;
}

function getSubNameVal($nameI18n, $locale) {
    if (empty($nameI18n)) return '';
    $i18n = is_string($nameI18n) ? json_decode($nameI18n, true) : $nameI18n;
    if (!is_array($i18n)) return '';
    if ($locale === 'ja' && !empty($i18n['ko'])) return $i18n['ko'];
    if (!empty($i18n['en'])) return $i18n['en'];
    if (!empty($i18n['ja'])) return $i18n['ja'];
    return '';
}

$staff = null;
$staffServices = [];
$schedules = [];
$businessHours = [];

try {
    $pdo = new PDO(
        'mysql:host=' . ($_ENV['DB_HOST'] ?? 'localhost') . ';dbname=' . ($_ENV['DB_DATABASE'] ?? 'rezlyx') . ';charset=utf8mb4',
        $_ENV['DB_USERNAME'] ?? 'root',
        $_ENV['DB_PASSWORD'] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

    $scheduleEnabled = ($siteSettings['staff_schedule_enabled'] ?? '0') === '1';
    $slotInterval = (int)($siteSettings['booking_slot_interval'] ?? 30);
    if (!in_array($slotInterval, [15, 30, 60])) $slotInterval = 30;

    // ========== AJAX 처리 ==========
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json; charset=utf-8');
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';

        // 기본 영업시간
        $bhRows = $pdo->query("SELECT day_of_week, is_open, open_time, close_time, break_start, break_end FROM {$prefix}business_hours ORDER BY day_of_week")->fetchAll(PDO::FETCH_ASSOC);
        $bh = [];
        foreach ($bhRows as $r) $bh[$r['day_of_week']] = $r;

        // ---- 월별 근무일 조회 ----
        if ($action === 'get_month_schedule') {
            $year = (int)($input['year'] ?? date('Y'));
            $month = (int)($input['month'] ?? date('n'));
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

            // 주간 스케줄
            $wkStmt = $pdo->prepare("SELECT day_of_week, is_working, start_time, end_time FROM {$prefix}staff_schedules WHERE staff_id = ?");
            $wkStmt->execute([$staffId]);
            $weekly = [];
            while ($w = $wkStmt->fetch(PDO::FETCH_ASSOC)) $weekly[$w['day_of_week']] = $w;

            // 해당 월 오버라이드
            $ovStmt = $pdo->prepare("SELECT override_date, is_working, start_time, end_time FROM {$prefix}staff_schedule_overrides WHERE staff_id = ? AND override_date BETWEEN ? AND ?");
            $firstDay = sprintf('%04d-%02d-01', $year, $month);
            $lastDay = sprintf('%04d-%02d-%02d', $year, $month, $daysInMonth);
            $ovStmt->execute([$staffId, $firstDay, $lastDay]);
            $overrides = [];
            while ($o = $ovStmt->fetch(PDO::FETCH_ASSOC)) $overrides[$o['override_date']] = $o;

            // 휴일 조회
            $holStmt = $pdo->prepare("SELECT date FROM {$prefix}holidays WHERE date BETWEEN ? AND ?");
            $holStmt->execute([$firstDay, $lastDay]);
            $holidays = [];
            while ($h = $holStmt->fetch(PDO::FETCH_ASSOC)) $holidays[] = $h['date'];

            $days = [];
            $today = date('Y-m-d');
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $date = sprintf('%04d-%02d-%02d', $year, $month, $d);
                $dow = (int)date('w', strtotime($date));
                $working = false;
                $hours = '';

                // 과거 날짜 → 비활성
                if ($date < $today) {
                    $days[] = ['date' => $date, 'working' => false, 'past' => true, 'hours' => ''];
                    continue;
                }

                // 휴일 체크
                if (in_array($date, $holidays)) {
                    $days[] = ['date' => $date, 'working' => false, 'holiday' => true, 'hours' => ''];
                    continue;
                }

                // 오버라이드 → 주간 스케줄 → 영업시간 폴백
                if (isset($overrides[$date])) {
                    $ov = $overrides[$date];
                    $working = (bool)$ov['is_working'];
                    if ($working) $hours = substr($ov['start_time'], 0, 5) . '-' . substr($ov['end_time'], 0, 5);
                } elseif ($scheduleEnabled && isset($weekly[$dow])) {
                    $wk = $weekly[$dow];
                    $working = (bool)$wk['is_working'];
                    if ($working) $hours = substr($wk['start_time'], 0, 5) . '-' . substr($wk['end_time'], 0, 5);
                } elseif (isset($bh[$dow])) {
                    $working = (bool)$bh[$dow]['is_open'];
                    if ($working) $hours = substr($bh[$dow]['open_time'], 0, 5) . '-' . substr($bh[$dow]['close_time'], 0, 5);
                }

                $days[] = ['date' => $date, 'working' => $working, 'hours' => $hours];
            }

            echo json_encode(['success' => true, 'days' => $days, 'year' => $year, 'month' => $month]);
            exit;
        }

        // ---- 날짜별 슬롯 조회 ----
        if ($action === 'get_day_slots') {
            $reqDate = $input['date'] ?? '';
            $reqDuration = max(15, (int)($input['duration'] ?? 60));

            if (!$reqDate || $reqDate < date('Y-m-d')) {
                echo json_encode(['success' => true, 'slots' => []]);
                exit;
            }

            $dow = (int)date('w', strtotime($reqDate));
            $openTime = null;
            $closeTime = null;
            $breakStart = null;
            $breakEnd = null;
            $isWorking = false;

            // 1. 오버라이드
            if ($scheduleEnabled) {
                $ovStmt = $pdo->prepare("SELECT is_working, start_time, end_time, break_start, break_end FROM {$prefix}staff_schedule_overrides WHERE staff_id = ? AND override_date = ?");
                $ovStmt->execute([$staffId, $reqDate]);
                $override = $ovStmt->fetch(PDO::FETCH_ASSOC);
                if ($override) {
                    $isWorking = (bool)$override['is_working'];
                    $openTime = $override['start_time'];
                    $closeTime = $override['end_time'];
                    $breakStart = $override['break_start'];
                    $breakEnd = $override['break_end'];
                }
            }

            // 2. 주간 스케줄
            if ($openTime === null && $scheduleEnabled) {
                $schStmt = $pdo->prepare("SELECT is_working, start_time, end_time, break_start, break_end FROM {$prefix}staff_schedules WHERE staff_id = ? AND day_of_week = ?");
                $schStmt->execute([$staffId, $dow]);
                $sch = $schStmt->fetch(PDO::FETCH_ASSOC);
                if ($sch) {
                    $isWorking = (bool)$sch['is_working'];
                    $openTime = $sch['start_time'];
                    $closeTime = $sch['end_time'];
                    $breakStart = $sch['break_start'];
                    $breakEnd = $sch['break_end'];
                }
            }

            // 3. 기본 영업시간
            if ($openTime === null) {
                $bhItem = $bh[$dow] ?? null;
                if ($bhItem) {
                    $isWorking = (bool)$bhItem['is_open'];
                    $openTime = $bhItem['open_time'];
                    $closeTime = $bhItem['close_time'];
                    $breakStart = $bhItem['break_start'];
                    $breakEnd = $bhItem['break_end'];
                } else {
                    $isWorking = true;
                    $openTime = '09:00:00';
                    $closeTime = '20:00:00';
                }
            }

            if (!$isWorking) {
                echo json_encode(['success' => true, 'slots' => [], 'message' => 'day_off']);
                exit;
            }

            // 기존 예약 조회
            $bookedSlots = [];
            $bkStmt = $pdo->prepare("SELECT start_time, end_time FROM {$prefix}reservations WHERE reservation_date = ? AND staff_id = ? AND status NOT IN ('cancelled', 'no_show')");
            $bkStmt->execute([$reqDate, $staffId]);
            while ($bk = $bkStmt->fetch(PDO::FETCH_ASSOC)) {
                $bookedSlots[] = ['start' => $bk['start_time'], 'end' => $bk['end_time']];
            }

            // 슬롯 생성
            $slots = [];
            $startMin = (int)substr($openTime, 0, 2) * 60 + (int)substr($openTime, 3, 2);
            $endMin = (int)substr($closeTime, 0, 2) * 60 + (int)substr($closeTime, 3, 2);
            $breakStartMin = $breakStart ? ((int)substr($breakStart, 0, 2) * 60 + (int)substr($breakStart, 3, 2)) : null;
            $breakEndMin = $breakEnd ? ((int)substr($breakEnd, 0, 2) * 60 + (int)substr($breakEnd, 3, 2)) : null;

            for ($m = $startMin; $m + $reqDuration <= $endMin; $m += $slotInterval) {
                $slotEnd = $m + $reqDuration;
                if ($breakStartMin !== null && $breakEndMin !== null) {
                    if ($m < $breakEndMin && $slotEnd > $breakStartMin) continue;
                }
                $slotTimeStr = sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
                $slotEndStr = sprintf('%02d:%02d:00', intdiv($slotEnd, 60), $slotEnd % 60);
                $slotStartFull = $slotTimeStr . ':00';

                $conflict = false;
                foreach ($bookedSlots as $booked) {
                    if ($slotStartFull < $booked['end'] && $slotEndStr > $booked['start']) {
                        $conflict = true;
                        break;
                    }
                }
                if ($conflict) continue;
                if ($reqDate === date('Y-m-d') && $slotTimeStr <= date('H:i')) continue;

                $slots[] = $slotTimeStr;
            }

            echo json_encode(['success' => true, 'slots' => $slots]);
            exit;
        }

        echo json_encode(['success' => false, 'message' => 'Unknown action']);
        exit;
    }

    // ========== 페이지 데이터 로드 ==========
    $stmt = $pdo->prepare("SELECT s.*, p.name as position_name, p.name_i18n as position_name_i18n
        FROM {$prefix}staff s
        LEFT JOIN {$prefix}staff_positions p ON s.position_id = p.id
        WHERE s.id = ? AND s.is_active = 1");
    $stmt->execute([$staffId]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$staff) {
        header('Location: ' . $baseUrl . '/staff');
        exit;
    }

    // 담당 서비스
    $stmt = $pdo->prepare("SELECT sv.id, sv.name, sv.slug, sv.description, sv.price, sv.duration, sv.image,
            sc.name as category_name
        FROM {$prefix}staff_services ss
        JOIN {$prefix}services sv ON ss.service_id = sv.id
        LEFT JOIN {$prefix}service_categories sc ON sv.category_id = sc.id
        WHERE ss.staff_id = ? AND sv.is_active = 1
        ORDER BY sv.sort_order, sv.name");
    $stmt->execute([$staffId]);
    $staffServices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 주간 스케줄 (프로필 표시용)
    $stmt = $pdo->prepare("SELECT * FROM {$prefix}staff_schedules WHERE staff_id = ? ORDER BY day_of_week");
    $stmt->execute([$staffId]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $schedules[$row['day_of_week']] = $row;
    }

} catch (PDOException $e) {
    if ($config['debug'] ?? false) {
        error_log('Staff detail DB error: ' . $e->getMessage());
    }
    header('Location: ' . $baseUrl . '/staff');
    exit;
}

$staffName = getLocalizedVal($staff['name'], $staff['name_i18n'], $currentLocale);
$subName = getSubNameVal($staff['name_i18n'], $currentLocale);
$positionLabel = getLocalizedVal($staff['position_name'] ?? '', $staff['position_name_i18n'] ?? null, $currentLocale);
$bio = getLocalizedVal($staff['bio'] ?? '', $staff['bio_i18n'] ?? null, $currentLocale);
$designationFee = (float)($staff['designation_fee'] ?? 0);
$avatarUrl = $staff['avatar'] ?? '';
$bannerUrl = $staff['banner'] ?? '';

$pageTitle = $staffName . ' - ' . ($config['app_name'] ?? 'RezlyX');

// 요일명
$dayLabels = [
    'ko' => ['일', '월', '화', '수', '목', '금', '토'],
    'ja' => ['日', '月', '火', '水', '木', '金', '土'],
    'en' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
];
$days = $dayLabels[$currentLocale] ?? $dayLabels['en'];

include BASE_PATH . '/resources/views/partials/header.php';
?>

    <main class="max-w-4xl mx-auto px-4 py-6">
        <!-- Breadcrumb -->
        <nav class="text-sm text-gray-500 dark:text-zinc-400 mb-4">
            <a href="<?= $baseUrl ?>/staff" class="hover:text-blue-600 dark:hover:text-blue-400"><?= __('staff_page.back_to_list') ?></a>
            <span class="mx-2">&gt;</span>
            <span class="text-gray-900 dark:text-white"><?= htmlspecialchars($staffName) ?></span>
        </nav>

        <!-- Banner -->
        <?php if (!empty($bannerUrl)): ?>
        <div class="w-full h-48 md:h-56 rounded-xl overflow-hidden mb-6">
            <img src="<?= htmlspecialchars($bannerUrl) ?>" alt="" class="w-full h-full object-cover">
        </div>
        <?php endif; ?>

        <!-- Profile Header -->
        <div class="flex flex-col md:flex-row gap-6 mb-8">
            <!-- Avatar -->
            <div class="w-32 md:w-40 flex-shrink-0">
                <div class="aspect-square overflow-hidden bg-gray-100 dark:bg-zinc-800 rounded-xl <?= !empty($bannerUrl) ? '-mt-16 md:-mt-20 relative z-10 border-4 border-white dark:border-zinc-900' : '' ?>">
                    <?php if (!empty($avatarUrl)): ?>
                    <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="<?= htmlspecialchars($staffName) ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-200 to-gray-300 dark:from-zinc-700 dark:to-zinc-800">
                        <span class="text-4xl font-bold text-gray-400 dark:text-zinc-500"><?= mb_substr($staffName, 0, 1) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Info -->
            <div class="flex-1 <?= !empty($bannerUrl) ? 'pt-0 md:pt-2' : '' ?>">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white"><?= htmlspecialchars($staffName) ?></h1>
                <?php if (!empty($subName)): ?>
                <p class="text-gray-500 dark:text-zinc-400 text-sm mt-0.5"><?= htmlspecialchars($subName) ?></p>
                <?php endif; ?>

                <?php if (!empty($positionLabel)): ?>
                <span class="inline-block mt-2 px-3 py-1 text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-full">
                    <?= htmlspecialchars($positionLabel) ?>
                </span>
                <?php endif; ?>

                <?php if ($designationFee > 0): ?>
                <p class="mt-2 text-red-600 dark:text-red-400 font-semibold text-sm">
                    <?= __('staff_page.designation_fee') ?> &yen;<?= number_format($designationFee) ?>
                </p>
                <?php endif; ?>

                <?php if (!empty($bio)): ?>
                <div class="mt-3 text-gray-700 dark:text-zinc-300 text-sm leading-relaxed">
                    <?= nl2br(htmlspecialchars($bio)) ?>
                </div>
                <?php endif; ?>

                <!-- Weekly Schedule Summary -->
                <?php if (!empty($schedules)): ?>
                <div class="mt-4">
                    <div class="flex flex-wrap gap-1.5">
                        <?php for ($d = 0; $d < 7; $d++):
                            $sch = $schedules[$d] ?? null;
                            $isWorking = $sch && $sch['is_working'];
                            $dayColor = $d === 0 ? 'text-red-500' : ($d === 6 ? 'text-blue-500' : 'text-gray-700 dark:text-zinc-300');
                        ?>
                        <div class="text-center px-2 py-1 rounded <?= $isWorking ? 'bg-gray-50 dark:bg-zinc-800' : 'bg-gray-100 dark:bg-zinc-900 opacity-50' ?>">
                            <div class="text-xs font-medium <?= $dayColor ?>"><?= $days[$d] ?></div>
                            <?php if ($isWorking): ?>
                            <div class="text-[10px] text-gray-500 dark:text-zinc-400 mt-0.5">
                                <?= substr($sch['start_time'], 0, 5) ?>-<?= substr($sch['end_time'], 0, 5) ?>
                            </div>
                            <?php else: ?>
                            <div class="text-[10px] text-gray-400 dark:text-zinc-500 mt-0.5"><?= __('staff_page.day_off') ?></div>
                            <?php endif; ?>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Monthly Calendar + Slots Section -->
        <div class="border-t border-gray-200 dark:border-zinc-700 pt-6 mb-8">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4"><?= __('staff_page.schedule_calendar') ?></h2>

            <!-- Calendar Navigation -->
            <div class="flex items-center justify-between mb-3">
                <button id="btnPrevMonth" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-zinc-800 text-gray-600 dark:text-zinc-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <h3 id="calendarTitle" class="text-base font-semibold text-gray-900 dark:text-white"></h3>
                <button id="btnNextMonth" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-zinc-800 text-gray-600 dark:text-zinc-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>

            <!-- Calendar Grid -->
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 overflow-hidden">
                <!-- Day Headers -->
                <div class="grid grid-cols-7 text-center text-xs font-medium border-b border-gray-200 dark:border-zinc-700">
                    <?php for ($d = 0; $d < 7; $d++):
                        $hColor = $d === 0 ? 'text-red-500' : ($d === 6 ? 'text-blue-500' : 'text-gray-500 dark:text-zinc-400');
                    ?>
                    <div class="py-2 <?= $hColor ?>"><?= $days[$d] ?></div>
                    <?php endfor; ?>
                </div>
                <!-- Calendar Body -->
                <div id="calendarBody" class="grid grid-cols-7 text-center">
                    <!-- JS fills this -->
                </div>
            </div>

            <!-- Time Slots -->
            <div id="slotsSection" class="mt-4 hidden">
                <h3 id="slotsTitle" class="text-sm font-semibold text-gray-900 dark:text-white mb-3"></h3>
                <div id="slotsGrid" class="flex flex-wrap gap-2">
                    <!-- JS fills this -->
                </div>
                <div id="slotsEmpty" class="hidden text-sm text-gray-500 dark:text-zinc-400 py-4 text-center">
                    <?= __('staff_page.no_available_slots') ?>
                </div>
                <div id="slotsLoading" class="hidden text-sm text-gray-500 dark:text-zinc-400 py-4 text-center">
                    <svg class="animate-spin h-5 w-5 mx-auto mb-1 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                </div>
            </div>
        </div>

        <!-- Available Services -->
        <?php if (!empty($staffServices)): ?>
        <div class="border-t border-gray-200 dark:border-zinc-700 pt-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4"><?= __('staff_page.available_services') ?></h2>

            <div class="space-y-3">
                <?php foreach ($staffServices as $svc):
                    $svcName = htmlspecialchars($svc['name']);
                    $svcDesc = htmlspecialchars($svc['description'] ?? '');
                    $svcPrice = (float)$svc['price'];
                    $svcDuration = (int)$svc['duration'];
                    $svcImage = $svc['image'] ?? '';
                    $svcCategory = $svc['category_name'] ?? '';
                ?>
                <div class="flex gap-3 p-3 bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 rounded-lg hover:shadow-md transition-shadow">
                    <?php if (!empty($svcImage)): ?>
                    <div class="w-20 h-20 flex-shrink-0 rounded-lg overflow-hidden bg-gray-100 dark:bg-zinc-700">
                        <img src="<?= htmlspecialchars($svcImage) ?>" alt="<?= $svcName ?>" class="w-full h-full object-cover">
                    </div>
                    <?php endif; ?>

                    <div class="flex-1 min-w-0">
                        <?php if (!empty($svcCategory)): ?>
                        <span class="text-xs text-blue-600 dark:text-blue-400 font-medium"><?= htmlspecialchars($svcCategory) ?></span>
                        <?php endif; ?>
                        <h3 class="font-semibold text-gray-900 dark:text-white text-sm"><?= $svcName ?></h3>
                        <div class="flex items-center gap-2 text-xs mt-0.5">
                            <span class="text-red-600 dark:text-red-400 font-bold">&yen;<?= number_format($svcPrice) ?></span>
                            <?php if ($svcDuration > 0): ?>
                            <span class="text-gray-400">|</span>
                            <span class="text-gray-500 dark:text-zinc-400"><?= $svcDuration ?><?= __('staff_page.minutes') ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="flex-shrink-0 flex items-center">
                        <a href="<?= $baseUrl ?>/booking?staff=<?= $staff['id'] ?>&service=<?= $svc['id'] ?>"
                           class="px-3 py-1.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-xs font-medium rounded-lg hover:bg-gray-700 dark:hover:bg-gray-200 transition">
                            <?= __('staff_page.select_service') ?>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Back to list -->
        <div class="mt-8 text-center">
            <a href="<?= $baseUrl ?>/staff" class="text-gray-500 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 text-sm">
                &larr; <?= __('staff_page.back_to_list') ?>
            </a>
        </div>
    </main>

<?php include BASE_PATH . '/resources/views/customer/staff-detail-js.php'; ?>

<?php
include BASE_PATH . '/resources/views/partials/footer.php';
?>
