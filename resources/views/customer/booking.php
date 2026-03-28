<?php
/**
 * RezlyX Booking Page
 * - 서비스 선택 → (스태프 선택) → 날짜/시간 → 고객정보 → 확인/제출
 */

require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
use RzxLib\Core\Auth\Auth;

$_isWidgetMode = !empty($_GET['widget_mode']);
// 위젯 모드에서는 $config가 위젯 설정이므로 사이트 설정은 글로벌에서 가져옴
$_siteConfig = (isset($config['app_url']) || isset($config['app_name'])) ? $config : ($GLOBALS['config'] ?? []);
$pageTitle = ($_siteConfig['app_name'] ?? 'RezlyX') . ' - ' . __('common.nav.booking');
if (!isset($baseUrl)) $baseUrl = $_siteConfig['app_url'] ?? '';
$appName = $_siteConfig['app_name'] ?? 'RezlyX';

if (!isset($isLoggedIn)) $isLoggedIn = Auth::check();
if (!isset($currentUser)) $currentUser = $isLoggedIn ? Auth::user() : null;

$services = [];

try {
    if (!isset($pdo)) {
        $pdo = new PDO(
            'mysql:host=' . ($_ENV['DB_HOST'] ?? 'localhost') . ';dbname=' . ($_ENV['DB_DATABASE'] ?? 'rezlyx') . ';charset=utf8mb4',
            $_ENV['DB_USERNAME'] ?? 'root',
            $_ENV['DB_PASSWORD'] ?? '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }
    $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

    // 서비스 로드 (카테고리 JOIN)
    $services = $pdo->query("SELECT s.*, c.name as category_name FROM {$prefix}services s LEFT JOIN {$prefix}service_categories c ON s.category_id = c.id WHERE s.is_active = 1 ORDER BY s.sort_order, s.name")->fetchAll(PDO::FETCH_ASSOC);

    // 서비스 번역 로드 (rzx_translations)
    $currentLocale = $config['locale'] ?? 'ko';
    $defaultLocale = $siteSettings['default_language'] ?? 'ko';
    $localeChain = array_unique(array_filter([$currentLocale, 'en', $defaultLocale]));
    $lcPlaceholders = implode(',', array_fill(0, count($localeChain), '?'));
    $trStmt = $pdo->prepare("SELECT lang_key, locale, content FROM {$prefix}translations
        WHERE locale IN ({$lcPlaceholders}) AND (lang_key LIKE 'service.%.name' OR lang_key LIKE 'service.%.description')");
    $trStmt->execute(array_values($localeChain));
    $svcTranslations = [];
    while ($tr = $trStmt->fetch(PDO::FETCH_ASSOC)) {
        $svcTranslations[$tr['lang_key']][$tr['locale']] = $tr['content'];
    }

    // 슬롯 설정
    $scheduleEnabled = ($siteSettings['staff_schedule_enabled'] ?? '0') === '1';
    $slotInterval = (int)($siteSettings['booking_slot_interval'] ?? 30);
    if (!in_array($slotInterval, [15, 30, 60])) $slotInterval = 30;

    // 기본 영업시간 로드 (폴백용)
    $businessHours = [];
    $bhStmt = $pdo->query("SELECT day_of_week, is_open, open_time, close_time, break_start, break_end FROM {$prefix}business_hours ORDER BY day_of_week");
    while ($bh = $bhStmt->fetch(PDO::FETCH_ASSOC)) {
        $businessHours[$bh['day_of_week']] = $bh;
    }

    // 스태프 로드 (선택 가능한 스태프 목록)
    $staffs = [];
    $staffStmt = $pdo->query("SELECT id, name, avatar, designation_fee FROM {$prefix}staff WHERE is_active = 1 ORDER BY sort_order, name");
    while ($staff = $staffStmt->fetch(PDO::FETCH_ASSOC)) {
        $staffs[] = $staff;
    }

    // 번들 로드 (선택 가능한 번들 목록)
    $bundles = [];
    try {
        $bundleStmt = $pdo->query("SELECT id, name, slug, description, price, image FROM {$prefix}bundles WHERE is_active = 1 ORDER BY sort_order, name");
        if ($bundleStmt) {
            while ($bundle = $bundleStmt->fetch(PDO::FETCH_ASSOC)) {
                $bundles[] = $bundle;
            }
        }
    } catch (PDOException $e) {
        // 번들 테이블이 없는 경우 무시
    }

    // POST 처리
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        header('Content-Type: application/json; charset=utf-8');
        $input = json_decode(file_get_contents('php://input'), true);

        // AJAX: 가용 슬롯 조회
        if (($input['action'] ?? '') === 'get_available_slots') {
            $reqDate = $input['date'] ?? '';
            $reqStaffId = $input['staff_id'] ?? null;
            $reqDuration = max(15, (int)($input['total_duration'] ?? 60));

            if (!$reqDate) {
                echo json_encode(['success' => false, 'message' => 'Date required']);
                exit;
            }

            $dow = (int)date('w', strtotime($reqDate));
            $openTime = null;
            $closeTime = null;
            $breakStart = null;
            $breakEnd = null;
            $isWorking = false;

            // 1. 오버라이드 확인 (스태프 지정 + 스케줄 ON)
            if ($reqStaffId && $scheduleEnabled) {
                $ovStmt = $pdo->prepare("SELECT is_working, start_time, end_time, break_start, break_end FROM {$prefix}staff_schedule_overrides WHERE staff_id = ? AND override_date = ?");
                $ovStmt->execute([$reqStaffId, $reqDate]);
                $override = $ovStmt->fetch(PDO::FETCH_ASSOC);
                if ($override) {
                    $isWorking = (bool)$override['is_working'];
                    $openTime = $override['start_time'];
                    $closeTime = $override['end_time'];
                    $breakStart = $override['break_start'];
                    $breakEnd = $override['break_end'];
                }
            }

            // 2. 주간 스케줄 (오버라이드 없을 때)
            if ($openTime === null && $reqStaffId && $scheduleEnabled) {
                $schStmt = $pdo->prepare("SELECT is_working, start_time, end_time, break_start, break_end FROM {$prefix}staff_schedules WHERE staff_id = ? AND day_of_week = ?");
                $schStmt->execute([$reqStaffId, $dow]);
                $sch = $schStmt->fetch(PDO::FETCH_ASSOC);
                if ($sch) {
                    $isWorking = (bool)$sch['is_working'];
                    $openTime = $sch['start_time'];
                    $closeTime = $sch['end_time'];
                    $breakStart = $sch['break_start'];
                    $breakEnd = $sch['break_end'];
                }
            }

            // 3. 기본 영업시간 폴백
            if ($openTime === null) {
                $bh = $businessHours[$dow] ?? null;
                if ($bh) {
                    $isWorking = (bool)$bh['is_open'];
                    $openTime = $bh['open_time'];
                    $closeTime = $bh['close_time'];
                    $breakStart = $bh['break_start'];
                    $breakEnd = $bh['break_end'];
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

            // 기존 예약 조회 (해당 날짜 + 스태프)
            $bookedSlots = [];
            $bkQuery = "SELECT start_time, end_time FROM {$prefix}reservations WHERE reservation_date = ? AND status NOT IN ('cancelled', 'no_show')";
            $bkParams = [$reqDate];
            if ($reqStaffId) {
                $bkQuery .= " AND staff_id = ?";
                $bkParams[] = $reqStaffId;
            }
            $bkStmt = $pdo->prepare($bkQuery);
            $bkStmt->execute($bkParams);
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

                // 휴식 시간 겹침 확인
                if ($breakStartMin !== null && $breakEndMin !== null) {
                    if ($m < $breakEndMin && $slotEnd > $breakStartMin) continue;
                }

                $slotTimeStr = sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
                $slotEndStr = sprintf('%02d:%02d:00', intdiv($slotEnd, 60), $slotEnd % 60);
                $slotStartFull = $slotTimeStr . ':00';

                // 기존 예약과 충돌 확인
                $conflict = false;
                foreach ($bookedSlots as $booked) {
                    if ($slotStartFull < $booked['end'] && $slotEndStr > $booked['start']) {
                        $conflict = true;
                        break;
                    }
                }
                if ($conflict) continue;

                // 과거 시간 제외
                if ($reqDate === date('Y-m-d') && $slotTimeStr <= date('H:i')) continue;

                $slots[] = $slotTimeStr;
            }

            echo json_encode(['success' => true, 'slots' => $slots]);
            exit;
        }

        // service_ids: 배열 또는 단일값 호환
        $serviceIds = $input['service_ids'] ?? [];
        if (!is_array($serviceIds) || empty($serviceIds)) {
            echo json_encode(['success' => false, 'message' => __('booking.error.required_fields')]);
            exit;
        }
        // 스태프 ID (booking 페이지에서도 선택 가능하도록 수정)
        $staffId = (int)($input['staff_id'] ?? 0) ?: null;
        $date = $input['date'] ?? '';
        $time = $input['time'] ?? '';
        $name = trim($input['customer_name'] ?? '');
        $phone = trim($input['customer_phone'] ?? '');
        $email = trim($input['customer_email'] ?? '');
        $notes = trim($input['notes'] ?? '');

        if (!$date || !$time || !$name || !$phone) {
            echo json_encode(['success' => false, 'message' => __('booking.error.required_fields')]);
            exit;
        }

        // 선택된 서비스들 조회
        $ph = implode(',', array_fill(0, count($serviceIds), '?'));
        $svcStmt = $pdo->prepare("SELECT id, name, price, duration FROM {$prefix}services WHERE id IN ({$ph}) AND is_active = 1");
        $svcStmt->execute(array_values($serviceIds));
        $selectedServices = $svcStmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($selectedServices)) {
            echo json_encode(['success' => false, 'message' => __('booking.error.invalid_service')]);
            exit;
        }

        // 합산
        $totalPrice = 0;
        $totalDuration = 0;
        foreach ($selectedServices as $s) {
            $totalPrice += (float)$s['price'];
            $totalDuration += (int)$s['duration'];
        }

        // 이 페이지에서는 지명 예약 없음 (스태프 페이지에서 진행)
        $designationFee = 0;
        $finalAmount = $totalPrice;

        // 예약번호 생성
        $reservationNumber = 'RZX' . date('ymd') . strtoupper(bin2hex(random_bytes(3)));

        // end_time 계산 (합산 duration)
        $startDt = new DateTime("$date $time");
        $endDt = clone $startDt;
        $endDt->modify("+{$totalDuration} minutes");

        $userId = $isLoggedIn ? ($currentUser['id'] ?? null) : null;
        $id = bin2hex(random_bytes(4)) . '-' . bin2hex(random_bytes(2)) . '-' . bin2hex(random_bytes(2)) . '-' . bin2hex(random_bytes(2)) . '-' . bin2hex(random_bytes(6));

        // 메인 예약 (service_id 제거 → reservation_services로 관리)
        $sql = "INSERT INTO {$prefix}reservations
            (id, reservation_number, user_id, staff_id, customer_name, customer_phone, customer_email,
             reservation_date, start_time, end_time, total_amount, final_amount, designation_fee, status, source, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'online', ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $id, $reservationNumber, $userId,
            $staffId ?: null,
            $name, $phone, $email ?: null,
            $date, $time . ':00', $endDt->format('H:i:s'),
            $totalPrice, $finalAmount, $designationFee, $notes ?: null,
        ]);

        // 예약-서비스 관계 저장 (service_name 스냅샷 포함)
        $rsStmt = $pdo->prepare("INSERT INTO {$prefix}reservation_services (reservation_id, service_id, service_name, price, duration, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
        $sortIdx = 0;
        foreach ($selectedServices as $s) {
            $rsStmt->execute([$id, $s['id'], $s['name'], $s['price'], $s['duration'], $sortIdx++]);
        }

        echo json_encode([
            'success' => true,
            'message' => __('booking.success'),
            'reservation_number' => $reservationNumber,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

} catch (PDOException $e) {
    if ($config['debug'] ?? false) {
        error_log('Booking page DB error: ' . $e->getMessage());
    }
}

// 통화·가격 표시 설정
$serviceCurrency = $siteSettings['service_currency'] ?? 'KRW';
$priceDisplay = $siteSettings['service_price_display'] ?? 'show';
$_currencySymbols = ['KRW' => '₩', 'USD' => '$', 'JPY' => '¥', 'EUR' => '€', 'CNY' => '¥'];
$currencySymbol = $_currencySymbols[$serviceCurrency] ?? $serviceCurrency;

// 서비스 번역 헬퍼
if (!function_exists('getBookingSvcTranslated')) {
    function getBookingSvcTranslated($svcId, $field, $default) {
        global $svcTranslations, $localeChain;
        $key = "service.{$svcId}.{$field}";
        if (isset($svcTranslations[$key])) {
            foreach ($localeChain as $loc) {
                if (!empty($svcTranslations[$key][$loc])) return $svcTranslations[$key][$loc];
            }
        }
        return $default;
    }
}

// 스텝 정의: 서비스 → 스태프 → 날짜/시간 → 고객정보 → 확인 (5단계)
$totalSteps = 5;

// headExtra에 booking 고유 스타일 추가
$headExtra = '<style>
    .step-active { background-color: #2563eb; color: white; }
    .step-completed { background-color: #22c55e; color: white; }
    .step-inactive { background-color: #e5e7eb; color: #6b7280; }
    .dark .step-inactive { background-color: #3f3f46; color: #a1a1aa; }
</style>';

// === 위젯 기반 렌더링 ===
require_once BASE_PATH . '/rzxlib/Core/Modules/WidgetRenderer.php';
$_bookingWidgetRenderer = null;
try {
    $_bookingWidgetRenderer = new \RzxLib\Core\Modules\WidgetRenderer($pdo, 'booking', $currentLocale, $baseUrl);
} catch (\Throwable $e) {}

$seoContext = ['type' => 'sub', 'subpage_title' => __('common.nav.booking')];
?>

<?php if ($_bookingWidgetRenderer && $_bookingWidgetRenderer->hasWidgets()): ?>
<?= $_bookingWidgetRenderer->renderAll() ?>
<?php else: ?>
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2"><?= __('common.nav.booking') ?></h1>
            <p class="text-gray-600 dark:text-zinc-400"><?= __('booking.select_service_datetime') ?></p>
            <p class="text-sm text-gray-500 dark:text-zinc-500 mt-1"><?= __('booking.staff_designation_guide') ?></p>
            <a href="<?= $baseUrl ?>/staff" class="inline-flex items-center gap-2 mt-3 px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold rounded-lg transition shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                <?= __('booking.go_staff_booking') ?>
            </a>
        </div>

        <!-- Progress Steps (동적) -->
        <div id="progressBar" class="flex items-center justify-center mb-8"></div>

        <!-- Step: 서비스 선택 -->
        <div id="stepService" class="step-panel bg-white dark:bg-zinc-800 rounded-2xl shadow-lg p-6 md:p-8">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6"><?= __('booking.select_service') ?></h2>
            <?php if (empty($services)): ?>
            <div class="text-center py-12">
                <p class="text-gray-500 dark:text-zinc-400"><?= __('booking.no_services') ?></p>
            </div>
            <?php else: ?>

            <!-- 카테고리 필터 -->
            <?php
            $bkCategories = [];
            foreach ($services as $s) {
                $cid = $s['category_id'] ?? '';
                $cname = $s['category_name'] ?? '';
                if ($cid && $cname && !isset($bkCategories[$cid])) {
                    $bkCategories[$cid] = $cname;
                }
            }
            ?>
            <?php if (!empty($bkCategories)): ?>
            <div id="bkCatFilter" class="flex flex-wrap gap-2 mb-4">
                <button type="button" class="bk-cat-btn px-4 py-1.5 text-xs font-medium rounded-full transition-all bg-blue-600 text-white" data-cat=""><?= __('common.all') ?></button>
                <?php foreach ($bkCategories as $cid => $cname): ?>
                <button type="button" class="bk-cat-btn px-4 py-1.5 text-xs font-medium rounded-full transition-all bg-gray-100 dark:bg-zinc-700 text-gray-600 dark:text-zinc-300 hover:bg-gray-200 dark:hover:bg-zinc-600" data-cat="<?= htmlspecialchars($cid) ?>"><?= htmlspecialchars($cname) ?></button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- 번들 선택 (옵션) -->
            <?php if (!empty($bundles)): ?>
            <div class="mb-6 pb-6 border-b border-gray-200 dark:border-zinc-700">
                <h3 class="text-sm font-medium text-gray-700 dark:text-zinc-300 mb-3"><?= __('booking.select_bundle') ?> (<?= __('common.optional') ?>)</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <?php foreach ($bundles as $bdl): ?>
                    <label class="bundle-card cursor-pointer">
                        <input type="radio" name="bundle" value="<?= $bdl['id'] ?>" class="hidden" data-bundle-id="<?= $bdl['id'] ?>">
                        <div class="bundle-item relative rounded-xl border-2 border-gray-200 dark:border-zinc-700 hover:border-amber-400 dark:hover:border-amber-500 p-3 cursor-pointer transition-all overflow-hidden"
                             style="<?php if ($bdl['image']): ?>background-image:url('<?= htmlspecialchars($baseUrl . '/' . $bdl['image']) ?>');background-size:cover;background-position:center<?php endif; ?>">
                            <?php if (!$bdl['image']): ?>
                            <div class="absolute inset-0 bg-gradient-to-br from-amber-100 to-orange-100 dark:from-amber-900/40 dark:to-orange-900/40"></div>
                            <?php endif; ?>
                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/20 to-transparent"></div>
                            <div class="absolute top-2 right-2 w-5 h-5 rounded-full border-2 border-white/70 bg-black/20 flex items-center justify-center transition-all shadow-sm z-10 bundle-check">
                                <svg class="w-3 h-3 text-white hidden bundle-check-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <div class="absolute bottom-0 left-0 right-0 p-2 z-10">
                                <p class="text-xs font-bold text-white drop-shadow-sm"><?= htmlspecialchars($bdl['name']) ?></p>
                                <p class="text-[10px] text-white/80 drop-shadow-sm"><?= __('common.bundle') ?></p>
                            </div>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- 선택 요약 -->
            <div id="bkSelectedSummary" class="hidden mb-4 flex items-center justify-between bg-blue-50 dark:bg-blue-900/20 rounded-lg px-4 py-2">
                <span id="bkSelectedCount" class="text-sm text-blue-700 dark:text-blue-300 font-medium">0</span>
                <div class="text-sm">
                    <span class="text-gray-500 dark:text-zinc-400"><?= __('booking.total_duration') ?>:</span>
                    <span id="bkTotalDuration" class="font-medium text-gray-900 dark:text-white ml-1">0<?= __('common.minutes') ?></span>
                    <?php if ($priceDisplay === 'show'): ?>
                    <span class="mx-2 text-gray-300">|</span>
                    <span id="bkTotalPrice" class="font-bold text-blue-600 dark:text-blue-400"><?= $currencySymbol ?>0</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <?php foreach ($services as $service):
                    $svcName = getBookingSvcTranslated($service['id'], 'name', $service['name']);
                    $svcDesc = getBookingSvcTranslated($service['id'], 'description', $service['description'] ?? '');
                    $svcImage = $service['image'] ?? '';
                    $hasImage = !empty($svcImage);
                    $svcCatId = $service['category_id'] ?? '';
                    $svcCatName = $service['category_name'] ?? '';
                    $svcPrice = (float)($service['price'] ?? 0);
                    $svcDuration = (int)($service['duration'] ?? 60);
                ?>
                <label class="service-card cursor-pointer" data-cat="<?= htmlspecialchars($svcCatId) ?>" data-sname="<?= htmlspecialchars(strtolower($svcName)) ?>">
                    <input type="checkbox" name="service[]" value="<?= $service['id'] ?>" class="hidden"
                           data-name="<?= htmlspecialchars($svcName) ?>"
                           data-price="<?= $svcPrice ?>"
                           data-duration="<?= $svcDuration ?>">
                    <div class="bk-svc-card group relative rounded-xl border-2 border-gray-200 dark:border-zinc-700 hover:border-gray-300 dark:hover:border-zinc-600 hover:shadow-md cursor-pointer transition-all overflow-hidden"
                         style="min-height:150px;<?php if ($hasImage): ?>background-image:url('<?= htmlspecialchars($baseUrl . '/' . $svcImage) ?>');background-size:cover;background-position:center<?php endif; ?>">
                        <?php if (!$hasImage): ?>
                        <div class="absolute inset-0 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-zinc-700 dark:to-zinc-800"></div>
                        <?php endif; ?>
                        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>
                        <div class="absolute inset-0 bg-blue-500/20 hidden bk-overlay"></div>
                        <!-- 선택 체크 -->
                        <div class="absolute top-2 right-2 w-6 h-6 rounded-full border-2 border-white/70 bg-black/20 flex items-center justify-center transition-all shadow-sm z-10 bk-circle">
                            <svg class="w-3.5 h-3.5 text-white hidden bk-check-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <?php if ($svcCatName): ?>
                        <div class="absolute top-2 left-2 z-10">
                            <span class="px-2 py-0.5 text-[10px] font-medium rounded-full bg-black/40 text-white/90 backdrop-blur-sm"><?= htmlspecialchars($svcCatName) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="absolute bottom-0 left-0 right-0 p-3 z-10">
                            <p class="text-sm font-bold text-white truncate drop-shadow-sm"><?= htmlspecialchars($svcName) ?></p>
                            <div class="flex items-center justify-between mt-1">
                                <span class="text-xs text-white/70 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <?= $svcDuration ?><?= __('common.minutes') ?>
                                </span>
                                <?php if ($priceDisplay === 'show'): ?>
                                <span class="text-sm font-bold text-white drop-shadow-sm"><?= $currencySymbol ?><?= number_format($svcPrice) ?></span>
                                <?php elseif ($priceDisplay === 'contact'): ?>
                                <span class="text-xs text-white/80"><?= __('services.settings.general.price_contact') ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
            <div class="flex justify-end mt-6">
                <button type="button" onclick="nextStep()" id="btnServiceNext" class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                    <?= __('common.buttons.next') ?> <svg class="w-4 h-4 inline ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
            <?php endif; ?>
        </div>

        <!-- Step: 스태프 선택 (옵션) -->
        <div id="stepStaff" class="step-panel bg-white dark:bg-zinc-800 rounded-2xl shadow-lg p-6 md:p-8 hidden">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2"><?= __('booking.select_staff') ?></h2>
            <p class="text-sm text-gray-600 dark:text-zinc-400 mb-6"><?= __('booking.staff_select_guide') ?></p>

            <?php if (empty($staffs)): ?>
            <div class="text-center py-12">
                <p class="text-gray-500 dark:text-zinc-400"><?= __('booking.no_staff') ?></p>
            </div>
            <?php else: ?>
            <div class="space-y-3">
                <!-- 스태프 미선택 옵션 -->
                <label class="staff-option flex items-center gap-4 p-4 rounded-lg border-2 border-gray-200 dark:border-zinc-700 hover:border-blue-400 dark:hover:border-blue-500 cursor-pointer transition">
                    <input type="radio" name="staff" value="" class="w-5 h-5" checked>
                    <div class="flex-1">
                        <p class="font-medium text-gray-900 dark:text-white"><?= __('common.no_preference') ?></p>
                        <p class="text-sm text-gray-500 dark:text-zinc-400"><?= __('booking.staff_auto_assign') ?></p>
                    </div>
                </label>

                <!-- 스태프 목록 -->
                <?php foreach ($staffs as $staff): ?>
                <label class="staff-option flex items-center gap-4 p-4 rounded-lg border-2 border-gray-200 dark:border-zinc-700 hover:border-blue-400 dark:hover:border-blue-500 cursor-pointer transition">
                    <input type="radio" name="staff" value="<?= (int)$staff['id'] ?>" class="w-5 h-5">
                    <div class="w-12 h-12 rounded-full shrink-0 bg-gray-300 dark:bg-zinc-700 overflow-hidden">
                        <?php if ($staff['avatar']): ?>
                        <img src="<?= htmlspecialchars($baseUrl . '/' . $staff['avatar']) ?>" alt="<?= htmlspecialchars($staff['name']) ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center text-white bg-blue-500 font-bold"><?= mb_substr($staff['name'], 0, 1) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1">
                        <p class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($staff['name']) ?></p>
                        <?php if ((float)$staff['designation_fee'] > 0): ?>
                        <p class="text-sm text-amber-600 dark:text-amber-400"><?= __('booking.designation_fee') ?> +<?= $currencySymbol ?><?= number_format($staff['designation_fee']) ?></p>
                        <?php else: ?>
                        <p class="text-sm text-gray-500 dark:text-zinc-400"><?= __('booking.staff_available') ?></p>
                        <?php endif; ?>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="flex justify-between mt-6">
                <button type="button" onclick="prevStep()" class="px-6 py-3 border border-gray-300 dark:border-zinc-600 text-gray-700 dark:text-zinc-300 font-semibold rounded-lg hover:bg-gray-50 dark:hover:bg-zinc-700 transition">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg> <?= __('common.buttons.previous') ?>
                </button>
                <button type="button" onclick="nextStep()" class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition">
                    <?= __('common.buttons.next') ?> <svg class="w-4 h-4 inline ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
        </div>

        <!-- Step: 날짜 & 시간 -->
        <div id="stepDatetime" class="step-panel bg-white dark:bg-zinc-800 rounded-2xl shadow-lg p-6 md:p-8 hidden">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6"><?= __('booking.select_datetime') ?></h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-2"><?= __('booking.select_date') ?></label>
                    <input type="date" id="bookingDate" min="<?= date('Y-m-d') ?>"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-2"><?= __('booking.select_time') ?></label>
                    <div id="timeSlots" class="grid grid-cols-3 gap-2 max-h-48 overflow-y-auto"></div>
                </div>
            </div>
            <div class="flex justify-between mt-6">
                <button type="button" onclick="prevStep()" class="px-6 py-3 border border-gray-300 dark:border-zinc-600 text-gray-700 dark:text-zinc-300 font-semibold rounded-lg hover:bg-gray-50 dark:hover:bg-zinc-700 transition">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg> <?= __('common.buttons.previous') ?>
                </button>
                <button type="button" onclick="nextStep()" id="btnDatetimeNext" class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                    <?= __('common.buttons.next') ?> <svg class="w-4 h-4 inline ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
        </div>

        <!-- Step: 고객 정보 -->
        <div id="stepInfo" class="step-panel bg-white dark:bg-zinc-800 rounded-2xl shadow-lg p-6 md:p-8 hidden">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6"><?= __('booking.enter_info') ?></h2>
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1"><?= __('auth.register.name') ?> <span class="text-red-500">*</span></label>
                        <input type="text" id="customerName" required value="<?= $isLoggedIn ? htmlspecialchars($currentUser['name'] ?? '') : '' ?>"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500"
                               placeholder="<?= __('auth.register.name_placeholder') ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1"><?= __('auth.register.phone') ?> <span class="text-red-500">*</span></label>
                        <input type="tel" id="customerPhone" required value="<?= $isLoggedIn ? htmlspecialchars($currentUser['phone'] ?? '') : '' ?>"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500"
                               placeholder="<?= __('auth.register.phone_placeholder') ?>">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1"><?= __('auth.register.email') ?></label>
                    <input type="email" id="customerEmail" value="<?= $isLoggedIn ? htmlspecialchars($currentUser['email'] ?? '') : '' ?>"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500"
                           placeholder="<?= __('auth.register.email_placeholder') ?>">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1"><?= __('booking.notes') ?></label>
                    <textarea id="customerMemo" rows="3"
                              class="w-full px-4 py-3 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500"
                              placeholder="<?= __('booking.notes_placeholder') ?>"></textarea>
                </div>
            </div>
            <div class="flex justify-between mt-6">
                <button type="button" onclick="prevStep()" class="px-6 py-3 border border-gray-300 dark:border-zinc-600 text-gray-700 dark:text-zinc-300 font-semibold rounded-lg hover:bg-gray-50 dark:hover:bg-zinc-700 transition">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg> <?= __('common.buttons.previous') ?>
                </button>
                <button type="button" onclick="nextStep()" class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition">
                    <?= __('booking.confirm_booking') ?> <svg class="w-4 h-4 inline ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
        </div>

        <!-- Step: 확인 & 제출 -->
        <div id="stepConfirm" class="step-panel bg-white dark:bg-zinc-800 rounded-2xl shadow-lg p-6 md:p-8 hidden">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6"><?= __('booking.confirm_info') ?></h2>
            <div class="bg-gray-50 dark:bg-zinc-700/50 rounded-xl p-6 space-y-4">
                <div class="pb-4 border-b dark:border-zinc-600">
                    <span class="text-gray-600 dark:text-zinc-400"><?= __('booking.service.title') ?></span>
                    <div id="confirmService" class="mt-2 space-y-1"></div>
                </div>
                <div id="confirmStaffSection" class="pb-4 border-b dark:border-zinc-600 hidden">
                    <span class="text-gray-600 dark:text-zinc-400"><?= __('booking.select_staff') ?></span>
                    <div id="confirmStaff" class="mt-2 font-semibold text-gray-900 dark:text-white">-</div>
                </div>
                <div class="flex justify-between items-center pb-4 border-b dark:border-zinc-600">
                    <span class="text-gray-600 dark:text-zinc-400"><?= __('booking.date_label') ?></span>
                    <span id="confirmDate" class="font-semibold text-gray-900 dark:text-white">-</span>
                </div>
                <div class="flex justify-between items-center pb-4 border-b dark:border-zinc-600">
                    <span class="text-gray-600 dark:text-zinc-400"><?= __('booking.time_label') ?></span>
                    <span id="confirmTime" class="font-semibold text-gray-900 dark:text-white">-</span>
                </div>
                <div class="flex justify-between items-center pb-4 border-b dark:border-zinc-600">
                    <span class="text-gray-600 dark:text-zinc-400"><?= __('booking.customer') ?></span>
                    <span id="confirmName" class="font-semibold text-gray-900 dark:text-white">-</span>
                </div>
                <div class="flex justify-between items-center pb-4 border-b dark:border-zinc-600">
                    <span class="text-gray-600 dark:text-zinc-400"><?= __('booking.phone') ?></span>
                    <span id="confirmPhone" class="font-semibold text-gray-900 dark:text-white">-</span>
                </div>
                <div class="flex justify-between items-center pt-2">
                    <span class="text-lg font-semibold text-gray-900 dark:text-white"><?= __('booking.total_price') ?></span>
                    <span id="confirmPrice" class="text-2xl font-bold text-blue-600 dark:text-blue-400">-</span>
                </div>
            </div>
            <div class="mt-6 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 mt-0.5 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <p class="text-sm text-amber-700 dark:text-amber-300"><?= __('booking.cancel_policy') ?></p>
                </div>
            </div>
            <div class="flex justify-between mt-6">
                <button type="button" onclick="prevStep()" class="px-6 py-3 border border-gray-300 dark:border-zinc-600 text-gray-700 dark:text-zinc-300 font-semibold rounded-lg hover:bg-gray-50 dark:hover:bg-zinc-700 transition">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg> <?= __('common.buttons.previous') ?>
                </button>
                <button type="button" onclick="submitBooking()" id="submitBtn" class="px-8 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition shadow-lg shadow-blue-500/30">
                    <?= __('booking.complete_booking') ?>
                </button>
            </div>
        </div>

        <!-- 예약 완료 -->
        <div id="stepDone" class="step-panel bg-white dark:bg-zinc-800 rounded-2xl shadow-lg p-8 hidden text-center">
            <div class="w-20 h-20 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2"><?= __('booking.success') ?></h2>
            <p class="text-gray-600 dark:text-zinc-400 mb-2"><?= __('booking.success_desc') ?></p>
            <p class="text-lg font-mono font-bold text-blue-600 dark:text-blue-400 mb-6" id="doneBookingCode"></p>
            <a href="<?= $baseUrl ?>/" class="inline-block px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition"><?= __('common.nav.home') ?></a>
        </div>
    </div>

<!-- 번들 데이터 (JavaScript에서 사용) -->
<script>
window.BUNDLE_DATA = {};
<?php foreach ($bundles as $bdl): ?>
    // 번들의 서비스 목록 로드
    <?php
    try {
        $bundleServicesStmt = $pdo->prepare("SELECT bs.service_id FROM {$prefix}bundle_services bs WHERE bs.bundle_id = ? ORDER BY bs.sort_order");
        $bundleServicesStmt->execute([$bdl['id']]);
        $bundleServiceIds = [];
        while ($bs = $bundleServicesStmt->fetch(PDO::FETCH_ASSOC)) {
            $bundleServiceIds[] = $bs['service_id'];
        }
    } catch (PDOException $e) {
        $bundleServiceIds = [];
    }
    ?>
    window.BUNDLE_DATA['<?= $bdl['id'] ?>'] = <?= json_encode($bundleServiceIds, JSON_UNESCAPED_UNICODE) ?>;
<?php endforeach; ?>
</script>

<?php include BASE_PATH . '/resources/views/customer/booking-js.php'; ?>
<?php endif; ?>

