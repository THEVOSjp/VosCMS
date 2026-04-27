<?php
/**
 * 스타일북 설정 페이지
 * /styles/settings — 관리자만 접근 가능
 * 태그 관리 (추가/수정/삭제/정렬)
 */

require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
if (!\RzxLib\Core\Auth\Auth::check()) {
    header('Location: ' . ($config['app_url'] ?? '') . '/login?redirect=styles/settings');
    exit;
}
$currentUser = \RzxLib\Core\Auth\Auth::user();
$isAdmin = in_array($currentUser['role'] ?? '', ['admin', 'supervisor', 'manager', 'staff']);
if (!$isAdmin) {
    http_response_code(403);
    echo '<div class="max-w-md mx-auto py-16 text-center text-zinc-500">' . (__('common.no_permission') ?? '관리자 권한이 필요합니다.') . '</div>';
    return;
}

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$currentLocale = $config['locale'] ?? 'ko';

// 플러그인 번역 로드
$_shopLang = @include(BASE_PATH . '/plugins/vos-shop/lang/' . $currentLocale . '/shop.php');
if (!is_array($_shopLang)) $_shopLang = @include(BASE_PATH . '/plugins/vos-shop/lang/ko/shop.php');
if (is_array($_shopLang) && class_exists('\RzxLib\Core\I18n\Translator')) {
    \RzxLib\Core\I18n\Translator::merge('shop', $_shopLang);
}

$pageTitle = __('shop.stylebook.settings_title') ?? '스타일북 설정';
$seoContext = ['type' => 'sub', 'subpage_title' => $pageTitle];

