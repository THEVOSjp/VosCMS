<?php
/**
 * 사업장 이벤트 등록/관리 페이지
 * /shop/{slug}/events
 * 1회 등록: ¥10,000 / 기간: 7~30일
 */

require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
if (!\RzxLib\Core\Auth\Auth::check()) {
    header('Location: ' . ($config['app_url'] ?? '') . '/login?redirect=shop/' . urlencode($shopSlug) . '/events');
    exit;
}

$currentUser = \RzxLib\Core\Auth\Auth::user();
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$currentLocale = $config['locale'] ?? (function_exists('current_locale') ? current_locale() : 'ko');

// 플러그인 번역 로드
$_shopLang = @include(BASE_PATH . '/plugins/vos-shop/lang/' . $currentLocale . '/shop.php');
if (!is_array($_shopLang)) $_shopLang = @include(BASE_PATH . '/plugins/vos-shop/lang/ko/shop.php');
if (is_array($_shopLang) && class_exists('\RzxLib\Core\I18n\Translator')) {
    \RzxLib\Core\I18n\Translator::merge('shop', $_shopLang);
}

// 업소 로드
$shop = null;
if (!empty($shopSlug)) {
    $stmt = $pdo->prepare("SELECT * FROM {$prefix}shops WHERE slug = ? LIMIT 1");
    $stmt->execute([$shopSlug]);
    $shop = $stmt->fetch(PDO::FETCH_ASSOC);
}
if (!$shop) { http_response_code(404); include BASE_PATH . '/resources/views/customer/404.php'; return; }

$isOwner = $currentUser['id'] === $shop['user_id'];
$isAdmin = !empty($_SESSION['admin_id']);
if (!$isOwner && !$isAdmin) { http_response_code(403); echo '<div class="max-w-md mx-auto py-16 text-center text-xl font-bold text-zinc-900 dark:text-white">' . (__('common.forbidden') ?? '접근 권한이 없습니다.') . '</div>'; return; }

$EVENT_PRICE = 10000; // ¥10,000
$MIN_DAYS = 7;
$MAX_DAYS = 30;

