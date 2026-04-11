<?php
/**
 * 스타일 상세 페이지
 * /styles/{id}
 */
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$currentLocale = $config['locale'] ?? 'ko';

$_shopLang = @include(BASE_PATH . '/plugins/vos-shop/lang/' . $currentLocale . '/shop.php');
if (!is_array($_shopLang)) $_shopLang = @include(BASE_PATH . '/plugins/vos-shop/lang/ko/shop.php');
if (is_array($_shopLang) && class_exists('\RzxLib\Core\I18n\Translator')) {
    \RzxLib\Core\I18n\Translator::merge('shop', $_shopLang);
}

$styleId = (int)($styleId ?? 0);
$post = null;
try {
    $stmt = $pdo->prepare("SELECT sp.*, s.name as shop_name, s.slug as shop_slug, u.name as author_name
        FROM {$prefix}style_posts sp
        LEFT JOIN {$prefix}shops s ON sp.shop_id = s.id
        LEFT JOIN {$prefix}users u ON sp.user_id = u.id
        WHERE sp.id = ?");
    $stmt->execute([$styleId]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (\Throwable $e) {}

if (!$post) {
    http_response_code(404);
    include BASE_PATH . '/resources/views/customer/404.php';
    return;
}

$images = json_decode($post['images'] ?? '[]', true) ?: [];
$tags = json_decode($post['tags'] ?? '[]', true) ?: [];
$pageTitle = ($post['content'] ? mb_substr($post['content'], 0, 30) : __('shop.stylebook.title') ?? '스타일') . ' - ' . ($config['app_name'] ?? 'RezlyX');
$seoContext = ['type' => 'sub', 'subpage_title' => $pageTitle];

// 로그인 확인
$isLoggedIn = false;
$currentUserId = null;
try {
    require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
    if (\RzxLib\Core\Auth\Auth::check()) {
        $isLoggedIn = true;
        $currentUserId = \RzxLib\Core\Auth\Auth::user()['id'];
    }
} catch (\Throwable $e) {}

$isOwner = $currentUserId && $currentUserId === $post['user_id'];
$isAdmin = false;
if ($isLoggedIn) {
    $role = \RzxLib\Core\Auth\Auth::user()['role'] ?? 'member';
    $isAdmin = in_array($role, ['admin', 'supervisor', 'manager']);
}

// 좋아요 여부
$liked = false;
if ($currentUserId) {
    try {
        $lChk = $pdo->prepare("SELECT id FROM {$prefix}style_likes WHERE style_post_id = ? AND user_id = ?");
        $lChk->execute([$styleId, $currentUserId]);
        $liked = (bool)$lChk->fetch();
    } catch (\Throwable $e) {}
}

// 이름 복호화 헬퍼
$_decryptName = function($name) {
    if ($name && str_starts_with($name, 'enc:') && class_exists('\RzxLib\Core\Helpers\Encryption')) {
        try { return \RzxLib\Core\Helpers\Encryption::decrypt($name); } catch (\Throwable $e) {}
    }
    return $name;
};

// 댓글
$comments = [];
try {
    $cStmt = $pdo->prepare("SELECT c.*, u.name as user_name FROM {$prefix}style_comments c LEFT JOIN {$prefix}users u ON c.user_id = u.id WHERE c.style_post_id = ? AND c.status = 'active' ORDER BY c.created_at ASC");
    $cStmt->execute([$styleId]);
    $comments = $cStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($comments as &$c) { $c['user_name'] = $_decryptName($c['user_name']); }
    unset($c);
} catch (\Throwable $e) {}

// 작성자 이름 복호화
$post['author_name'] = $_decryptName($post['author_name'] ?? '');

// 태그 이름 로드
$tagNames = [];
try {
    $tStmt = $pdo->query("SELECT slug, name FROM {$prefix}style_tags WHERE is_active = 1");
    while ($t = $tStmt->fetch(PDO::FETCH_ASSOC)) {
        $tn = json_decode($t['name'], true) ?: [];
        $tagNames[$t['slug']] = $tn[$currentLocale] ?? $tn['ko'] ?? $t['slug'];
    }
} catch (\Throwable $e) {}
?>

<div class="max-w-3xl mx-auto px-4 sm:px-6 py-8">
    <!-- 네비 -->
    <a href="<?= $baseUrl ?>/styles" class="inline-flex items-center gap-1 text-sm text-zinc-500 dark:text-zinc-400 hover:text-blue-600 transition mb-4">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        <?= __('common.back') ?? '돌아가기' ?>
    </a>

    <!-- 이미지 -->
    <?php if (count($images) === 1):
        $m0 = $images[0];
        $isVid0 = ($m0['media'] ?? '') === 'video' || preg_match('/\.(mp4|mov|webm)$/i', $m0['url']);
    ?>
    <div class="rounded-2xl overflow-hidden bg-zinc-100 dark:bg-zinc-800 mb-4">
        <?php if ($isVid0): ?>
        <video src="<?= $baseUrl . '/' . ltrim($m0['url'], '/') ?>" class="w-full max-h-[600px]" controls playsinline></video>
        <?php else: ?>
        <img src="<?= $baseUrl . '/' . ltrim($m0['url'], '/') ?>" alt="" class="w-full max-h-[600px] object-contain">
        <?php endif; ?>
    </div>
    <?php elseif (count($images) > 1): ?>
    <div class="relative rounded-2xl overflow-hidden bg-zinc-100 dark:bg-zinc-800 mb-4">
        <!-- 슬라이더 -->
        <div id="sdSlider" class="flex transition-transform duration-300" style="will-change:transform">
            <?php foreach ($images as $idx => $img):
                $isVidItem = ($img['media'] ?? '') === 'video' || preg_match('/\.(mp4|mov|webm)$/i', $img['url']);
            ?>
            <div class="w-full flex-shrink-0 relative" style="min-width:100%;aspect-ratio:3/4">
                <?php if ($isVidItem): ?>
                <video src="<?= $baseUrl . '/' . ltrim($img['url'], '/') ?>" class="w-full h-full object-cover" controls playsinline></video>
                <?php else: ?>
                <img src="<?= $baseUrl . '/' . ltrim($img['url'], '/') ?>" alt="" class="w-full h-full object-cover cursor-pointer" onclick="window.open(this.src)">
                <?php endif; ?>
                <?php if (($img['type'] ?? '') !== 'result'): ?>
                <span class="absolute top-3 left-3 text-[10px] px-2 py-0.5 bg-black/50 text-white rounded-full"><?= $img['type'] === 'before' ? 'Before' : 'After' ?></span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <!-- 좌우 버튼 -->
        <button onclick="sdMove(-1)" class="absolute left-2 top-1/2 -translate-y-1/2 w-10 h-10 bg-black/40 text-white rounded-full flex items-center justify-center hover:bg-black/60 transition z-10">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <button onclick="sdMove(1)" class="absolute right-2 top-1/2 -translate-y-1/2 w-10 h-10 bg-black/40 text-white rounded-full flex items-center justify-center hover:bg-black/60 transition z-10">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </button>
        <!-- 인디케이터 -->
        <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-1.5 z-10">
            <?php for ($idx = 0; $idx < count($images); $idx++): ?>
            <button onclick="sdGoTo(<?= $idx ?>)" class="sd-dot w-2 h-2 rounded-full transition <?= $idx === 0 ? 'bg-white' : 'bg-white/50' ?>"></button>
            <?php endfor; ?>
        </div>
        <!-- 카운터 -->
        <span class="absolute top-3 right-3 text-xs px-2 py-0.5 bg-black/50 text-white rounded-full z-10"><span id="sdCurrent">1</span>/<?= count($images) ?></span>
    </div>
    <script>
    (function(){
        var idx=0, total=<?= count($images) ?>, track=document.getElementById('sdSlider');
        function go(i){
            // 이전 슬라이드 동영상 정지
            var prevSlide = track.children[idx];
            if (prevSlide) { var pv = prevSlide.querySelector('video'); if (pv) { pv.pause(); } }
            idx=Math.max(0,Math.min(total-1,i));
            track.style.transform='translateX(-'+(idx*100)+'%)';
            document.getElementById('sdCurrent').textContent=idx+1;
            document.querySelectorAll('.sd-dot').forEach(function(d,j){d.className='sd-dot w-2 h-2 rounded-full transition '+(j===idx?'bg-white':'bg-white/50');});
            // 현재 슬라이드 동영상 자동 재생
            var curSlide = track.children[idx];
            if (curSlide) { var cv = curSlide.querySelector('video'); if (cv) { cv.currentTime = 0; cv.play(); } }
        }
        // 첫 슬라이드가 동영상이면 자동 재생
        var firstVid = track.children[0] && track.children[0].querySelector('video');
        if (firstVid) { firstVid.play(); }
        window.sdMove=function(d){go(idx+d)};
        window.sdGoTo=function(i){go(i)};
        // 스와이프
        var sx=0;
        track.addEventListener('touchstart',function(e){sx=e.touches[0].clientX},{passive:true});
        track.addEventListener('touchend',function(e){var dx=e.changedTouches[0].clientX-sx; if(Math.abs(dx)>50){go(idx+(dx<0?1:-1));}},{passive:true});
    })();
    </script>
    <?php endif; ?>

    <!-- 액션 바 -->
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-4">
            <button onclick="toggleLike(<?= $styleId ?>)" id="likeBtn" class="flex items-center gap-1.5 text-sm <?= $liked ? 'text-red-500' : 'text-zinc-500 dark:text-zinc-400' ?> hover:text-red-500 transition">
                <svg class="w-5 h-5" fill="<?= $liked ? 'currentColor' : 'none' ?>" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                <span id="likeCount"><?= number_format($post['like_count'] ?? 0) ?></span>
            </button>
            <span class="flex items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                <?= $post['comment_count'] ?? 0 ?>
            </span>
        </div>
        <div class="flex items-center gap-2">
            <?php if ($isOwner || $isAdmin): ?>
            <button onclick="if(confirm('<?= __('common.confirm_delete') ?? '삭제하시겠습니까?' ?>'))deletePost(<?= $styleId ?>)" class="text-xs text-red-500 hover:underline"><?= __('common.buttons.delete') ?? '삭제' ?></button>
            <?php endif; ?>
            <?php if ($isLoggedIn && !$isOwner): ?>
            <button onclick="reportPost(<?= $styleId ?>)" class="text-xs text-zinc-400 hover:text-red-500"><?= __('shop.stylebook.report') ?? '신고' ?></button>
            <?php endif; ?>
        </div>
    </div>

    <!-- 태그 -->
    <?php if (!empty($tags)): ?>
    <div class="flex flex-wrap gap-2 mb-4">
        <?php foreach ($tags as $t): ?>
        <a href="<?= $baseUrl ?>/styles?tag=<?= urlencode($t) ?>" class="px-3 py-1 text-sm border border-zinc-200 dark:border-zinc-600 rounded-full text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition">#<?= htmlspecialchars($tagNames[$t] ?? $t) ?></a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- 설명 -->
    <?php if ($post['content']): ?>
    <p class="text-sm text-zinc-800 dark:text-zinc-200 mb-4 whitespace-pre-wrap"><?= nl2br(htmlspecialchars($post['content'])) ?></p>
    <?php endif; ?>

    <!-- 매장/디자이너 -->
    <div class="flex items-center justify-between p-4 bg-zinc-50 dark:bg-zinc-800 rounded-xl mb-6">
        <div class="text-sm">
            <?php if ($post['shop_name']): ?>
            <a href="<?= $baseUrl ?>/shop/<?= htmlspecialchars($post['shop_slug']) ?>" class="font-medium text-zinc-900 dark:text-white hover:text-blue-600"><?= htmlspecialchars($post['shop_name']) ?></a>
            <?php endif; ?>
            <?php if ($post['staff_name']): ?>
            <span class="text-zinc-400"> · <?= htmlspecialchars($post['staff_name']) ?></span>
            <?php endif; ?>
            <p class="text-xs text-zinc-400 mt-0.5"><?= date('Y.m.d', strtotime($post['created_at'])) ?></p>
        </div>
        <?php if ($post['shop_slug']): ?>
        <a href="<?= $baseUrl ?>/shop/<?= htmlspecialchars($post['shop_slug']) ?>" class="px-4 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700 transition"><?= __('shop.stylebook.view_shop') ?? '매장 보기' ?></a>
        <?php endif; ?>
    </div>

    <!-- 댓글 -->
    <div class="border-t border-zinc-200 dark:border-zinc-700 pt-6">
        <h3 class="text-base font-semibold text-zinc-900 dark:text-white mb-4"><?= __('shop.stylebook.comments') ?? '댓글' ?> (<?= count($comments) ?>)</h3>

        <?php if (empty($comments)): ?>
        <p class="text-sm text-zinc-400 py-4 text-center"><?= __('shop.stylebook.no_comments') ?? '아직 댓글이 없습니다.' ?></p>
        <?php else: ?>
        <div class="space-y-3 mb-4" id="commentList">
            <?php foreach ($comments as $c): ?>
            <div class="flex gap-3">
                <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 text-xs font-bold flex-shrink-0"><?= mb_substr($c['user_name'] ?? 'U', 0, 1) ?></div>
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-zinc-900 dark:text-white"><?= htmlspecialchars($c['user_name'] ?? '') ?></span>
                        <span class="text-[10px] text-zinc-400"><?= date('Y.m.d H:i', strtotime($c['created_at'])) ?></span>
                    </div>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-0.5"><?= nl2br(htmlspecialchars($c['content'])) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($isLoggedIn): ?>
        <div class="flex gap-2 mt-4">
            <input type="text" id="commentInput" placeholder="<?= __('shop.stylebook.comment_placeholder') ?? '댓글을 입력하세요' ?>"
                   class="flex-1 px-4 py-2.5 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white"
                   onkeydown="if(event.key==='Enter'){event.preventDefault();addComment()}">
            <button onclick="addComment()" class="px-4 py-2.5 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"><?= __('shop.stylebook.comment_submit') ?? '등록' ?></button>
        </div>
        <?php else: ?>
        <p class="text-sm text-zinc-400 text-center mt-4">
            <a href="<?= $baseUrl ?>/login?redirect=styles/<?= $styleId ?>" class="text-blue-600 hover:underline"><?= __('shop.stylebook.login_to_comment') ?? '로그인 후 댓글을 남길 수 있습니다.' ?></a>
        </p>
        <?php endif; ?>
    </div>
</div>

<script>
var stylesUrl = '<?= $baseUrl ?>/styles';

function toggleLike(postId) {
    <?php if (!$isLoggedIn): ?>
    location.href = '<?= $baseUrl ?>/login?redirect=styles/' + postId;
    return;
    <?php endif; ?>
    var fd = new FormData();
    fd.append('action', 'toggle_like');
    fd.append('post_id', postId);
    fetch(stylesUrl, { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body:fd })
    .then(function(r){return r.json()}).then(function(d) {
        if (d.success) {
            document.getElementById('likeCount').textContent = d.count;
            var btn = document.getElementById('likeBtn');
            var svg = btn.querySelector('svg');
            if (d.liked) { btn.classList.add('text-red-500'); btn.classList.remove('text-zinc-500'); svg.setAttribute('fill','currentColor'); }
            else { btn.classList.remove('text-red-500'); btn.classList.add('text-zinc-500'); svg.setAttribute('fill','none'); }
        }
    });
}

function addComment() {
    var input = document.getElementById('commentInput');
    var content = input.value.trim();
    if (!content) return;
    var fd = new FormData();
    fd.append('action', 'add_comment');
    fd.append('post_id', <?= $styleId ?>);
    fd.append('content', content);
    fetch(stylesUrl, { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body:fd })
    .then(function(r){return r.json()}).then(function(d) {
        if (d.success) { input.value = ''; location.reload(); }
    });
}

function deletePost(postId) {
    var fd = new FormData();
    fd.append('action', 'delete');
    fd.append('post_id', postId);
    fetch(stylesUrl, { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body:fd })
    .then(function(r){return r.json()}).then(function(d) {
        if (d.success) location.href = stylesUrl;
    });
}

function reportPost(postId) {
    if (!confirm('<?= __('shop.stylebook.report_confirm') ?? '이 콘텐츠를 신고하시겠습니까?' ?>')) return;
    var fd = new FormData();
    fd.append('action', 'report');
    fd.append('post_id', postId);
    fetch(stylesUrl, { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body:fd })
    .then(function(r){return r.json()}).then(function(d) {
        if (d.success) alert('<?= __('shop.stylebook.report_done') ?? '신고가 접수되었습니다.' ?>');
    });
}
</script>
