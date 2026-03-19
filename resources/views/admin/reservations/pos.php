<?php
/**
 * POS 페이지 - 당일 현장 관리 화면
 * 상단: 메뉴/버튼 영역
 * 하단 3:1 — 왼쪽: 이용중+대기 고객 카드 / 오른쪽: 탭(당일접수, 예약자리스트, 대기자명단)
 */
include __DIR__ . '/_init.php';

$services = getServices($pdo, $prefix);
$pageTitle = __('reservations.pos') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
$today = date('Y-m-d');
$nowTime = date('H:i:s');

// POS 설정 로드
$posSettings = [];
$psStmt = $pdo->query("SELECT `key`, `value` FROM {$prefix}settings WHERE `key` LIKE 'pos_%'");
while ($psRow = $psStmt->fetch(PDO::FETCH_ASSOC)) $posSettings[$psRow['key']] = $psRow['value'];
$posCardSize = $posSettings['pos_card_size'] ?? 'medium';
$posShowImage = ($posSettings['pos_show_service_image'] ?? '1') === '1';
$posImageOpacity = (int)($posSettings['pos_image_opacity'] ?? 60);
$posShowPrice = ($posSettings['pos_show_price'] ?? '1') === '1';
$posShowPhone = ($posSettings['pos_show_phone'] ?? '1') === '1';
$posDefaultTab = $posSettings['pos_default_tab'] ?? 'cards';
$posAutoRefresh = ($posSettings['pos_auto_refresh'] ?? '0') === '1';
$posRefreshInterval = (int)($posSettings['pos_refresh_interval'] ?? 30);
$posSoundNotify = ($posSettings['pos_sound_notification'] ?? '0') === '1';
$posRequireStaff = ($posSettings['pos_require_staff'] ?? '1') === '1';
$posAutoAssign = ($posSettings['pos_auto_assign'] ?? '0') === '1';

// 카드 크기별 그리드
$posGridClass = match($posCardSize) {
    'small' => 'grid-cols-2 md:grid-cols-3 lg:grid-cols-4',
    'large' => 'grid-cols-1 md:grid-cols-2',
    default => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3',
};

// 서비스 목록 (접수 컴포넌트용)
$calServices = $pdo->query("SELECT s.id, s.name, s.description, s.duration, s.price, s.image, s.category_id, c.name as category_name FROM {$prefix}services s LEFT JOIN {$prefix}service_categories c ON s.category_id = c.id WHERE s.is_active = 1 ORDER BY s.sort_order ASC, s.name ASC")->fetchAll(PDO::FETCH_ASSOC);