// AJAX 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_POST['action'] ?? '';

    // 이벤트 등록 (결제 후)
    if ($action === 'create_event') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $discountInfo = trim($_POST['discount_info'] ?? '');
        $durationDays = max($MIN_DAYS, min($MAX_DAYS, (int)($_POST['duration_days'] ?? 7)));

        if (!$title) { echo json_encode(['error' => __('shop.event.error_title') ?? '이벤트 제목을 입력해주세요.']); exit; }

        $startDate = date('Y-m-d H:i:s');
        $endDate = date('Y-m-d H:i:s', strtotime("+{$durationDays} days"));

        // 이미지 업로드
        $imagePath = '';
        if (!empty($_FILES['event_image']) && $_FILES['event_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = BASE_PATH . '/storage/uploads/events/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $ext = pathinfo($_FILES['event_image']['name'], PATHINFO_EXTENSION) ?: 'jpg';
            $filename = 'ev_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($_FILES['event_image']['tmp_name'], $uploadDir . $filename)) {
                $imagePath = '/storage/uploads/events/' . $filename;
            }
        }

        // TODO: 실제 결제 연동 (Stripe/PayPal) — 현재는 payment_status='paid'로 즉시 활성화
        $pdo->prepare("INSERT INTO {$prefix}shop_events (shop_id, title, description, image, discount_info, start_date, end_date, is_active, payment_status, payment_amount, duration_days, paid_at) VALUES (?, ?, ?, ?, ?, ?, ?, 1, 'paid', ?, ?, NOW())")->execute([
            $shop['id'], $title, $description ?: null, $imagePath ?: null, $discountInfo ?: null,
            $startDate, $endDate, $EVENT_PRICE, $durationDays
        ]);

        echo json_encode(['success' => true, 'message' => __('shop.event.create_success') ?? '이벤트가 등록되었습니다.']);
        exit;
    }

    // 이벤트 수정
    if ($action === 'update_event') {
        $eventId = (int)($_POST['event_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $discountInfo = trim($_POST['discount_info'] ?? '');

        if (!$eventId || !$title) { echo json_encode(['error' => __('shop.event.error_title') ?? '이벤트 제목을 입력해주세요.']); exit; }

        // 기존 이벤트 확인
        $evChk = $pdo->prepare("SELECT * FROM {$prefix}shop_events WHERE id = ? AND shop_id = ? AND is_active = 1");
        $evChk->execute([$eventId, $shop['id']]);
        $existing = $evChk->fetch(PDO::FETCH_ASSOC);
        if (!$existing) { echo json_encode(['error' => 'not_found']); exit; }

        // 이미지 업로드
        $imgSql = '';
        $imgParam = [];
        if (!empty($_FILES['event_image']) && $_FILES['event_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = BASE_PATH . '/storage/uploads/events/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $ext = pathinfo($_FILES['event_image']['name'], PATHINFO_EXTENSION) ?: 'jpg';
            $filename = 'ev_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($_FILES['event_image']['tmp_name'], $uploadDir . $filename)) {
                $imgSql = ', image = ?';
                $imgParam = ['/storage/uploads/events/' . $filename];
            }
        }

        $params = [$title, $description ?: null, $discountInfo ?: null];
        $params = array_merge($params, $imgParam, [$eventId, $shop['id']]);
        $pdo->prepare("UPDATE {$prefix}shop_events SET title = ?, description = ?, discount_info = ? {$imgSql} WHERE id = ? AND shop_id = ?")->execute($params);

        echo json_encode(['success' => true, 'message' => __('shop.event.update_success') ?? '이벤트가 수정되었습니다.']);
        exit;
    }

    // 이벤트 삭제 (비활성화)
    if ($action === 'delete_event') {
        $eventId = (int)($_POST['event_id'] ?? 0);
        if ($eventId) {
            $pdo->prepare("UPDATE {$prefix}shop_events SET is_active = 0 WHERE id = ? AND shop_id = ?")->execute([$eventId, $shop['id']]);
        }
        echo json_encode(['success' => true]);
        exit;
    }

    echo json_encode(['error' => 'unknown']); exit;
}

// 기존 이벤트 로드
$events = $pdo->prepare("SELECT * FROM {$prefix}shop_events WHERE shop_id = ? AND is_active = 1 ORDER BY created_at DESC");
$events->execute([$shop['id']]);
$events = $events->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = (__('shop.event.title') ?? '이벤트 관리') . ' - ' . $shop['name'];
$seoContext = ['type' => 'sub', 'subpage_title' => $pageTitle];
?>

<div class="max-w-3xl mx-auto px-4 sm:px-6 py-8">
    <!-- 헤더 -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white"><?= __('shop.event.title') ?? '이벤트 관리' ?></h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400"><?= htmlspecialchars($shop['name']) ?></p>
        </div>
        <a href="<?= $baseUrl ?>/shop/<?= htmlspecialchars($shop['slug']) ?>/edit" class="text-sm text-blue-600 hover:underline">&larr; <?= __('shop.edit.title') ?? '사업장 수정' ?></a>
    </div>

    <!-- 등록 가이드 배너 -->
    <div class="mb-8 bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 border border-amber-200 dark:border-amber-700 rounded-xl p-6">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0 w-12 h-12 bg-amber-100 dark:bg-amber-900/40 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
            </div>
            <div class="flex-1">
                <h2 class="text-lg font-bold text-amber-900 dark:text-amber-200 mb-1"><?= __('shop.event.promo_title') ?? '이벤트로 고객을 끌어보세요!' ?></h2>
                <p class="text-sm text-amber-700 dark:text-amber-400 mb-3"><?= __('shop.event.promo_desc') ?? '이벤트를 등록하면 메인 페이지와 사업장 상세 페이지에 배너가 노출됩니다.' ?></p>
                <div class="flex flex-wrap gap-4 text-sm">
                    <div class="flex items-center gap-1.5">
                        <span class="font-bold text-amber-900 dark:text-amber-200"><?= __('shop.event.price_label') ?? '등록 비용' ?></span>
                        <span class="px-2 py-0.5 bg-amber-200 dark:bg-amber-800 text-amber-800 dark:text-amber-200 font-bold rounded-full text-xs">&yen;<?= number_format($EVENT_PRICE) ?></span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="font-bold text-amber-900 dark:text-amber-200"><?= __('shop.event.duration_label') ?? '노출 기간' ?></span>
                        <span class="text-amber-700 dark:text-amber-400"><?= $MIN_DAYS ?>~<?= $MAX_DAYS ?><?= __('shop.event.days') ?? '일' ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 이벤트 등록 폼 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6 mb-8">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4"><?= __('shop.event.new_event') ?? '새 이벤트 등록' ?></h2>
        <form id="eventForm" enctype="multipart/form-data" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('shop.event.event_title') ?? '이벤트 제목' ?> <span class="text-red-500">*</span></label>
                <input type="text" name="title" required maxlength="200" placeholder="<?= __('shop.event.title_placeholder') ?? '예) 오픈 기념 20% 할인 이벤트' ?>" class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-sm text-zinc-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('shop.event.discount_info') ?? '할인/혜택 정보' ?></label>
                <input type="text" name="discount_info" maxlength="200" placeholder="<?= __('shop.event.discount_placeholder') ?? '예) 전 메뉴 20% OFF, 첫 방문 무료 상담' ?>" class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-sm text-zinc-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('shop.event.description') ?? '상세 설명' ?></label>
                <textarea name="description" rows="3" maxlength="1000" placeholder="<?= __('shop.event.desc_placeholder') ?? '이벤트 상세 내용을 입력해주세요.' ?>" class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-sm text-zinc-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500 resize-none"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('shop.event.banner_image') ?? '배너 이미지' ?></label>
                <p class="text-xs text-zinc-400 mb-2"><?= __('shop.event.banner_hint') ?? '가로형 이미지를 권장합니다. (권장 비율 3:1)' ?></p>
                <div id="evImgPreview" class="hidden mb-2 relative inline-block">
                    <img id="evImgThumb" class="h-24 rounded-lg object-cover">
                    <button type="button" onclick="document.querySelector('[name=event_image]').value='';document.getElementById('evImgPreview').classList.add('hidden')" class="absolute -top-2 -right-2 w-5 h-5 bg-red-500 text-white rounded-full text-xs flex items-center justify-center">&times;</button>
                </div>
                <input type="file" name="event_image" accept="image/*" onchange="if(this.files[0]){var r=new FileReader();r.onload=function(e){document.getElementById('evImgThumb').src=e.target.result;document.getElementById('evImgPreview').classList.remove('hidden')};r.readAsDataURL(this.files[0])}" class="text-sm text-zinc-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100 dark:file:bg-amber-900/30 dark:file:text-amber-300">
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('shop.event.duration') ?? '노출 기간' ?></label>
                <div class="flex items-center gap-3">
                    <input type="range" name="duration_days" min="<?= $MIN_DAYS ?>" max="<?= $MAX_DAYS ?>" value="14" id="evDurationRange" oninput="document.getElementById('evDurationVal').textContent=this.value" class="flex-1 accent-amber-500">
                    <span class="flex-shrink-0 text-sm font-bold text-zinc-900 dark:text-white w-16 text-right"><span id="evDurationVal">14</span><?= __('shop.event.days') ?? '일' ?></span>
                </div>
                <p class="text-xs text-zinc-400 mt-1"><?= __('shop.event.duration_hint') ?? '등록일부터 선택한 기간 동안 노출됩니다.' ?></p>
            </div>

            <!-- 결제 안내 + 등록 버튼 -->
            <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-sm text-zinc-500"><?= __('shop.event.payment_amount') ?? '결제 금액' ?></p>
                        <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">&yen;<?= number_format($EVENT_PRICE) ?></p>
                    </div>
                    <div class="text-right text-xs text-zinc-400">
                        <p><?= __('shop.event.tax_included') ?? '세금 포함' ?></p>
                    </div>
                </div>
                <button type="submit" id="evSubmitBtn" class="w-full py-3 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white font-bold rounded-xl transition flex items-center justify-center gap-2 text-sm shadow-lg shadow-amber-500/25">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    <?= __('shop.event.pay_and_register') ?? '결제하고 이벤트 등록' ?>
                </button>
                <p class="text-[10px] text-zinc-400 text-center mt-2"><?= __('shop.event.payment_note') ?? '결제 완료 후 즉시 이벤트가 노출됩니다. 등록 후 환불은 불가합니다.' ?></p>
            </div>
        </form>
    </div>

    <!-- 등록된 이벤트 목록 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4"><?= __('shop.event.my_events') ?? '내 이벤트' ?> <span class="text-sm font-normal text-zinc-400">(<?= count($events) ?>)</span></h2>

        <?php if (empty($events)): ?>
        <p class="text-sm text-zinc-400 py-8 text-center"><?= __('shop.event.no_events') ?? '등록된 이벤트가 없습니다.' ?></p>
        <?php else: ?>
        <div class="space-y-3" id="eventList">
            <?php foreach ($events as $ev):
                $isExpired = $ev['end_date'] && strtotime($ev['end_date']) < time();
                $isPending = $ev['payment_status'] === 'pending';
                $daysLeft = $ev['end_date'] ? max(0, (int)((strtotime($ev['end_date']) - time()) / 86400)) : null;
            ?>
            <div class="flex items-start gap-4 p-4 rounded-lg border <?= $isExpired ? 'border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 opacity-60' : 'border-amber-200 dark:border-amber-700 bg-amber-50/50 dark:bg-amber-900/10' ?>" data-event-id="<?= $ev['id'] ?>">
                <?php if ($ev['image']): ?>
                <img src="<?= $baseUrl . '/' . ltrim(htmlspecialchars($ev['image']), '/') ?>" class="w-20 h-14 rounded-lg object-cover flex-shrink-0">
                <?php endif; ?>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <p class="text-sm font-bold text-zinc-900 dark:text-white truncate"><?= htmlspecialchars($ev['title']) ?></p>
                        <?php if ($isExpired): ?>
                        <span class="flex-shrink-0 px-1.5 py-0.5 text-[9px] font-bold bg-zinc-200 dark:bg-zinc-700 text-zinc-500 rounded"><?= __('shop.event.expired') ?? '종료' ?></span>
                        <?php elseif ($isPending): ?>
                        <span class="flex-shrink-0 px-1.5 py-0.5 text-[9px] font-bold bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 rounded"><?= __('shop.event.pending') ?? '결제 대기' ?></span>
                        <?php else: ?>
                        <span class="flex-shrink-0 px-1.5 py-0.5 text-[9px] font-bold bg-green-100 dark:bg-green-900/30 text-green-600 rounded"><?= __('shop.event.active') ?? '노출 중' ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($ev['discount_info']): ?>
                    <p class="text-xs text-orange-600 dark:text-orange-400"><?= htmlspecialchars($ev['discount_info']) ?></p>
                    <?php endif; ?>
                    <div class="flex items-center gap-3 mt-1 text-[11px] text-zinc-400">
                        <span><?= date('Y.m.d', strtotime($ev['start_date'])) ?> ~ <?= $ev['end_date'] ? date('Y.m.d', strtotime($ev['end_date'])) : '' ?></span>
                        <?php if (!$isExpired && $daysLeft !== null): ?>
                        <span class="<?= $daysLeft <= 3 ? 'text-red-500 font-bold' : '' ?>">D-<?= $daysLeft ?></span>
                        <?php endif; ?>
                        <span>&yen;<?= number_format((int)$ev['payment_amount']) ?></span>
                    </div>
                </div>
                <?php if (!$isExpired): ?>
                <div class="flex flex-col gap-1 flex-shrink-0">
                    <button type="button" onclick="openEditModal(<?= $ev['id'] ?>, <?= htmlspecialchars(json_encode($ev['title']), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($ev['discount_info'] ?? ''), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($ev['description'] ?? ''), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($ev['image'] ?? ''), ENT_QUOTES) ?>)" class="p-1.5 text-zinc-400 hover:text-blue-500 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 transition" title="<?= __('common.buttons.edit') ?? '수정' ?>">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button type="button" onclick="deleteEvent(<?= $ev['id'] ?>)" class="p-1.5 text-zinc-400 hover:text-red-500 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition" title="<?= __('common.buttons.delete') ?? '삭제' ?>">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- 수정 모달 -->
<div id="editEventModal" class="hidden fixed inset-0 z-[9999] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="closeEditModal()"></div>
    <div class="relative bg-white dark:bg-zinc-800 rounded-xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-5 border-b border-zinc-200 dark:border-zinc-700">
            <h3 class="text-lg font-bold text-zinc-900 dark:text-white"><?= __('shop.event.edit_event') ?? '이벤트 수정' ?></h3>
            <button type="button" onclick="closeEditModal()" class="p-1 text-zinc-400 hover:text-zinc-600 rounded">&times;</button>
        </div>
        <form id="editEventForm" enctype="multipart/form-data" class="p-5 space-y-4">
            <input type="hidden" name="event_id" id="editEvId">
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('shop.event.event_title') ?? '이벤트 제목' ?> <span class="text-red-500">*</span></label>
                <input type="text" name="title" id="editEvTitle" required maxlength="200" class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-sm text-zinc-900 dark:text-white focus:ring-2 focus:ring-amber-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('shop.event.discount_info') ?? '할인/혜택 정보' ?></label>
                <input type="text" name="discount_info" id="editEvDiscount" maxlength="200" class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-sm text-zinc-900 dark:text-white focus:ring-2 focus:ring-amber-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('shop.event.description') ?? '상세 설명' ?></label>
                <textarea name="description" id="editEvDesc" rows="3" maxlength="1000" class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-sm text-zinc-900 dark:text-white focus:ring-2 focus:ring-amber-500 resize-none"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('shop.event.banner_image') ?? '배너 이미지' ?></label>
                <div id="editEvImgPreview" class="hidden mb-2">
                    <img id="editEvImgThumb" class="h-20 rounded-lg object-cover">
                </div>
                <input type="file" name="event_image" accept="image/*" onchange="if(this.files[0]){var r=new FileReader();r.onload=function(e){document.getElementById('editEvImgThumb').src=e.target.result;document.getElementById('editEvImgPreview').classList.remove('hidden')};r.readAsDataURL(this.files[0])}" class="text-sm text-zinc-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100">
                <p class="text-[10px] text-zinc-400 mt-1"><?= __('shop.event.edit_image_hint') ?? '새 이미지를 선택하면 기존 이미지가 교체됩니다.' ?></p>
            </div>
            <div class="flex gap-3 pt-3 border-t border-zinc-200 dark:border-zinc-700">
                <button type="button" onclick="closeEditModal()" class="flex-1 py-2.5 border border-zinc-300 dark:border-zinc-600 text-sm font-medium text-zinc-700 dark:text-zinc-300 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition"><?= __('common.buttons.cancel') ?? '취소' ?></button>
                <button type="submit" id="editEvSubmitBtn" class="flex-1 py-2.5 bg-amber-500 hover:bg-amber-600 text-white text-sm font-bold rounded-lg transition"><?= __('common.buttons.save') ?? '저장' ?></button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('eventForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var btn = document.getElementById('evSubmitBtn');
    if (btn.disabled) return;

    if (!confirm('<?= __('shop.event.confirm_payment') ?? '¥' . number_format($EVENT_PRICE) . '을 결제하고 이벤트를 등록하시겠습니까?' ?>')) return;

    btn.disabled = true;
    btn.innerHTML = '<svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> <?= __('shop.event.processing') ?? '처리 중...' ?>';

    var fd = new FormData(this);
    fd.append('action', 'create_event');

    fetch(location.href, {
        method: 'POST',
        headers: {'X-Requested-With': 'XMLHttpRequest'},
        credentials: 'same-origin',
        body: fd
    }).then(function(r) { return r.json(); }).then(function(data) {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.error || 'Error');
            btn.disabled = false;
            btn.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg> <?= __('shop.event.pay_and_register') ?? '결제하고 이벤트 등록' ?>';
        }
    }).catch(function() {
        alert('Error');
        btn.disabled = false;
        btn.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg> <?= __('shop.event.pay_and_register') ?? '결제하고 이벤트 등록' ?>';
    });
});

