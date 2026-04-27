<?php
/**
 * Before / After 전체 목록 페이지
 * /styles/before-after — rzx_before_afters 테이블
 */

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$currentLocale = $config['locale'] ?? 'ko';

$_shopLang = @include(BASE_PATH . '/plugins/vos-shop/lang/' . $currentLocale . '/shop.php');
if (!is_array($_shopLang)) $_shopLang = @include(BASE_PATH . '/plugins/vos-shop/lang/ko/shop.php');
if (is_array($_shopLang) && class_exists('\RzxLib\Core\I18n\Translator')) {
    \RzxLib\Core\I18n\Translator::merge('shop', $_shopLang);
}

$_wLang = @include(BASE_PATH . '/widgets/before-after/lang/' . $currentLocale . '.php');
if (!is_array($_wLang)) $_wLang = @include(BASE_PATH . '/widgets/before-after/lang/ko.php');
if (!is_array($_wLang)) $_wLang = [];
$_wt = function($key, $default = '') use ($_wLang) { return $_wLang[$key] ?? $default; };

$pageTitle = $_wt('title', 'Before / After');
$seoContext = ['type' => 'sub', 'subpage_title' => $pageTitle];

// 관리자 확인
$_baIsAdmin = false;
try {
    require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
    if (\RzxLib\Core\Auth\Auth::check()) {
        $_baUser = \RzxLib\Core\Auth\Auth::user();
        $_baIsAdmin = in_array($_baUser['role'] ?? '', ['admin', 'supervisor', 'manager']);
    }
} catch (\Throwable $e) {}

// AJAX: 좋아요/삭제
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json; charset=utf-8');
    require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
    if (!\RzxLib\Core\Auth\Auth::check()) { echo json_encode(['error' => 'login_required']); exit; }
    $userId = \RzxLib\Core\Auth\Auth::user()['id'];
    $action = $_POST['action'] ?? '';
    $baId = (int)($_POST['ba_id'] ?? 0);

    if ($action === 'toggle_like' && $baId) {
        $chk = $pdo->prepare("SELECT id FROM {$prefix}before_after_likes WHERE ba_id = ? AND user_id = ?");
        $chk->execute([$baId, $userId]);
        if ($chk->fetch()) {
            $pdo->prepare("DELETE FROM {$prefix}before_after_likes WHERE ba_id = ? AND user_id = ?")->execute([$baId, $userId]);
            $pdo->prepare("UPDATE {$prefix}before_afters SET like_count = GREATEST(like_count - 1, 0) WHERE id = ?")->execute([$baId]);
            $liked = false;
        } else {
            $pdo->prepare("INSERT IGNORE INTO {$prefix}before_after_likes (ba_id, user_id) VALUES (?, ?)")->execute([$baId, $userId]);
            $pdo->prepare("UPDATE {$prefix}before_afters SET like_count = like_count + 1 WHERE id = ?")->execute([$baId]);
            $liked = true;
        }
        $cnt = (int)$pdo->query("SELECT like_count FROM {$prefix}before_afters WHERE id = {$baId}")->fetchColumn();
        echo json_encode(['success' => true, 'liked' => $liked, 'count' => $cnt]);
        exit;
    }
    if ($action === 'delete' && $baId && $_baIsAdmin) {
        $pdo->prepare("DELETE FROM {$prefix}before_afters WHERE id = ?")->execute([$baId]);
        echo json_encode(['success' => true]);
        exit;
    }
    echo json_encode(['error' => 'unknown']);
    exit;
}

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

$items = [];
$totalCount = 0;

