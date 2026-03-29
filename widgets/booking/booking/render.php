<?php
/**
 * Booking Widget - render.php
 * 예약 폼을 직접 렌더링하는 시스템 위젯 (staff 위젯과 동일 구조)
 *
 * Config: title, subtitle
 * 전달 변수: $pdo, $baseUrl, $locale, $renderer, $config
 */

$wTitle    = $config['title'] ?? '';
$wSubtitle = $config['subtitle'] ?? '';
$baseUrl   = $baseUrl ?? ($config['app_url'] ?? '');
$prefix    = $_ENV['DB_PREFIX'] ?? 'rzx_';
$currentLocale = $locale ?? ($config['locale'] ?? 'ko');

// Auth 체크
if (!class_exists('RzxLib\Core\Auth\Auth')) {
    $authPath = ($_ENV['BASE_PATH'] ?? dirname(__DIR__, 2)) . '/rzxlib/Core/Auth/Auth.php';
    if (file_exists($authPath)) require_once $authPath;
}
$isLoggedIn  = class_exists('RzxLib\Core\Auth\Auth') ? \RzxLib\Core\Auth\Auth::check() : false;
$currentUser = $isLoggedIn && class_exists('RzxLib\Core\Auth\Auth') ? \RzxLib\Core\Auth\Auth::user() : null;

// siteSettings
$siteSettings = $siteSettings ?? ($GLOBALS['siteSettings'] ?? []);

$services = [];
$bkCategories = [];
$svcTranslations = [];
$localeChain = [];