// AJAX 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'add_tag') {
            $slug = trim($_POST['slug'] ?? '');
            $nameKo = trim($_POST['name_ko'] ?? '');
            $nameEn = trim($_POST['name_en'] ?? '');
            $nameJa = trim($_POST['name_ja'] ?? '');
            $category = trim($_POST['category'] ?? 'hair');
            if (!$slug || !$nameKo) { echo json_encode(['error' => '슬러그와 한국어 이름은 필수']); exit; }
            $slug = preg_replace('/[^a-z0-9-]/', '', strtolower(str_replace(' ', '-', $slug)));
            $name = json_encode(array_filter(['ko' => $nameKo, 'en' => $nameEn ?: $nameKo, 'ja' => $nameJa ?: '']), JSON_UNESCAPED_UNICODE);
            $maxOrder = (int)$pdo->query("SELECT MAX(sort_order) FROM {$prefix}style_tags")->fetchColumn();
            $pdo->prepare("INSERT INTO {$prefix}style_tags (slug, name, category, sort_order) VALUES (?, ?, ?, ?)")->execute([$slug, $name, $category, $maxOrder + 1]);
            echo json_encode(['success' => true]);
        } elseif ($action === 'update_tag') {
            $id = (int)($_POST['id'] ?? 0);
            $nameKo = trim($_POST['name_ko'] ?? '');
            $nameEn = trim($_POST['name_en'] ?? '');
            $nameJa = trim($_POST['name_ja'] ?? '');
            $category = trim($_POST['category'] ?? 'hair');
            $isActive = (int)($_POST['is_active'] ?? 1);
            if (!$id || !$nameKo) { echo json_encode(['error' => 'ID와 이름 필수']); exit; }
            $name = json_encode(array_filter(['ko' => $nameKo, 'en' => $nameEn ?: $nameKo, 'ja' => $nameJa ?: '']), JSON_UNESCAPED_UNICODE);
            $pdo->prepare("UPDATE {$prefix}style_tags SET name=?, category=?, is_active=? WHERE id=?")->execute([$name, $category, $isActive, $id]);
            echo json_encode(['success' => true]);
        } elseif ($action === 'delete_tag') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id) $pdo->prepare("DELETE FROM {$prefix}style_tags WHERE id=?")->execute([$id]);
            echo json_encode(['success' => true]);
        } elseif ($action === 'reorder') {
            $orders = $_POST['orders'] ?? [];
            $stmt = $pdo->prepare("UPDATE {$prefix}style_tags SET sort_order=? WHERE id=?");
            foreach ($orders as $i => $id) { $stmt->execute([$i, (int)$id]); }
            echo json_encode(['success' => true]);
        }
    } catch (\Throwable $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// 태그 목록 로드
$tags = $pdo->query("SELECT * FROM {$prefix}style_tags ORDER BY category, sort_order")->fetchAll(PDO::FETCH_ASSOC);
$categoryLabels = [
    'hair' => __('shop.stylebook.cat_hair') ?? '헤어',
    'nail' => __('shop.stylebook.cat_nail') ?? '네일',
    'skin' => __('shop.stylebook.cat_skin') ?? '스킨케어',
    'makeup' => __('shop.stylebook.cat_makeup') ?? '메이크업',
    'common' => __('shop.stylebook.cat_common') ?? '공통',
];
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars($pageTitle) ?></h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1"><?= __('shop.stylebook.settings_desc') ?? '스타일 태그를 관리합니다. 사용자가 등록 시 선택할 수 있는 태그 목록입니다.' ?></p>
        </div>
        <a href="<?= $baseUrl ?>/styles" class="text-sm text-blue-600 hover:underline">&larr; <?= __('shop.stylebook.title') ?? '스타일북' ?></a>
    </div>

    <!-- 태그 추가 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6 mb-6">
        <h2 class="text-base font-semibold text-zinc-900 dark:text-white mb-4"><?= __('shop.stylebook.add_tag') ?? '태그 추가' ?></h2>
        <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
            <input type="text" id="tagSlug" placeholder="slug (영문)" class="px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
            <input type="text" id="tagNameKo" placeholder="한국어" class="px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
            <input type="text" id="tagNameEn" placeholder="English" class="px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
            <select id="tagCategory" class="px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                <?php foreach ($categoryLabels as $ck => $cl): ?>
                <option value="<?= $ck ?>"><?= htmlspecialchars($cl) ?></option>
                <?php endforeach; ?>
            </select>
            <button onclick="addTag()" class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"><?= __('common.buttons.add') ?? '추가' ?></button>
        </div>
    </div>

    <!-- 태그 목록 -->
    <?php
    $grouped = [];
    foreach ($tags as $t) { $grouped[$t['category']][] = $t; }
    ?>
    <?php foreach ($grouped as $cat => $catTags): ?>
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 mb-4 overflow-hidden">
        <div class="px-6 py-3 bg-zinc-50 dark:bg-zinc-700/50 border-b border-zinc-200 dark:border-zinc-700">
            <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars($categoryLabels[$cat] ?? $cat) ?> (<?= count($catTags) ?>)</h3>
        </div>
        <div class="divide-y divide-zinc-100 dark:divide-zinc-700">
            <?php foreach ($catTags as $t):
                $tName = json_decode($t['name'], true) ?: [];
            ?>
            <div class="flex items-center px-6 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-700/30" data-tag-id="<?= $t['id'] ?>">
                <span class="w-24 text-xs text-zinc-400 font-mono"><?= htmlspecialchars($t['slug']) ?></span>
                <span class="flex-1 text-sm text-zinc-900 dark:text-white font-medium"><?= htmlspecialchars($tName[$currentLocale] ?? $tName['ko'] ?? '') ?></span>
                <span class="w-24 text-xs text-zinc-400"><?= htmlspecialchars($tName['en'] ?? '') ?></span>
                <span class="w-16 text-center">
                    <?php if ($t['is_active']): ?>
                    <span class="text-[10px] px-2 py-0.5 bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300 rounded-full">ON</span>
                    <?php else: ?>
                    <span class="text-[10px] px-2 py-0.5 bg-zinc-100 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400 rounded-full">OFF</span>
                    <?php endif; ?>
                </span>
                <div class="flex items-center gap-1 ml-3">
                    <button onclick="toggleTag(<?= $t['id'] ?>, <?= $t['is_active'] ? 0 : 1 ?>)" class="p-1.5 text-zinc-400 hover:text-blue-600 rounded transition" title="<?= $t['is_active'] ? '비활성화' : '활성화' ?>">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $t['is_active'] ? 'M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21' : 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z' ?>"/></svg>
                    </button>
                    <button onclick="if(confirm('삭제하시겠습니까?'))deleteTag(<?= $t['id'] ?>)" class="p-1.5 text-zinc-400 hover:text-red-600 rounded transition" title="삭제">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<script>
function addTag() {
    var fd = new FormData();
    fd.append('action', 'add_tag');
    fd.append('slug', document.getElementById('tagSlug').value);
    fd.append('name_ko', document.getElementById('tagNameKo').value);
    fd.append('name_en', document.getElementById('tagNameEn').value);
    fd.append('category', document.getElementById('tagCategory').value);
    fetch(location.href, { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body:fd })
    .then(function(r){return r.json()}).then(function(d) {
        if (d.success) location.reload();
        else alert(d.error || 'Error');
    });
}
function toggleTag(id, active) {
    var fd = new FormData();
    fd.append('action', 'update_tag');
    fd.append('id', id);
    fd.append('is_active', active);
    // 기존 이름 유지
    fd.append('name_ko', '-'); fd.append('category', 'hair');
    fetch(location.href, { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body:fd })
    .then(function(r){return r.json()}).then(function(d) { if (d.success) location.reload(); });
}
function deleteTag(id) {
    var fd = new FormData();
    fd.append('action', 'delete_tag');
    fd.append('id', id);
    fetch(location.href, { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body:fd })
    .then(function(r){return r.json()}).then(function(d) { if (d.success) location.reload(); });
}
</script>
