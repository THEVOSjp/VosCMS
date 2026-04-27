<?php
include __DIR__ . '/../_head.php';
$db = mkt_pdo(); $pfx = $_mktPrefix;
$id = (int)($_GET['id'] ?? 0);
$cat = null;
if ($id) {
    $st = $db->prepare("SELECT * FROM {$pfx}mkt_categories WHERE id=?");
    $st->execute([$id]); $cat = $st->fetch();
    if (!$cat) { echo '<p class="text-red-500">카테고리를 찾을 수 없습니다.</p>'; include __DIR__.'/../_foot.php'; return; }
}
$pageHeaderTitle = $cat ? '카테고리 수정' : '새 카테고리';
$csrf = $_SESSION['_csrf'] ?? '';
$adminUrl = $_mktAdmin;
$locales = ['ko'=>'한국어','en'=>'English','ja'=>'日本語'];
$nameData = $cat ? (json_decode($cat['name'],true)?:[]) : [];
$descData = $cat ? (json_decode($cat['description']??'{}',true)?:[]) : [];
?>
<div class="flex items-center gap-4 mb-6">
    <a href="<?= $adminUrl ?>/market/categories" class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">← 목록</a>
    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white"><?= $pageHeaderTitle ?></h1>
</div>

<form id="catForm" class="max-w-2xl space-y-6">
<input type="hidden" id="f_id" value="<?= $id ?>">
<input type="hidden" id="f_token" value="<?= htmlspecialchars($csrf) ?>">

<div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6 space-y-5">
    <div>
        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">슬러그 <span class="text-red-500">*</span></label>
        <input type="text" id="f_slug" value="<?= htmlspecialchars($cat['slug']??'') ?>" placeholder="business"
            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 font-mono">
    </div>
    <div>
        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">카테고리명 (다국어)</label>
        <?php foreach ($locales as $lk => $ll): ?>
        <div class="flex items-center gap-2 mb-2">
            <span class="w-16 text-xs text-zinc-500 font-medium"><?= $ll ?></span>
            <input type="text" id="f_name_<?= $lk ?>" value="<?= htmlspecialchars($nameData[$lk]??'') ?>"
                class="flex-1 px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200">
        </div>
        <?php endforeach; ?>
    </div>
    <div>
        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">설명 (다국어)</label>
        <?php foreach ($locales as $lk => $ll): ?>
        <div class="flex items-center gap-2 mb-2">
            <span class="w-16 text-xs text-zinc-500 font-medium"><?= $ll ?></span>
            <input type="text" id="f_desc_<?= $lk ?>" value="<?= htmlspecialchars($descData[$lk]??'') ?>"
                class="flex-1 px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200">
        </div>
        <?php endforeach; ?>
    </div>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">아이콘</label>
            <input type="text" id="f_icon" value="<?= htmlspecialchars($cat['icon']??'') ?>" placeholder="grid"
                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200">
        </div>
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">정렬 순서</label>
            <input type="number" id="f_sort" value="<?= (int)($cat['sort_order']??0) ?>"
                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200">
        </div>
    </div>
    <div class="flex items-center gap-2">
        <input type="checkbox" id="f_active" <?= ($cat['is_active']??1) ? 'checked' : '' ?> class="rounded">
        <label for="f_active" class="text-sm text-zinc-700 dark:text-zinc-300">활성화</label>
    </div>
</div>

<div class="flex gap-3">
    <button type="button" onclick="saveCategory()"
        class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition">저장</button>
    <a href="<?= $adminUrl ?>/market/categories" class="px-6 py-2.5 border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-400 rounded-lg text-sm hover:bg-zinc-50 dark:hover:bg-zinc-700">취소</a>
</div>
</form>

<script>
const CSRF = <?= json_encode($csrf) ?>;
const API_URL = <?= json_encode($adminUrl.'/market/categories/api') ?>;
async function saveCategory() {
    const slug = document.getElementById('f_slug').value.trim();
    if (!slug) { alert('슬러그를 입력하세요'); return; }
    const name = { ko: document.getElementById('f_name_ko').value, en: document.getElementById('f_name_en').value, ja: document.getElementById('f_name_ja').value };
    const desc = { ko: document.getElementById('f_desc_ko').value, en: document.getElementById('f_desc_en').value, ja: document.getElementById('f_desc_ja').value };
    const fd = new FormData();
    fd.append('action', document.getElementById('f_id').value ? 'update' : 'create');
    fd.append('id', document.getElementById('f_id').value);
    fd.append('slug', slug);
    fd.append('name', JSON.stringify(name));
    fd.append('description', JSON.stringify(desc));
    fd.append('icon', document.getElementById('f_icon').value.trim());
    fd.append('sort_order', document.getElementById('f_sort').value);
    fd.append('is_active', document.getElementById('f_active').checked ? '1' : '0');
    fd.append('_token', CSRF);
    const r = await fetch(API_URL, {method:'POST',body:fd});
    const d = await r.json();
    if (d.ok) location.href = <?= json_encode($adminUrl.'/market/categories') ?>;
    else alert(d.msg);
}
</script>
<?php include __DIR__ . '/../_foot.php'; ?>