try {
    // 서비스 로드
    $services = $pdo->query("SELECT s.*, c.name as category_name
        FROM {$prefix}services s
        LEFT JOIN {$prefix}service_categories c ON s.category_id = c.id
        WHERE s.is_active = 1 ORDER BY s.sort_order, s.name")->fetchAll(PDO::FETCH_ASSOC);

    // 번들(추천 패키지) 로드
    $bundles = [];
    // bundle_display_name 다국어 처리
    $bundleDisplayName = $siteSettings['bundle_display_name'] ?? '';
    if (function_exists('db_trans')) {
        $_bdnTr = db_trans('bundle_display_name', $currentLocale);
        if ($_bdnTr) $bundleDisplayName = $_bdnTr;
    }
    if (!$bundleDisplayName || $bundleDisplayName === '') $bundleDisplayName = __('bundles.recommended') ?? '추천 패키지';
    $bStmt = $pdo->query("SELECT b.*, GROUP_CONCAT(bi.service_id) as svc_ids, COUNT(bi.service_id) as svc_count, SUM(s.duration) as total_duration
        FROM {$prefix}service_bundles b
        LEFT JOIN {$prefix}service_bundle_items bi ON b.id = bi.bundle_id
        LEFT JOIN {$prefix}services s ON bi.service_id = s.id
        WHERE b.is_active = 1 GROUP BY b.id ORDER BY b.display_order");
    while ($b = $bStmt->fetch(PDO::FETCH_ASSOC)) {
        $b['svc_id_list'] = $b['svc_ids'] ? explode(',', $b['svc_ids']) : [];
        // 이벤트 가격 확인
        $now = date('Y-m-d H:i:s');
        $b['is_event'] = $b['event_price'] && $b['event_price'] > 0 && (!$b['event_start'] || $b['event_start'] <= $now) && (!$b['event_end'] || $b['event_end'] >= $now);
        $b['display_price'] = $b['is_event'] ? $b['event_price'] : $b['bundle_price'];
        // 이미지 경로
        if ($b['image'] && !str_starts_with($b['image'], 'http')) {
            $b['image'] = $baseUrl . '/' . ltrim($b['image'], '/');
        }
        $bundles[] = $b;
    }

    // 번역 로드
    $defaultLocale = $siteSettings['default_language'] ?? 'ko';
    $localeChain = array_unique(array_filter([$currentLocale, 'en', $defaultLocale]));
    $lcPH = implode(',', array_fill(0, count($localeChain), '?'));
    $trStmt = $pdo->prepare("SELECT lang_key, locale, content FROM {$prefix}translations
        WHERE locale IN ({$lcPH}) AND (lang_key LIKE 'service.%.name' OR lang_key LIKE 'service.%.description' OR lang_key LIKE 'bundle.%.name' OR lang_key LIKE 'bundle.%.description')");
    $trStmt->execute(array_values($localeChain));
    while ($tr = $trStmt->fetch(PDO::FETCH_ASSOC)) {
        $svcTranslations[$tr['lang_key']][$tr['locale']] = $tr['content'];
    }

    // 설정
    $scheduleEnabled = ($siteSettings['staff_schedule_enabled'] ?? '0') === '1';
    $slotInterval = (int)($siteSettings['booking_slot_interval'] ?? 30);
    if (!in_array($slotInterval, [15, 30, 60])) $slotInterval = 30;

    $businessHours = [];
    $bhStmt = $pdo->query("SELECT day_of_week, is_open, open_time, close_time, break_start, break_end FROM {$prefix}business_hours ORDER BY day_of_week");
    while ($bh = $bhStmt->fetch(PDO::FETCH_ASSOC)) {
        $businessHours[$bh['day_of_week']] = $bh;
    }

    // POST 처리 (AJAX) - 위젯 미리보기 시 건너뜀
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && empty($GLOBALS['_rzx_widget_preview'])) {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') !== false) {
            header('Content-Type: application/json; charset=utf-8');
            $input = json_decode(file_get_contents('php://input') ?: '{}', true);

            // 슬롯 조회
            if (($input['action'] ?? '') === 'get_available_slots') {
                $reqDate = $input['date'] ?? '';
                $reqStaffId = $input['staff_id'] ?? null;
                $reqDuration = max(15, (int)($input['total_duration'] ?? 60));

                if (!$reqDate) { echo json_encode(['success'=>false,'message'=>'Date required']); exit; }

                $dow = (int)date('w', strtotime($reqDate));
                $openTime = $closeTime = $breakStart = $breakEnd = null;
                $isWorking = false;

                if ($reqStaffId && $scheduleEnabled) {
                    $ovStmt = $pdo->prepare("SELECT is_working, start_time, end_time, break_start, break_end FROM {$prefix}staff_schedule_overrides WHERE staff_id=? AND override_date=?");
                    $ovStmt->execute([$reqStaffId, $reqDate]);
                    $ov = $ovStmt->fetch(PDO::FETCH_ASSOC);
                    if ($ov) { $isWorking=(bool)$ov['is_working']; $openTime=$ov['start_time']; $closeTime=$ov['end_time']; $breakStart=$ov['break_start']; $breakEnd=$ov['break_end']; }
                }
                if ($openTime===null && $reqStaffId && $scheduleEnabled) {
                    $schStmt = $pdo->prepare("SELECT is_working, start_time, end_time, break_start, break_end FROM {$prefix}staff_schedules WHERE staff_id=? AND day_of_week=?");
                    $schStmt->execute([$reqStaffId, $dow]);
                    $sch = $schStmt->fetch(PDO::FETCH_ASSOC);
                    if ($sch) { $isWorking=(bool)$sch['is_working']; $openTime=$sch['start_time']; $closeTime=$sch['end_time']; $breakStart=$sch['break_start']; $breakEnd=$sch['break_end']; }
                }
                if ($openTime===null) {
                    $bh = $businessHours[$dow] ?? null;
                    if ($bh) { $isWorking=(bool)$bh['is_open']; $openTime=$bh['open_time']; $closeTime=$bh['close_time']; $breakStart=$bh['break_start']; $breakEnd=$bh['break_end']; }
                    else { $isWorking=true; $openTime='09:00:00'; $closeTime='20:00:00'; }
                }
                if (!$isWorking) { echo json_encode(['success'=>true,'slots'=>[],'message'=>'day_off']); exit; }

                $bookedSlots = [];
                $bkQ = "SELECT start_time, end_time FROM {$prefix}reservations WHERE reservation_date=? AND status NOT IN ('cancelled','no_show')";
                $bkP = [$reqDate];
                if ($reqStaffId) { $bkQ .= " AND staff_id=?"; $bkP[] = $reqStaffId; }
                $bkStmt = $pdo->prepare($bkQ); $bkStmt->execute($bkP);
                while ($bk = $bkStmt->fetch(PDO::FETCH_ASSOC)) { $bookedSlots[] = ['start'=>$bk['start_time'],'end'=>$bk['end_time']]; }

                $slots = [];
                $startMin = (int)substr($openTime,0,2)*60+(int)substr($openTime,3,2);
                $endMin   = (int)substr($closeTime,0,2)*60+(int)substr($closeTime,3,2);
                $brSM = $breakStart ? ((int)substr($breakStart,0,2)*60+(int)substr($breakStart,3,2)) : null;
                $brEM = $breakEnd   ? ((int)substr($breakEnd,0,2)*60+(int)substr($breakEnd,3,2)) : null;

                for ($m=$startMin; $m+$reqDuration<=$endMin; $m+=$slotInterval) {
                    $se = $m + $reqDuration;
                    if ($brSM!==null && $brEM!==null && $m<$brEM && $se>$brSM) continue;
                    $ts = sprintf('%02d:%02d', intdiv($m,60), $m%60);
                    $teFull = sprintf('%02d:%02d:00', intdiv($se,60), $se%60);
                    $tsFull = $ts.':00';
                    $conflict = false;
                    foreach ($bookedSlots as $b) { if ($tsFull<$b['end'] && $teFull>$b['start']) { $conflict=true; break; } }
                    if ($conflict) continue;
                    if ($reqDate===date('Y-m-d') && $ts<=date('H:i')) continue;
                    $slots[] = $ts;
                }
                echo json_encode(['success'=>true,'slots'=>$slots]); exit;
            }

            // 예약 제출
            $serviceIds = $input['service_ids'] ?? [];
            if (!is_array($serviceIds) || empty($serviceIds)) { echo json_encode(['success'=>false,'message'=>__('booking.error.required_fields')]); exit; }
            $date  = $input['date'] ?? '';
            $time  = $input['time'] ?? '';
            $name  = trim($input['customer_name'] ?? '');
            $phone = trim($input['customer_phone'] ?? '');
            $email = trim($input['customer_email'] ?? '');
            $notes = trim($input['notes'] ?? '');
            $bundleId = !empty($input['bundle_id']) ? $input['bundle_id'] : null;
            $bundlePrice = $bundleId ? (float)($input['bundle_price'] ?? 0) : null;
            if (!$date||!$time||!$name||!$phone) { echo json_encode(['success'=>false,'message'=>__('booking.error.required_fields')]); exit; }

            $ph = implode(',', array_fill(0, count($serviceIds), '?'));
            $svcStmt = $pdo->prepare("SELECT id,name,price,duration FROM {$prefix}services WHERE id IN ({$ph}) AND is_active=1");
            $svcStmt->execute(array_values($serviceIds));
            $selSvcs = $svcStmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($selSvcs)) { echo json_encode(['success'=>false,'message'=>__('booking.error.invalid_service')]); exit; }

            // total_amount = 서비스 원래 가격 합계, final_amount = 번들 가격 또는 원래 합계
            $totalPrice=$totalDuration=0;
            foreach ($selSvcs as $s) { $totalPrice+=(float)$s['price']; $totalDuration+=(int)$s['duration']; }

            // 번들이 있으면 DB에서 번들 가격 검증
            if ($bundleId) {
                $bdlStmt = $pdo->prepare("SELECT bundle_price FROM {$prefix}service_bundles WHERE id = ? AND is_active = 1");
                $bdlStmt->execute([$bundleId]);
                $dbBundlePrice = $bdlStmt->fetchColumn();
                if ($dbBundlePrice !== false) {
                    $bundlePrice = (float)$dbBundlePrice;
                }
            }
            $finalAmount = $bundleId && $bundlePrice !== null ? $bundlePrice : $totalPrice;

            $resNum = 'RZX'.date('ymd').strtoupper(bin2hex(random_bytes(3)));
            $startDt = new DateTime("$date $time");
            $endDt = clone $startDt; $endDt->modify("+{$totalDuration} minutes");
            $userId = $isLoggedIn ? ($currentUser['id'] ?? null) : null;
            $id = bin2hex(random_bytes(4)).'-'.bin2hex(random_bytes(2)).'-'.bin2hex(random_bytes(2)).'-'.bin2hex(random_bytes(2)).'-'.bin2hex(random_bytes(6));

            $sql = "INSERT INTO {$prefix}reservations (id,reservation_number,user_id,staff_id,bundle_id,bundle_price,customer_name,customer_phone,customer_email,reservation_date,start_time,end_time,total_amount,final_amount,designation_fee,status,source,notes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'pending','online',?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id,$resNum,$userId,null,$bundleId,$bundlePrice,$name,$phone,$email?:null,$date,$time.':00',$endDt->format('H:i:s'),$totalPrice,$finalAmount,0,$notes?:null]);

            // reservation_services: 서비스 원래 가격 그대로, bundle_id 기록
            $rsStmt = $pdo->prepare("INSERT INTO {$prefix}reservation_services (reservation_id,service_id,service_name,price,duration,sort_order,bundle_id) VALUES (?,?,?,?,?,?,?)");
            $si=0; foreach ($selSvcs as $s) { $rsStmt->execute([$id,$s['id'],$s['name'],$s['price'],$s['duration'],$si++,$bundleId]); }

            // 온라인 결제 활성화 여부 확인 (DB에서 직접 조회)
            $_bwPayStmt = $pdo->prepare("SELECT `value` FROM {$prefix}settings WHERE `key` = 'payment_config'");
            $_bwPayStmt->execute();
            $_bwPayConf = json_decode($_bwPayStmt->fetchColumn() ?: '{}', true) ?: [];
            $_bwPayEnabled = ($_bwPayConf['enabled'] ?? '0') === '1' && !empty($_bwPayConf['public_key']) && !empty($_bwPayConf['secret_key']);
            $response = ['success'=>true,'message'=>__('booking.success'),'reservation_number'=>$resNum,'reservation_id'=>$id];
            if ($_bwPayEnabled) {
                $response['needs_payment'] = true;
                $response['payment_url'] = $baseUrl . '/payment/checkout?reservation_id=' . urlencode($id);
            }
            echo json_encode($response, JSON_UNESCAPED_UNICODE); exit;
        }
    }

} catch (PDOException $e) {
    $services = [];
    error_log('Booking widget DB error: ' . $e->getMessage());
}