try {
    $totalCount = (int)$pdo->query("SELECT COUNT(*) FROM {$prefix}before_afters WHERE status = 'active'")->fetchColumn();
    $stmt = $pdo->prepare("
        SELECT ba.*, s.name as shop_name, s.slug as shop_slug
        FROM {$prefix}before_afters ba
        LEFT JOIN {$prefix}shops s ON ba.shop_id = s.id
        WHERE ba.status = 'active'
        ORDER BY ba.created_at DESC
        LIMIT {$perPage} OFFSET {$offset}
    ");
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (\Throwable $e) {}

$totalPages = max(1, ceil($totalCount / $perPage));
?>

<style>
.bap-box { position: relative; aspect-ratio: 1/1; overflow: hidden; }
.bap-side { display: grid; grid-template-columns: 1fr 1fr; gap: 2px; position: absolute; inset: 0; }
.bap-side img { width: 100%; height: 100%; object-fit: cover; }
.bap-side.hidden { display: none; }
.bap-drag { position: absolute; inset: 0; display: none; }
.bap-drag.active { display: block; }
.bap-drag .bap-before, .bap-drag .bap-after { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; }
.bap-drag .bap-after { z-index: 2; clip-path: inset(0 0 0 50%); }
.bap-drag .bap-divider { position: absolute; top: 0; bottom: 0; width: 3px; background: #fff; box-shadow: 0 0 10px rgba(0,0,0,0.4); cursor: ew-resize; z-index: 10; left: 50%; transform: translateX(-50%); }
.bap-drag .bap-knob { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 36px; height: 36px; background: #fff; border-radius: 50%; box-shadow: 0 2px 8px rgba(0,0,0,0.25); display: flex; align-items: center; justify-content: center; }
</style>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <a href="<?= $baseUrl ?>/Hair" class="inline-flex items-center gap-1 text-sm text-zinc-500 hover:text-blue-600 transition mb-4">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        <?= __('common.back') ?? '돌아가기' ?>
    </a>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars($pageTitle) ?></h1>
            <p class="text-sm text-zinc-500 mt-1"><?= $_wt('page_subtitle', '시술 전후를 비교해보세요') ?></p>
        </div>
        <a href="<?= $baseUrl ?>/styles/before-after/create" class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <?= $_wt('create', '등록') ?>
        </a>
    </div>

    <?php if (empty($items)): ?>
    <div class="text-center py-16 text-zinc-400">
        <svg class="w-16 h-16 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
        <p><?= $_wt('empty') ?></p>
    </div>
    <?php else: ?>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($items as $item): ?>
        <div class="bg-white dark:bg-zinc-800 rounded-xl overflow-hidden shadow-sm border border-zinc-100 dark:border-zinc-700 group">
            <div class="bap-box">
                <div class="bap-side" id="bap_s_<?= $item['id'] ?>">
                    <div class="relative">
                        <img src="<?= $baseUrl . '/' . ltrim($item['before_image'], '/') ?>" alt="Before">
                        <span class="absolute top-2 left-2 text-[10px] px-2 py-0.5 bg-black/50 text-white rounded-full"><?= $_wt('before') ?></span>
                    </div>
                    <div class="relative">
                        <img src="<?= $baseUrl . '/' . ltrim($item['after_image'], '/') ?>" alt="After">
                        <span class="absolute top-2 right-2 text-[10px] px-2 py-0.5 bg-black/50 text-white rounded-full"><?= $_wt('after') ?></span>
                    </div>
                </div>
                <div class="bap-drag" id="bap_d_<?= $item['id'] ?>">
                    <img src="<?= $baseUrl . '/' . ltrim($item['before_image'], '/') ?>" class="bap-before" alt="Before">
                    <img src="<?= $baseUrl . '/' . ltrim($item['after_image'], '/') ?>" class="bap-after" alt="After">
                    <span class="absolute top-2 left-2 text-[10px] px-2 py-0.5 bg-black/50 text-white rounded-full z-20"><?= $_wt('before') ?></span>
                    <span class="absolute top-2 right-2 text-[10px] px-2 py-0.5 bg-black/50 text-white rounded-full z-20"><?= $_wt('after') ?></span>
                    <div class="bap-divider"><div class="bap-knob"><svg class="w-4 h-4 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg></div></div>
                </div>
                <?php if ($_baIsAdmin): ?>
                <button onclick="event.stopPropagation();baDelete(<?= $item['id'] ?>,this)" class="absolute top-2 right-2 w-7 h-7 bg-red-500/80 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-600 z-30">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
                <?php endif; ?>
            </div>
            <div class="p-3 flex items-center justify-between">
                <div class="flex-1 min-w-0 mr-2">
                    <?php if ($item['content']): ?><p class="text-sm font-medium text-zinc-900 dark:text-white truncate"><?= htmlspecialchars($item['content']) ?></p><?php endif; ?>
                    <p class="text-[11px] text-zinc-400"><?= htmlspecialchars(($item['staff_name'] ?? '') . ($item['shop_name'] ? ' · ' . $item['shop_name'] : '')) ?> · <?= date('Y.m.d', strtotime($item['created_at'])) ?></p>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <button onclick="bapToggle(<?= $item['id'] ?>,this)" class="px-3 py-1.5 text-[11px] font-medium border border-zinc-300 dark:border-zinc-600 rounded-full hover:bg-zinc-50 dark:hover:bg-zinc-700 transition flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                        <span class="bap-lbl"><?= $_wt('compare') ?></span>
                    </button>
                    <span class="text-xs text-zinc-400">♡ <?= $item['like_count'] ?></span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="flex items-center justify-center gap-1 mt-8">
        <?php if ($page > 1): ?><a href="<?= $baseUrl ?>/styles/before-after?page=<?= $page - 1 ?>" class="px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-50 transition"><svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></a><?php endif; ?>
        <?php for ($i = max(1, $page - 3); $i <= min($totalPages, $page + 3); $i++): ?>
        <a href="<?= $baseUrl ?>/styles/before-after?page=<?= $i ?>" class="px-3 py-2 text-sm rounded-lg transition <?= $i === $page ? 'bg-blue-600 text-white' : 'border border-zinc-300 dark:border-zinc-600 hover:bg-zinc-50' ?>"><?= $i ?></a>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?><a href="<?= $baseUrl ?>/styles/before-after?page=<?= $page + 1 ?>" class="px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-50 transition"><svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></a><?php endif; ?>
    </div>
    <p class="text-center text-xs text-zinc-400 mt-2"><?= $totalCount ?>개 · <?= $page ?> / <?= $totalPages ?></p>
    <?php endif; ?>

    <?php endif; ?>
</div>

<script>
var cmpLabel = '<?= $_wt('compare') ?>';
var sideLabel = '<?= $_wt('side_by_side') ?>';
function bapToggle(id, btn) {
    var s = document.getElementById('bap_s_' + id);
    var d = document.getElementById('bap_d_' + id);
    var lbl = btn.querySelector('.bap-lbl');
    if (d.classList.contains('active')) {
        d.classList.remove('active'); s.classList.remove('hidden');
        if (lbl) lbl.textContent = cmpLabel;
    } else {
        s.classList.add('hidden'); d.classList.add('active');
        if (lbl) lbl.textContent = sideLabel;
        initBapDrag(d);
    }
}
function initBapDrag(el) {
    if (el._init) return; el._init = true;
    var div = el.querySelector('.bap-divider'), af = el.querySelector('.bap-after'), dragging = false;
    function mv(x) { var r = el.getBoundingClientRect(); var p = Math.max(0, Math.min(1, (x-r.left)/r.width)); af.style.clipPath='inset(0 0 0 '+(p*100)+'%)'; div.style.left=(p*100)+'%'; }
    div.addEventListener('mousedown', function(e){dragging=true;e.preventDefault()});
    div.addEventListener('touchstart', function(){dragging=true},{passive:true});
    el.addEventListener('mousemove', function(e){if(dragging)mv(e.clientX)});
    el.addEventListener('touchmove', function(e){if(dragging)mv(e.touches[0].clientX)},{passive:true});
    document.addEventListener('mouseup', function(){dragging=false});
    document.addEventListener('touchend', function(){dragging=false});
    el.addEventListener('click', function(e){if(e.target!==div&&!div.contains(e.target))mv(e.clientX)});
}
function baDelete(id, btn) {
    if (!confirm('<?= __('common.msg.confirm_delete') ?? '삭제하시겠습니까?' ?>')) return;
    var fd = new FormData(); fd.append('action','delete'); fd.append('ba_id',id);
    fetch('<?= $baseUrl ?>/styles/before-after', {method:'POST',headers:{'X-Requested-With':'XMLHttpRequest'},body:fd,credentials:'same-origin'})
    .then(function(r){return r.json()}).then(function(d){ if(d.success){var c=btn.closest('.group');if(c)c.remove();} });
}
</script>
