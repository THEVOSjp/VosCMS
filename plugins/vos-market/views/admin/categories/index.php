<?php
include __DIR__ . '/../_head.php';
$pageHeaderTitle = '카테고리 관리';
$db = mkt_pdo(); $pfx = $_mktPrefix;
$cats = $db->query("SELECT c.*, COUNT(i.id) item_count FROM {$pfx}mkt_categories c LEFT JOIN {$pfx}mkt_items i ON i.category_id=c.id GROUP BY c.id ORDER BY c.sort_order ASC, c.id ASC")->fetchAll();
$adminUrl = $_mktAdmin;
$csrf = $_SESSION['_csrf'] ?? '';
?>
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">카테고리 관리</h1>
        <p class="text-sm text-zinc-500 mt-0.5">전체 <?= count($cats) ?>개</p>
    </div>
    <a href="<?= $adminUrl ?>/market/categories/edit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition">+ 새 카테고리</a>
</div>

<?php if (empty($cats)): ?>
<div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-16 text-center">
    <p class="text-zinc-400">카테고리가 없습니다.</p>
</div>
<?php else: ?>
<div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-zinc-50 dark:bg-zinc-900/50 border-b border-zinc-200 dark:border-zinc-700">
            <tr>
                <th class="text-left px-4 py-3 text-zinc-500 font-medium">카테고리</th>
                <th class="text-left px-4 py-3 text-zinc-500 font-medium">슬러그</th>
                <th class="text-left px-4 py-3 text-zinc-500 font-medium">아이콘</th>
                <th class="text-left px-4 py-3 text-zinc-500 font-medium">아이템 수</th>
                <th class="text-left px-4 py-3 text-zinc-500 font-medium">순서</th>
                <th class="text-left px-4 py-3 text-zinc-500 font-medium">상태</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
        <?php foreach ($cats as $cat):
            $catName = mkt_locale_val($cat['name'], $_mktLocale) ?: $cat['slug'];
        ?>
        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
            <td class="px-4 py-3 font-medium text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($catName) ?></td>
            <td class="px-4 py-3 font-mono text-xs text-zinc-400"><?= htmlspecialchars($cat['slug']) ?></td>
            <td class="px-4 py-3 text-zinc-500"><?= htmlspecialchars($cat['icon'] ?? '') ?></td>
            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400"><?= number_format((int)$cat['item_count']) ?></td>
            <td class="px-4 py-3 text-zinc-500"><?= (int)$cat['sort_order'] ?></td>
            <td class="px-4 py-3">
                <span class="px-2 py-0.5 text-xs rounded font-medium <?= $cat['is_active'] ? 'bg-green-100 text-green-700' : 'bg-zinc-100 text-zinc-500' ?>">
                    <?= $cat['is_active'] ? '활성' : '비활성' ?>
                </span>
            </td>
            <td class="px-4 py-3 flex gap-2">
                <a href="<?= $adminUrl ?>/market/categories/edit?id=<?= $cat['id'] ?>" class="text-indigo-600 dark:text-indigo-400 hover:underline text-xs">수정</a>
                <button onclick="deleteCategory(<?= $cat['id'] ?>, <?= (int)$cat['item_count'] ?>)" class="text-red-500 hover:text-red-700 text-xs">삭제</button>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<script>
const CSRF = <?= json_encode($csrf) ?>;
const API_URL = <?= json_encode($adminUrl.'/market/categories/api') ?>;
async function deleteCategory(id, itemCount) {
    if (itemCount > 0 && !confirm(`이 카테고리에 ${itemCount}개의 아이템이 있습니다. 정말 삭제하시겠습니까?`)) return;
    if (itemCount === 0 && !confirm('삭제하시겠습니까?')) return;
    const fd = new FormData();
    fd.append('action','delete'); fd.append('id',id); fd.append('_token',CSRF);
    const r = await fetch(API_URL, {method:'POST',body:fd});
    const d = await r.json();
    if (d.ok) location.reload(); else alert(d.msg);
}
</script>
<?php include __DIR__ . '/../_foot.php'; ?>