function openEditModal(id, title, discount, desc, image) {
    document.getElementById('editEvId').value = id;
    document.getElementById('editEvTitle').value = title || '';
    document.getElementById('editEvDiscount').value = discount || '';
    document.getElementById('editEvDesc').value = desc || '';
    var preview = document.getElementById('editEvImgPreview');
    var thumb = document.getElementById('editEvImgThumb');
    if (image) {
        thumb.src = image.startsWith('/') ? '<?= $baseUrl ?>' + image : image;
        preview.classList.remove('hidden');
    } else {
        preview.classList.add('hidden');
    }
    document.getElementById('editEventModal').classList.remove('hidden');
}
function closeEditModal() {
    document.getElementById('editEventModal').classList.add('hidden');
    document.getElementById('editEventForm').reset();
    document.getElementById('editEvImgPreview').classList.add('hidden');
}

document.getElementById('editEventForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var btn = document.getElementById('editEvSubmitBtn');
    if (btn.disabled) return;
    btn.disabled = true;
    btn.textContent = '<?= __('shop.event.processing') ?? '처리 중...' ?>';

    var fd = new FormData(this);
    fd.append('action', 'update_event');

    fetch(location.href, {
        method: 'POST',
        headers: {'X-Requested-With': 'XMLHttpRequest'},
        credentials: 'same-origin',
        body: fd
    }).then(function(r) { return r.json(); }).then(function(data) {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.error || 'Error');
            btn.disabled = false;
            btn.textContent = '<?= __('common.buttons.save') ?? '저장' ?>';
        }
    }).catch(function() {
        alert('Error');
        btn.disabled = false;
        btn.textContent = '<?= __('common.buttons.save') ?? '저장' ?>';
    });
});

function deleteEvent(id) {
    if (!confirm('<?= __('shop.event.confirm_delete') ?? '이벤트를 삭제하시겠습니까? 결제 금액은 환불되지 않습니다.' ?>')) return;
    var fd = new FormData();
    fd.append('action', 'delete_event');
    fd.append('event_id', id);
    fetch(location.href, {
        method: 'POST',
        headers: {'X-Requested-With': 'XMLHttpRequest'},
        credentials: 'same-origin',
        body: fd
    }).then(function(r) { return r.json(); }).then(function(data) {
        if (data.success) {
            var el = document.querySelector('[data-event-id="'+id+'"]');
            if (el) el.remove();
        }
    });
}
</script>
