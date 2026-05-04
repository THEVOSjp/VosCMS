<?php
/**
 * RezlyX - 개인정보 표시 설정 페이지
 * 프로필 항목별 공개/비공개 토글
 */

require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
use RzxLib\Core\Auth\Auth;

if (!Auth::check()) {
    header('Location: ' . ($config['app_url'] ?? '') . '/login');
    exit;
}

$user = Auth::user();
$baseUrl = $config['app_url'] ?? '';
$pageTitle = ($config['app_name'] ?? 'RezlyX') . ' - ' . __('auth.settings.title');
$isLoggedIn = true;
$currentUser = $user;

$error = '';
$success = '';

// DB 설정 로드
$registerFields = ['name', 'email', 'password', 'phone'];
try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
    $stmt = $pdo->prepare("SELECT `value` FROM {$prefix}settings WHERE `key` = ?");
    $stmt->execute(['member_register_fields']);
    $regFieldsVal = $stmt->fetchColumn();
    if ($regFieldsVal) {
        $registerFields = explode(',', $regFieldsVal);
    }
} catch (PDOException $e) {
    error_log('Settings load error: ' . $e->getMessage());
}

// 현재 개인정보 설정 로드
$privacySettings = [];
if (!empty($user['privacy_settings'])) {
    $privacySettings = json_decode($user['privacy_settings'], true) ?: [];
}

// 설정 가능한 항목 정의
$privacyFields = [];
// 이메일은 항상 존재하므로 registerFields 체크 없이 추가
$privacyFields['show_email'] = [
    'label' => __('auth.settings.fields.email'),
    'description' => __('auth.settings.fields.email_desc'),
    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>',
];
if (in_array('profile_photo', $registerFields)) {
    $privacyFields['show_profile_photo'] = [
        'label' => __('auth.settings.fields.profile_photo'),
        'description' => __('auth.settings.fields.profile_photo_desc'),
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>',
    ];
}
if (in_array('phone', $registerFields)) {
    $privacyFields['show_phone'] = [
        'label' => __('auth.settings.fields.phone'),
        'description' => __('auth.settings.fields.phone_desc'),
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>',
    ];
}
if (in_array('birth_date', $registerFields)) {
    $privacyFields['show_birth_date'] = [
        'label' => __('auth.settings.fields.birth_date'),
        'description' => __('auth.settings.fields.birth_date_desc'),
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
    ];
}
if (in_array('gender', $registerFields)) {
    $privacyFields['show_gender'] = [
        'label' => __('auth.settings.fields.gender'),
        'description' => __('auth.settings.fields.gender_desc'),
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>',
    ];
}
if (in_array('company', $registerFields)) {
    $privacyFields['show_company'] = [
        'label' => __('auth.settings.fields.company'),
        'description' => __('auth.settings.fields.company_desc'),
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>',
    ];
}
if (in_array('blog', $registerFields)) {
    $privacyFields['show_blog'] = [
        'label' => __('auth.settings.fields.blog'),
        'description' => __('auth.settings.fields.blog_desc'),
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>',
    ];
}

// POST 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = $_POST['form'] ?? 'privacy';

    if ($form === 'messaging') {
        // 메시지 수신 정책 + 프로필 공개 토글
        $allowOptions = ['all','followers','none'];
        $allow = in_array($_POST['allow_messages_from'] ?? '', $allowOptions, true)
            ? $_POST['allow_messages_from'] : 'all';
        $isPublic = isset($_POST['is_profile_public']) ? 1 : 0;
        try {
            $upd = $pdo->prepare("UPDATE {$prefix}users SET allow_messages_from = ?, is_profile_public = ?, updated_at = NOW() WHERE id = ?");
            $upd->execute([$allow, $isPublic, $user['id']]);
            $success = '메시지 수신 설정이 저장되었습니다.';
            // 갱신
            $u2 = $pdo->prepare("SELECT allow_messages_from, is_profile_public FROM {$prefix}users WHERE id = ?");
            $u2->execute([$user['id']]);
            $umsg = $u2->fetch(PDO::FETCH_ASSOC);
            $user['allow_messages_from'] = $umsg['allow_messages_from'] ?? 'all';
            $user['is_profile_public'] = (int)($umsg['is_profile_public'] ?? 1);
        } catch (\Throwable $e) {
            $error = '저장 실패: ' . $e->getMessage();
        }
    } else {
        $newSettings = [];
        foreach (array_keys($privacyFields) as $key) {
            $newSettings[$key] = isset($_POST[$key]) ? true : false;
        }
        $result = Auth::updateProfile($user['id'], [
            'privacy_settings' => json_encode($newSettings, JSON_UNESCAPED_UNICODE),
        ]);
        if ($result['success']) {
            $success = __('auth.settings.success');
            $privacySettings = $newSettings;
            $user = Auth::user();
            $currentUser = $user;
        } else {
            $error = __('auth.settings.error');
        }
    }
}