// 통화 설정
$serviceCurrency = $siteSettings['service_currency'] ?? 'KRW';
$priceDisplay    = $siteSettings['service_price_display'] ?? 'show';
$_cs = ['KRW'=>'₩','USD'=>'$','JPY'=>'¥','EUR'=>'€','CNY'=>'¥'];
$currencySymbol  = $_cs[$serviceCurrency] ?? $serviceCurrency;

// 번역 헬퍼
if (!function_exists('_bwSvcTr')) {
    function _bwSvcTr($id, $field, $default, $translations, $chain) {
        $key = "service.{$id}.{$field}";
        if (isset($translations[$key])) {
            foreach ($chain as $loc) { if (!empty($translations[$key][$loc])) return $translations[$key][$loc]; }
        }
        return $default;
    }
}

// 카테고리 목록
foreach ($services as $s) {
    $cid = $s['category_id'] ?? ''; $cn = $s['category_name'] ?? '';
    if ($cid && $cn && !isset($bkCategories[$cid])) $bkCategories[$cid] = $cn;
}

// 위젯 빌더 미리보기: 간소화된 HTML 반환
if (!empty($GLOBALS['_rzx_widget_preview'])) {
    $svcCount = count($services);
    $catCount = count($bkCategories);
    ob_start();
    ?>
    <section class="py-8">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900"><?= __('common.nav.booking') ?? '예약하기' ?></h2>
            <p class="text-sm text-gray-500 mt-1"><?= $svcCount ?><?= __('booking.service_count') ?> · <?= $catCount ?><?= __('booking.categories') ?></p>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
        <?php foreach (array_slice($services, 0, 6) as $s):
            $sName = _bwSvcTr($s['id'], 'name', $s['name'], $svcTranslations, $localeChain);
            $sImg = $s['image'] ?? '';
            if ($sImg && !str_starts_with($sImg, 'http')) $sImg = $baseUrl . '/' . ltrim($sImg, '/');
        ?>
            <div class="relative rounded-xl border border-gray-200 overflow-hidden" style="min-height:120px;<?= $sImg ? "background-image:url('{$sImg}');background-size:cover;background-position:center" : '' ?>">
                <?php if ($sImg): ?><div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div><?php endif; ?>
                <div class="absolute bottom-0 left-0 right-0 p-2">
                    <p class="text-xs font-bold <?= $sImg ? 'text-white' : 'text-gray-900' ?>"><?= htmlspecialchars($sName) ?></p>
                    <p class="text-xs <?= $sImg ? 'text-white/70' : 'text-gray-500' ?>"><?= $s['duration'] ?>분 · <?= $currencySymbol ?><?= number_format($s['price']) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if ($svcCount > 6): ?>
            <div class="rounded-xl border border-dashed border-gray-300 flex items-center justify-center" style="min-height:120px">
                <span class="text-sm text-gray-400">+<?= $svcCount - 6 ?> more</span>
            </div>
        <?php endif; ?>
        </div>
        <div class="mt-6 text-center">
            <div class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg">
                <?= __('booking.next') ?> →
            </div>
        </div>
    </div>
    </section>
    <?php
    return ob_get_clean();
}

ob_start();
include __DIR__ . '/booking-html.php';
return ob_get_clean();
