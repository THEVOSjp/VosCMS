<?php
/**
 * 공개 프로필 페이지
 * /profile/{user_id}
 *
 * 입력 변수: $profileUserId (UUID), $pdo, $config, $baseUrl
 */

require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
use RzxLib\Core\Auth\Auth;

$baseUrl = $config['app_url'] ?? '';
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$isLoggedIn = Auth::check();
$me = $isLoggedIn ? Auth::user() : null;
$myId = $me['id'] ?? '';

// 대상 사용자 조회
$ust = $pdo->prepare("SELECT id, email, name, nick_name, profile_image, avatar, bio,
        is_profile_public, allow_messages_from, created_at, last_login_at
    FROM {$prefix}users WHERE id = ? AND is_active = 1 LIMIT 1");
$ust->execute([$profileUserId]);
$profile = $ust->fetch(PDO::FETCH_ASSOC);

if (!$profile) {
    http_response_code(404);
    echo '<div class="max-w-2xl mx-auto py-20 text-center"><h1 class="text-2xl font-bold text-zinc-700">사용자를 찾을 수 없습니다</h1><p class="text-sm text-zinc-400 mt-2">존재하지 않거나 비활성화된 계정입니다.</p></div>';
    return;
}

$isMine = $myId === $profileUserId;
$_nameDec = function_exists('decrypt') ? decrypt($profile['name'] ?? '') : ($profile['name'] ?? '');
$displayName = $profile['nick_name'] ?: ($_nameDec ?: explode('@', $profile['email'])[0]);
$avatarUrl = $profile['profile_image'] ?: $profile['avatar'] ?: '';
if ($avatarUrl && !str_starts_with($avatarUrl, 'http')) $avatarUrl = $baseUrl . $avatarUrl;

// 비공개 프로필 + 본인 아닐 시 제한 노출
$canViewFull = $isMine || (int)$profile['is_profile_public'] === 1;

// 카운트
$cst = $pdo->prepare("SELECT
    (SELECT COUNT(*) FROM {$prefix}user_follows WHERE following_id = ?) AS followers,
    (SELECT COUNT(*) FROM {$prefix}user_follows WHERE follower_id = ?) AS following");
$cst->execute([$profileUserId, $profileUserId]);
$counts = $cst->fetch(PDO::FETCH_ASSOC) ?: ['followers' => 0, 'following' => 0];

// 팔로우 상태
$isFollowing = false;
if ($isLoggedIn && !$isMine) {
    $fst = $pdo->prepare("SELECT 1 FROM {$prefix}user_follows WHERE follower_id = ? AND following_id = ?");
    $fst->execute([$myId, $profileUserId]);
    $isFollowing = (bool)$fst->fetchColumn();
}

// 게시판 활동 카운트 (선택적 — 게시판 테이블 존재 시)
$postCount = 0;
try {
    $bp = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}board_posts WHERE author_id = ? AND status = 'published'");
    $bp->execute([$profileUserId]);
    $postCount = (int)$bp->fetchColumn();
} catch (\Throwable $e) { /* table may not exist or different schema */ }

$pageTitle = $displayName . ' - ' . ($config['app_name'] ?? 'VosCMS');
?>
<div class="max-w-3xl mx-auto px-4 py-8">
    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg overflow-hidden">
        <!-- 커버 -->
        <div class="h-32 bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500"></div>

        <!-- 프로필 헤더 -->
        <div class="px-6 pb-6 -mt-12">
            <div class="flex items-end gap-4">
                <?php if ($avatarUrl): ?>
                <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="" class="w-24 h-24 rounded-full border-4 border-white dark:border-zinc-800 object-cover bg-white">
                <?php else: ?>
                <div class="w-24 h-24 rounded-full border-4 border-white dark:border-zinc-800 bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center">
                    <span class="text-3xl font-bold text-blue-600 dark:text-blue-400"><?= mb_strtoupper(mb_substr($displayName, 0, 1)) ?></span>
                </div>
                <?php endif; ?>
                <div class="flex-1 mb-2 flex items-center justify-between">
                    <div>
                        <h1 class="text-xl font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars($displayName) ?></h1>
                        <p class="text-xs text-zinc-400">#<?= htmlspecialchars(substr($profile['id'], 0, 8)) ?></p>
                    </div>
                    <?php if (!$isMine && $isLoggedIn): ?>
                    <div class="flex gap-2">
                        <button id="btnFollow" onclick="toggleFollow()" data-following="<?= $isFollowing ? '1' : '0' ?>"
                            class="px-4 py-1.5 text-xs font-medium rounded-lg transition <?= $isFollowing ? 'bg-zinc-100 text-zinc-700 hover:bg-red-50 hover:text-red-600 dark:bg-zinc-700 dark:text-zinc-300' : 'bg-blue-600 text-white hover:bg-blue-700' ?>">
                            <span id="btnFollowLabel"><?= $isFollowing ? '✓ 팔로잉' : '+ 팔로우' ?></span>
                        </button>
                        <button onclick="messageUser()" class="px-4 py-1.5 text-xs font-medium border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition text-zinc-700 dark:text-zinc-300">
                            ✉ 메시지
                        </button>
                    </div>
                    <?php elseif ($isMine): ?>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/mypage/profile" class="px-4 py-1.5 text-xs font-medium border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-300">
                        프로필 편집
                    </a>
                    <?php elseif (!$isLoggedIn): ?>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/login" class="px-4 py-1.5 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                        로그인하여 팔로우
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($canViewFull && !empty($profile['bio'])): ?>
            <p class="text-sm text-zinc-700 dark:text-zinc-300 mt-4 leading-relaxed whitespace-pre-wrap"><?= htmlspecialchars($profile['bio']) ?></p>
            <?php endif; ?>

            <!-- 카운트 -->
            <div class="mt-4 flex gap-6 text-sm">
                <a href="<?= htmlspecialchars($baseUrl) ?>/profile/<?= htmlspecialchars($profile['id']) ?>?tab=followers" class="hover:text-blue-600">
                    <span id="cntFollowers" class="font-bold text-zinc-900 dark:text-white"><?= number_format((int)$counts['followers']) ?></span>
                    <span class="text-xs text-zinc-500">팔로워</span>
                </a>
                <a href="<?= htmlspecialchars($baseUrl) ?>/profile/<?= htmlspecialchars($profile['id']) ?>?tab=following" class="hover:text-blue-600">
                    <span id="cntFollowing" class="font-bold text-zinc-900 dark:text-white"><?= number_format((int)$counts['following']) ?></span>
                    <span class="text-xs text-zinc-500">팔로잉</span>
                </a>
                <?php if ($postCount > 0): ?>
                <span>
                    <span class="font-bold text-zinc-900 dark:text-white"><?= number_format($postCount) ?></span>
                    <span class="text-xs text-zinc-500">게시글</span>
                </span>
                <?php endif; ?>
            </div>

            <?php if (!$canViewFull): ?>
            <div class="mt-6 p-4 bg-zinc-50 dark:bg-zinc-700/50 rounded-lg text-center">
                <svg class="w-8 h-8 mx-auto text-zinc-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                <p class="text-sm text-zinc-500">비공개 프로필입니다</p>
            </div>
            <?php endif; ?>

            <?php if ($canViewFull && !empty($profile['created_at'])): ?>
            <div class="mt-6 pt-4 border-t border-zinc-100 dark:border-zinc-700 flex items-center gap-3 text-xs text-zinc-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                가입일: <?= date('Y-m-d', strtotime($profile['created_at'])) ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- 새 메시지 모달 (재사용) -->
<div id="profileMsgModal" class="hidden fixed inset-0 z-[9000] flex items-center justify-center bg-black/50 p-4">
    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl w-full max-w-md p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars($displayName) ?> 님에게 메시지</h3>
            <button type="button" onclick="closeProfileMsgModal()" class="text-zinc-400 hover:text-zinc-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <textarea id="profileMsgBody" rows="6" maxlength="5000" placeholder="메시지를 입력하세요..." class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 dark:text-white resize-none mb-3"></textarea>
        <div class="flex justify-end gap-2">
            <button type="button" onclick="closeProfileMsgModal()" class="px-4 py-2 text-xs text-zinc-500 hover:text-zinc-700">취소</button>
            <button type="button" id="btnProfileMsgSend" onclick="sendProfileMsg()" class="px-4 py-2 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">보내기</button>
        </div>
    </div>
</div>

<script>
(function(){
    var BASE = <?= json_encode($baseUrl) ?>;
    var TARGET = <?= json_encode($profileUserId) ?>;
    var IS_LOGGED_IN = <?= $isLoggedIn ? 'true' : 'false' ?>;

    window.toggleFollow = function() {
        if (!IS_LOGGED_IN) { location.href = BASE + '/login'; return; }
        var btn = document.getElementById('btnFollow');
        var lbl = document.getElementById('btnFollowLabel');
        var following = btn.dataset.following === '1';
        btn.disabled = true;
        var fd = new FormData();
        fd.append('action', following ? 'unfollow' : 'follow');
        fd.append('target_id', TARGET);
        fetch(BASE + '/api/follows.php', { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function(r){ return r.json(); })
            .then(function(d){
                btn.disabled = false;
                if (!d.success) { alert(d.message || '실패'); return; }
                btn.dataset.following = d.is_following ? '1' : '0';
                lbl.textContent = d.is_following ? '✓ 팔로잉' : '+ 팔로우';
                if (d.is_following) {
                    btn.className = 'px-4 py-1.5 text-xs font-medium rounded-lg transition bg-zinc-100 text-zinc-700 hover:bg-red-50 hover:text-red-600 dark:bg-zinc-700 dark:text-zinc-300';
                } else {
                    btn.className = 'px-4 py-1.5 text-xs font-medium rounded-lg transition bg-blue-600 text-white hover:bg-blue-700';
                }
                if (d.counts) {
                    document.getElementById('cntFollowers').textContent = d.counts.followers;
                    document.getElementById('cntFollowing').textContent = d.counts.following;
                }
            }).catch(function(){ btn.disabled = false; alert('네트워크 오류'); });
    };

    window.messageUser = function() {
        if (!IS_LOGGED_IN) { location.href = BASE + '/login'; return; }
        document.getElementById('profileMsgModal').classList.remove('hidden');
        document.getElementById('profileMsgBody').focus();
    };
    window.closeProfileMsgModal = function() {
        document.getElementById('profileMsgModal').classList.add('hidden');
    };
    window.sendProfileMsg = function() {
        var body = document.getElementById('profileMsgBody').value.trim();
        if (!body) return;
        var btn = document.getElementById('btnProfileMsgSend');
        btn.disabled = true;
        var fd = new FormData();
        fd.append('action', 'send');
        fd.append('recipient_id', TARGET);
        fd.append('body', body);
        fetch(BASE + '/api/messages.php', { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function(r){ return r.json(); })
            .then(function(d){
                btn.disabled = false;
                if (!d.success) { alert(d.message || '전송 실패'); return; }
                alert('메시지를 보냈습니다');
                closeProfileMsgModal();
                location.href = BASE + '/mypage/messages?c=' + d.conversation_id;
            }).catch(function(){ btn.disabled = false; alert('네트워크 오류'); });
    };
})();
</script>