// 메시지 설정 현재값 로드 (POST 후에도 최신 상태)
$msgAllow = $user['allow_messages_from'] ?? 'all';
$msgPublic = (int)($user['is_profile_public'] ?? 1);

// 프로필 이미지 URL
$profileImgUrl = '';
if (!empty($user['profile_image'])) {
    $profileImgUrl = str_starts_with($user['profile_image'], 'http')
        ? $user['profile_image']
        : $baseUrl . $user['profile_image'];
}

?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="lg:flex lg:gap-8">
            <!-- 사이드바 -->
            <?php
            $sidebarActive = 'settings';
            include BASE_PATH . '/resources/views/components/mypage-sidebar.php';
            ?>

            <!-- 메인 콘텐츠 -->
            <div class="flex-1">
                <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg p-6">
                    <div class="mb-6">
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white"><?= __('auth.settings.title') ?></h1>
                        <p class="text-gray-500 dark:text-zinc-400 mt-1"><?= __('auth.settings.description') ?></p>
                    </div>

                    <?php if ($error): ?>
                    <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg">
                        <span class="text-red-700 dark:text-red-300 text-sm"><?= htmlspecialchars($error) ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                    <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg">
                        <span class="text-green-700 dark:text-green-300 text-sm"><?= htmlspecialchars($success) ?></span>
                    </div>
                    <?php endif; ?>

                    <!-- 안내 박스 -->
                    <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg flex items-start gap-3">
                        <svg class="w-5 h-5 text-blue-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="text-sm text-blue-700 dark:text-blue-300"><?= __('auth.settings.info') ?></p>
                    </div>

                    <form method="POST" class="space-y-1">
                        <?php foreach ($privacyFields as $key => $field):
                            $isEnabled = $privacySettings[$key] ?? true;
                        ?>
                        <div class="flex items-center justify-between py-4 border-b border-zinc-100 dark:border-zinc-700 last:border-b-0">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg bg-zinc-100 dark:bg-zinc-700 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-zinc-500 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><?= $field['icon'] ?></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white"><?= $field['label'] ?></p>
                                    <p class="text-xs text-gray-500 dark:text-zinc-400"><?= $field['description'] ?></p>
                                </div>
                            </div>
                            <!-- 토글 스위치 -->
                            <label class="relative inline-flex items-center cursor-pointer flex-shrink-0 ml-4">
                                <input type="checkbox" name="<?= $key ?>" value="1" class="sr-only peer" <?= $isEnabled ? 'checked' : '' ?>>
                                <div class="w-11 h-6 bg-zinc-300 dark:bg-zinc-600 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border after:border-zinc-300 after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>
                        <?php endforeach; ?>

                        <?php if (empty($privacyFields)): ?>
                        <div class="py-8 text-center">
                            <p class="text-zinc-400 dark:text-zinc-500"><?= __('auth.settings.no_fields') ?></p>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($privacyFields)): ?>
                        <div class="pt-6">
                            <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition shadow-lg shadow-blue-500/30">
                                <?= __('auth.profile.submit') ?>
                            </button>
                        </div>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- 메시지 수신 + 프로필 공개 -->
                <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg p-6 mt-6">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">메시지 · 프로필</h2>
                    <form method="POST" class="space-y-5">
                        <input type="hidden" name="form" value="messaging">

                        <div>
                            <label class="block text-sm font-semibold text-zinc-800 dark:text-zinc-200 mb-2">메시지 수신 허용</label>
                            <div class="space-y-2">
                                <label class="flex items-start gap-3 p-3 border border-zinc-200 dark:border-zinc-700 rounded-lg cursor-pointer has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 dark:has-[:checked]:bg-blue-900/20">
                                    <input type="radio" name="allow_messages_from" value="all" <?= $msgAllow === 'all' ? 'checked' : '' ?> class="mt-0.5">
                                    <div>
                                        <p class="text-sm font-medium text-zinc-900 dark:text-white">누구나</p>
                                        <p class="text-xs text-zinc-500 mt-0.5">모든 회원이 메시지를 보낼 수 있습니다.</p>
                                    </div>
                                </label>
                                <label class="flex items-start gap-3 p-3 border border-zinc-200 dark:border-zinc-700 rounded-lg cursor-pointer has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 dark:has-[:checked]:bg-blue-900/20">
                                    <input type="radio" name="allow_messages_from" value="followers" <?= $msgAllow === 'followers' ? 'checked' : '' ?> class="mt-0.5">
                                    <div>
                                        <p class="text-sm font-medium text-zinc-900 dark:text-white">내가 팔로우하는 사람만</p>
                                        <p class="text-xs text-zinc-500 mt-0.5">본인이 팔로우하는 사용자만 메시지를 보낼 수 있습니다.</p>
                                    </div>
                                </label>
                                <label class="flex items-start gap-3 p-3 border border-zinc-200 dark:border-zinc-700 rounded-lg cursor-pointer has-[:checked]:border-red-500 has-[:checked]:bg-red-50 dark:has-[:checked]:bg-red-900/20">
                                    <input type="radio" name="allow_messages_from" value="none" <?= $msgAllow === 'none' ? 'checked' : '' ?> class="mt-0.5">
                                    <div>
                                        <p class="text-sm font-medium text-zinc-900 dark:text-white">받지 않음</p>
                                        <p class="text-xs text-zinc-500 mt-0.5">아무도 메시지를 보낼 수 없습니다.</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="flex items-center justify-between p-3 bg-zinc-50 dark:bg-zinc-700/30 rounded-lg">
                            <div>
                                <p class="text-sm font-medium text-zinc-900 dark:text-white">프로필 공개</p>
                                <p class="text-xs text-zinc-500">비공개 시 본인 외에는 프로필 상세를 볼 수 없습니다 (카운트만 표시).</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="is_profile_public" value="1" class="sr-only peer" <?= $msgPublic ? 'checked' : '' ?>>
                                <div class="w-11 h-6 bg-zinc-300 dark:bg-zinc-600 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>

                        <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg">
                            저장
                        </button>
                    </form>
                </div>

                <!-- 차단 목록 -->
                <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg p-6 mt-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">차단 목록</h2>
                        <span id="blockListCount" class="text-xs text-zinc-400">-</span>
                    </div>
                    <div id="blockList" class="divide-y divide-zinc-100 dark:divide-zinc-700"></div>
                    <div id="blockListEmpty" class="hidden p-8 text-center text-sm text-zinc-400">차단한 사용자가 없습니다.</div>
                </div>

                <script>
                (function(){
                    var BASE = <?= json_encode($baseUrl) ?>;
                    function escHtml(s) { return (s || '').replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
                    function loadBlocks() {
                        fetch(BASE + '/api/blocks.php?action=list', {credentials:'same-origin'})
                            .then(function(r){ return r.json(); })
                            .then(function(d){
                                if (!d || !d.success) return;
                                var list = document.getElementById('blockList');
                                var empty = document.getElementById('blockListEmpty');
                                document.getElementById('blockListCount').textContent = d.blocks.length + '명';
                                if (!d.blocks.length) {
                                    list.innerHTML = '';
                                    empty.classList.remove('hidden');
                                    return;
                                }
                                empty.classList.add('hidden');
                                list.innerHTML = d.blocks.map(function(b){
                                    var av = b.avatar_url
                                        ? '<img src="' + escHtml(b.avatar_url) + '" class="w-8 h-8 rounded-full object-cover">'
                                        : '<div class="w-8 h-8 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center text-xs font-bold">' + escHtml((b.display_name||'?').charAt(0).toUpperCase()) + '</div>';
                                    var when = (b.created_at || '').slice(0, 10);
                                    return '<div class="flex items-center gap-3 py-3">'
                                        + av
                                        + '<div class="flex-1 min-w-0">'
                                        +   '<p class="text-sm font-semibold text-zinc-900 dark:text-white truncate">' + escHtml(b.display_name) + '</p>'
                                        +   '<p class="text-[11px] text-zinc-400">차단일: ' + when + (b.reason ? ' · 사유: ' + escHtml(b.reason) : '') + '</p>'
                                        + '</div>'
                                        + '<button type="button" onclick="unblockUser(\'' + escHtml(b.blocked_id) + '\')" class="px-3 py-1.5 text-xs font-medium text-zinc-600 dark:text-zinc-300 border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700">차단 해제</button>'
                                        + '</div>';
                                }).join('');
                            });
                    }
                    window.unblockUser = function(uid) {
                        if (!confirm('차단을 해제하시겠습니까?')) return;
                        var fd = new FormData(); fd.append('action', 'unblock'); fd.append('target_id', uid);
                        fetch(BASE + '/api/blocks.php', {method:'POST', body: fd, credentials:'same-origin'})
                            .then(function(r){ return r.json(); })
                            .then(function(d){
                                if (!d.success) { alert(d.message || '실패'); return; }
                                loadBlocks();
                            });
                    };
                    loadBlocks();
                })();
                </script>
            </div>
        </div>
    </div>

<?php
?>