// 오늘 전체 예약 (junction table 기반 + 서비스 이미지 + 회원 프로필 + 회원 등급)
try {
    $posServiceNameExpr = "(SELECT GROUP_CONCAT(rs.service_name ORDER BY rs.sort_order SEPARATOR ', ') FROM {$prefix}reservation_services rs WHERE rs.reservation_id = r.id)";
    $pdo->query("SELECT service_name FROM {$prefix}reservation_services LIMIT 0");
} catch (PDOException $e) {
    $posServiceNameExpr = "(SELECT GROUP_CONCAT(s.name SEPARATOR ', ') FROM {$prefix}reservation_services rs JOIN {$prefix}services s ON rs.service_id = s.id WHERE rs.reservation_id = r.id)";
}
$stmtToday = $pdo->prepare("
    SELECT r.*,
        {$posServiceNameExpr} as service_name,
        (SELECT SUM(rs2.duration) FROM {$prefix}reservation_services rs2 WHERE rs2.reservation_id = r.id) as service_duration,
        (SELECT s.image FROM {$prefix}reservation_services rs3 JOIN {$prefix}services s ON rs3.service_id = s.id WHERE rs3.reservation_id = r.id AND s.image IS NOT NULL AND s.image != '' LIMIT 1) as service_image,
        u.profile_image as user_profile_image,
        u.grade_id as user_grade_id,
        u.points_balance as user_points_balance,
        mg.name as grade_name,
        mg.discount_rate as grade_discount_rate,
        mg.point_rate as grade_point_rate,
        mg.color as grade_color,
        st.name as staff_name
    FROM {$prefix}reservations r
    LEFT JOIN {$prefix}users u ON r.user_id = u.id
    LEFT JOIN {$prefix}member_grades mg ON u.grade_id = mg.id
    LEFT JOIN {$prefix}staff st ON r.staff_id = st.id
    WHERE r.reservation_date = ?
    ORDER BY r.start_time ASC
");
$stmtToday->execute([$today]);
$todayAll = $stmtToday->fetchAll(PDO::FETCH_ASSOC);

// 업종별 POS 어댑터 로드
require_once BASE_PATH . '/rzxlib/Core/Modules/BusinessType/PosAdapterInterface.php';
require_once BASE_PATH . '/rzxlib/Core/Modules/BusinessType/CustomerBasedAdapter.php';
require_once BASE_PATH . '/rzxlib/Core/Modules/BusinessType/SpaceBasedAdapter.php';
require_once BASE_PATH . '/rzxlib/Core/Modules/BusinessType/BusinessTypeModule.php';

use RzxLib\Core\Modules\BusinessType\BusinessTypeModule;

$siteCategory = $config['site_category'] ?? '';
$posViewPath = __DIR__;
$posAdapter = BusinessTypeModule::createPosAdapter($siteCategory, $posViewPath, $pdo, $prefix);
$posMode = $posAdapter->getMode();

// 어댑터를 통한 데이터 그룹핑
$posData = $posAdapter->groupReservations($todayAll, $nowTime);
$allCards       = $posData['cards'];
$counts         = $posData['counts'];
$reservationList = $posData['tab_data']['reservations'];
$waitingList     = $posData['tab_data']['waiting'];
$completedCount  = $posData['completed'];

// 스태프 목록 (배정용)
$posStaffList = $pdo->prepare("SELECT id, name, avatar FROM {$prefix}staff WHERE is_active = 1 AND (is_visible = 1 OR is_visible IS NULL) ORDER BY sort_order");
$posStaffList->execute();
$posStaffList = $posStaffList->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/_head.php';
?>

<!-- ═══ 상단: 메뉴 및 버튼 영역 ═══ -->
<div class="flex items-center justify-between mb-4">
    <div class="flex items-center gap-3">
        <h2 class="text-2xl font-bold text-zinc-900 dark:text-white flex items-center">
            <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
            </svg>
            <?= __('reservations.pos') ?>
        </h2>
        <span class="text-sm text-zinc-500 dark:text-zinc-400"><?= date('Y-m-d (D)') ?></span>
        <span id="posClock" class="text-sm font-mono text-blue-600 dark:text-blue-400"><?= date('H:i:s') ?></span>
    </div>
    <div class="flex items-center gap-2 text-sm">
        <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400 rounded-lg font-medium">
            <?= __('reservations.pos_in_service') ?> <?= $counts['in_service'] ?>
        </span>
        <span class="px-2.5 py-1 bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400 rounded-lg font-medium">
            <?= __('reservations.pos_waiting') ?> <?= $counts['waiting'] ?>
        </span>
        <span class="px-2.5 py-1 bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 rounded-lg font-medium">
            <?= __('reservations.pos_total_count') ?> <?= $counts['total'] ?>
        </span>
        <button onclick="location.reload()" class="ml-2 p-1.5 text-zinc-400 hover:text-zinc-600 hover:bg-zinc-200 dark:hover:bg-zinc-700 rounded-lg transition" title="<?= __('reservations.pos_refresh') ?>">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
        </button>
        <a href="<?= $adminUrl ?>/pos/settings" class="p-1.5 text-zinc-400 hover:text-zinc-600 hover:bg-zinc-200 dark:hover:bg-zinc-700 rounded-lg transition" title="<?= __('reservations.pos_settings') ?>">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        </a>
    </div>
</div>

<!-- ═══ 3:1 메인 레이아웃 ═══ -->
<div class="flex gap-4" style="height: calc(100vh - 180px);">

    <!-- ━━━ 왼쪽 3/4: 이용중 + 대기 고객 카드 ━━━ -->
    <div class="w-3/4 flex flex-col">
        <?php if (empty($allCards)): ?>
        <div class="flex-1 flex items-center justify-center bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700">
            <div class="text-center">
                <svg class="w-16 h-16 mx-auto text-zinc-200 dark:text-zinc-700 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <p class="text-sm text-zinc-400 dark:text-zinc-500"><?= __('reservations.pos_no_in_service') ?></p>
            </div>
        </div>
        <?php else: ?>
        <div class="flex-1 overflow-y-auto pr-1">
            <div class="grid <?= $posGridClass ?> gap-3">
                <?php foreach ($allCards as $g):
                    // 어댑터를 통한 카드 데이터 준비 + 모드별 카드 뷰 include
                    $cd = $posAdapter->prepareCardData($g, $nowTime);
                    extract($cd);
                    $card = $cd['card'];
                    include $posAdapter->getCardViewPath();
                endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- ━━━ 오른쪽 1/4: 탭 패널 ━━━ -->
    <div class="w-1/4 flex flex-col bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="flex border-b border-zinc-200 dark:border-zinc-700">
            <button onclick="POS.switchTab('checkin')" class="pos-tab flex-1 py-2.5 text-xs font-bold text-center border-b-2 border-blue-500 text-blue-600 dark:text-blue-400" data-tab="checkin">
                <?= __('reservations.pos_tab_checkin') ?>
            </button>
            <button onclick="POS.switchTab('waiting')" class="pos-tab flex-1 py-2.5 text-xs font-bold text-center border-b-2 border-transparent text-zinc-400 hover:text-zinc-600" data-tab="waiting">
                <?= __('reservations.pos_tab_waiting') ?>
                <span class="ml-1 px-1 py-0.5 bg-amber-200 dark:bg-amber-800 text-amber-700 dark:text-amber-300 rounded text-[10px]"><?= $counts['waiting'] ?></span>
            </button>
            <button onclick="POS.switchTab('reservations')" class="pos-tab flex-1 py-2.5 text-xs font-bold text-center border-b-2 border-transparent text-zinc-400 hover:text-zinc-600" data-tab="reservations">
                <?= __('reservations.pos_tab_reservations') ?>
                <span class="ml-1 px-1 py-0.5 bg-zinc-200 dark:bg-zinc-600 text-zinc-600 dark:text-zinc-300 rounded text-[10px]"><?= $counts['reservations'] ?></span>
            </button>
        </div>

        <div class="flex-1 overflow-hidden relative">

            <!-- 탭1: 당일 접수 (예약 접수 모달 버튼) -->
            <div id="posTabCheckin" class="pos-tab-pane absolute inset-0 flex flex-col items-center justify-center p-4">
                <button onclick="POS.openCheckinModal()" class="w-full py-10 bg-blue-50 dark:bg-blue-900/20 border-2 border-dashed border-blue-300 dark:border-blue-600 rounded-xl hover:bg-blue-100 dark:hover:bg-blue-900/30 transition group">
                    <svg class="w-12 h-12 mx-auto text-blue-400 group-hover:text-blue-600 transition mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <p class="text-sm font-bold text-blue-600 dark:text-blue-400"><?= __('reservations.pos_tab_checkin') ?></p>
                    <p class="text-xs text-zinc-400 mt-1"><?= __('reservations.pos_walk_in') ?></p>
                </button>
                <!-- 오늘 요약 -->
                <div class="w-full mt-4 space-y-2 text-xs">
                    <div class="flex justify-between text-zinc-500"><span><?= __('reservations.pos_in_service') ?></span><span class="font-bold text-emerald-600"><?= $counts['in_service'] ?></span></div>
                    <div class="flex justify-between text-zinc-500"><span><?= __('reservations.pos_waiting') ?></span><span class="font-bold text-amber-600"><?= $counts['waiting'] ?></span></div>
                    <div class="flex justify-between text-zinc-500"><span><?= __('reservations.pos_done') ?></span><span class="font-bold text-zinc-600"><?= $completedCount ?></span></div>
                    <div class="flex justify-between text-zinc-500 pt-1 border-t border-zinc-200 dark:border-zinc-700"><span><?= __('reservations.pos_total_count') ?></span><span class="font-bold text-blue-600"><?= $counts['total'] ?></span></div>
                </div>
            </div>

            <!-- 탭2: 대기자 명단 -->
            <div id="posTabWaiting" class="pos-tab-pane absolute inset-0 hidden overflow-y-auto">
                <?php if (empty($waitingList)): ?>
                    <div class="flex items-center justify-center h-full">
                        <p class="text-xs text-zinc-400"><?= __('reservations.pos_no_waiting') ?></p>
                    </div>
                <?php else: ?>
                    <?php $wIdx = 1; foreach ($waitingList as $r): $st = $r['status'] ?? 'pending'; ?>
                    <div class="flex items-center justify-between px-3 py-2.5 border-b border-zinc-100 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 cursor-pointer transition"
                         onclick="POS.showDetail(<?= htmlspecialchars(json_encode($r, JSON_UNESCAPED_UNICODE)) ?>)">
                        <div class="flex items-center gap-2.5">
                            <span class="w-6 h-6 flex items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 text-[10px] font-bold flex-shrink-0"><?= $wIdx ?></span>
                            <div>
                                <p class="text-xs font-medium text-zinc-900 dark:text-white"><?= htmlspecialchars($r['customer_name'] ?? '') ?></p>
                                <p class="text-[10px] text-zinc-400">
                                    <?= substr($r['start_time'] ?? '', 0, 5) ?> · <?= htmlspecialchars($r['service_name'] ?? '') ?>
                                    <?php if (empty($r['staff_id'])): ?>
                                    <span class="ml-1 px-1 py-0.5 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded text-[9px] font-medium"><?= __('reservations.pos_unassigned') ?></span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        <?= statusBadge($st) ?>
                    </div>
                    <?php $wIdx++; endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- 탭3: 예약자 리스트 -->
            <div id="posTabReservations" class="pos-tab-pane absolute inset-0 hidden overflow-y-auto divide-y divide-zinc-100 dark:divide-zinc-700">
                <?php if (empty($reservationList)): ?>
                    <div class="flex items-center justify-center h-full">
                        <p class="text-xs text-zinc-400"><?= __('reservations.pos_no_reservations') ?></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($reservationList as $r):
                        $st = $r['status'] ?? 'pending';
                        $isInSvc = ($st === 'confirmed' && ($r['start_time'] ?? '') <= $nowTime && (($r['end_time'] ?? '23:59:59') >= $nowTime));
                    ?>
                    <div class="flex items-center justify-between px-3 py-2 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 cursor-pointer transition"
                         onclick="POS.showDetail(<?= htmlspecialchars(json_encode($r, JSON_UNESCAPED_UNICODE)) ?>)">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="text-[11px] font-mono text-zinc-400 w-9 flex-shrink-0"><?= substr($r['start_time'] ?? '', 0, 5) ?></span>
                            <?php if ($isInSvc): ?><div class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse flex-shrink-0"></div><?php endif; ?>
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-zinc-900 dark:text-white truncate"><?= htmlspecialchars($r['customer_name'] ?? '') ?></p>
                                <p class="text-[10px] text-zinc-400 truncate"><?= htmlspecialchars($r['service_name'] ?? '') ?></p>
                            </div>
                        </div>
                        <?= statusBadge($st) ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<?php include __DIR__ . '/pos-modals.php'; ?>

<script>
// POS 설정
const posConfig = {
    autoRefresh: <?= $posAutoRefresh ? 'true' : 'false' ?>,
    refreshInterval: <?= $posRefreshInterval ?>,
    soundNotify: <?= $posSoundNotify ? 'true' : 'false' ?>,
    requireStaff: <?= $posRequireStaff ? 'true' : 'false' ?>,
    autoAssign: <?= $posAutoAssign ? 'true' : 'false' ?>,
    showModalImage: <?= ($posSettings['pos_show_modal_image'] ?? '1') === '1' ? 'true' : 'false' ?>,
    modalImageOpacity: <?= (int)($posSettings['pos_modal_image_opacity'] ?? 50) ?>,
    defaultTab: '<?= $posDefaultTab ?>'
};

// 자동 새로고침
if (posConfig.autoRefresh && posConfig.refreshInterval > 0) {
    console.log('[POS] Auto-refresh enabled:', posConfig.refreshInterval, 'seconds');
    setInterval(() => {
        // 모달이 열려있지 않을 때만 새로고침
        const modals = document.querySelectorAll('.fixed.z-50:not(.hidden)');
        if (modals.length === 0) {
            console.log('[POS] Auto-refreshing...');
            location.reload();
        }
    }, posConfig.refreshInterval * 1000);
}

// POS 모드 및 전체 서비스 목록
const posMode = '<?= $posMode ?>';
const posAllServices = <?= json_encode($calServices, JSON_UNESCAPED_UNICODE) ?>;
const posAllStaff = <?= json_encode(
    $pdo->query("SELECT id, name, avatar, designation_fee FROM ${prefix}staff WHERE is_active = 1 AND (is_visible = 1 OR is_visible IS NULL) ORDER BY sort_order ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC),
    JSON_UNESCAPED_UNICODE
) ?>;
</script>

<?php include BASE_PATH . '/resources/views/admin/components/reservation-form-js.php'; ?>
<?php include __DIR__ . '/pos-js.php'; ?>
<?php include __DIR__ . '/pos-service-js.php'; ?>
<?php
// 업종별 추가 JS (공간 중심 등)
$extraJs = $posAdapter->getExtraJsPath();
if ($extraJs && file_exists($extraJs)) {
    include $extraJs;
}
?>
<?php include __DIR__ . '/_foot.php'; ?>
