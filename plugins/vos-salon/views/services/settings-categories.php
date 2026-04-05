<?php
/**
 * 서비스 설정 - 카테고리 관리 탭
 * 카테고리 CRUD + 드래그앤드롭 정렬 + 다국어
 */

// POST API 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_POST['action'];

    try {
        switch ($action) {
            case 'create_category':
                $name = trim($_POST['name'] ?? '');
                $slug = trim($_POST['slug'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
                $isActive = isset($_POST['is_active']) ? 1 : 0;

                if (empty($name)) {
                    echo json_encode(['success' => false, 'message' => __('services.categories.fields.name') . ' required']);
                    exit;
                }
                if (empty($slug)) {
                    $slug = preg_replace('/[^a-z0-9-]/', '-', strtolower($name));
                    $slug = preg_replace('/-+/', '-', trim($slug, '-'));
                }

                $maxSort = $pdo->query("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM {$prefix}service_categories")->fetchColumn();
                $stmt = $pdo->prepare("INSERT INTO {$prefix}service_categories (name, slug, description, parent_id, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $slug, $description, $parentId, $maxSort, $isActive]);

                echo json_encode(['success' => true, 'message' => __('services.categories.success.created'), 'id' => $pdo->lastInsertId()]);
                exit;

            case 'update_category':
                $id = (int)$_POST['id'];
                $name = trim($_POST['name'] ?? '');
                $slug = trim($_POST['slug'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
                $isActive = isset($_POST['is_active']) ? 1 : 0;

                $stmt = $pdo->prepare("UPDATE {$prefix}service_categories SET name=?, slug=?, description=?, parent_id=?, is_active=? WHERE id=?");
                $stmt->execute([$name, $slug, $description, $parentId, $isActive, $id]);

                echo json_encode(['success' => true, 'message' => __('services.categories.success.updated')]);
                exit;

            case 'delete_category':
                $id = (int)$_POST['id'];
                $cnt = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}services WHERE category_id = ?");
                $cnt->execute([$id]);
                if ($cnt->fetchColumn() > 0) {
                    echo json_encode(['success' => false, 'message' => __('services.categories.error.has_services')]);
                    exit;
                }
                $pdo->prepare("DELETE FROM {$prefix}service_categories WHERE id = ?")->execute([$id]);
                echo json_encode(['success' => true, 'message' => __('services.categories.success.deleted')]);
                exit;

            case 'reorder_categories':
                $ids = json_decode($_POST['ids'] ?? '[]', true);
                if (is_array($ids) && count($ids) > 0) {
                    $stmt = $pdo->prepare("UPDATE {$prefix}service_categories SET sort_order = ? WHERE id = ?");
                    foreach ($ids as $i => $id) {
                        $stmt->execute([$i + 1, (int)$id]);
                    }
                }
                echo json_encode(['success' => true, 'message' => __('services.categories.success.reordered')]);
                exit;
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// 카테고리 목록 로드
$categories = $pdo->query("SELECT * FROM {$prefix}service_categories ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);
$totalCategories = count($categories);

// 다국어 표시 룰
$currentLocale = $config['locale'] ?? 'ko';
$defaultLocale = $config['default_language'] ?? 'ko';
$catLocaleChain = array_unique(array_filter([$currentLocale, 'en', $defaultLocale]));

$catPlaceholders = implode(',', array_fill(0, count($catLocaleChain), '?'));
$trStmt = $pdo->prepare("SELECT lang_key, locale, content FROM {$prefix}translations WHERE locale IN ({$catPlaceholders}) AND lang_key LIKE 'category.%'");
$trStmt->execute(array_values($catLocaleChain));

$catAllTranslations = [];
while ($tr = $trStmt->fetch(PDO::FETCH_ASSOC)) {
    $catAllTranslations[$tr['lang_key']][$tr['locale']] = $tr['content'];
}

if (!function_exists('getCategoryTranslated')) {
    function getCategoryTranslated($catId, $field, $default) {
        global $catAllTranslations, $catLocaleChain;
        $key = "category.{$catId}.{$field}";
        if (isset($catAllTranslations[$key])) {
            foreach ($catLocaleChain as $loc) {
                if (!empty($catAllTranslations[$key][$loc])) return $catAllTranslations[$key][$loc];
            }
        }
        return $default;
    }
}

// 카테고리 편집 모달용 번역 데이터
$catTranslatedMap = [];
foreach ($categories as $cat) {
    $catTranslatedMap[$cat['id']] = [
        'name' => getCategoryTranslated($cat['id'], 'name', $cat['name']),
        'description' => getCategoryTranslated($cat['id'], 'description', $cat['description']),
    ];
}
?>

<!-- 알림 메시지 -->
<div id="alertBox" class="hidden mb-6 p-4 rounded-lg border"></div>

<div class="flex items-center justify-between mb-4">
    <p class="text-sm text-zinc-500 dark:text-zinc-400"><?= __('services.categories.list') ?> (<?= $totalCategories ?>)</p>
    <button onclick="openCategoryModal()"
            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        <?= __('services.categories.create') ?>
    </button>
</div>

<style>
    .cat-sortable-ghost { opacity: 0; }
    .cat-sortable-drag {
        opacity: 1 !important;
        box-shadow: 0 12px 28px rgba(0,0,0,0.15), 0 4px 8px rgba(0,0,0,0.1);
        transform: scale(1.02);
        z-index: 100;
    }
    .cat-sortable-fallback { opacity: 0.9 !important; }
    #categorySortable .cat-drop-placeholder {
        border: 2px dashed #3b82f6;
        background: rgba(59,130,246,0.06);
        border-radius: 0.75rem;
        min-height: 60px;
        transition: all 0.2s ease;
    }
</style>

<div id="categorySortable" class="flex flex-col gap-3">
    <?php foreach ($categories as $cat): ?>
    <div id="cat-<?= $cat['id'] ?>" data-id="<?= $cat['id'] ?>" class="cat-sort-item flex items-center gap-4 bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 px-4 py-3 hover:shadow-md transition-all">
        <div class="cat-drag-handle cursor-grab active:cursor-grabbing flex-shrink-0 p-1.5 text-zinc-300 hover:text-zinc-500 dark:text-zinc-600 dark:hover:text-zinc-400 rounded-md hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M7 2a2 2 0 10.001 4.001A2 2 0 007 2zm0 6a2 2 0 10.001 4.001A2 2 0 007 8zm0 6a2 2 0 10.001 4.001A2 2 0 007 14zm6-8a2 2 0 10-.001-4.001A2 2 0 0013 6zm0 2a2 2 0 10.001 4.001A2 2 0 0013 8zm0 6a2 2 0 10.001 4.001A2 2 0 0013 14z"/></svg>
        </div>
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2">
                <h3 class="font-semibold text-zinc-900 dark:text-white text-sm truncate"><?= htmlspecialchars(getCategoryTranslated($cat['id'], 'name', $cat['name'])) ?></h3>
                <span class="flex-shrink-0 text-xs text-zinc-400 dark:text-zinc-500"><?= htmlspecialchars($cat['slug']) ?></span>
            </div>
            <?php if (!empty($cat['description'])): ?>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5 truncate"><?= htmlspecialchars(mb_substr(getCategoryTranslated($cat['id'], 'description', $cat['description']), 0, 100)) ?></p>
            <?php endif; ?>
        </div>
        <span class="flex-shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $cat['is_active'] ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-500 dark:text-zinc-400' ?>">
            <?= $cat['is_active'] ? __('services.status_active') : __('services.status_inactive') ?>
        </span>
        <div class="flex-shrink-0 flex items-center gap-1">
            <button onclick='editCategory(<?= json_encode($cat, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                    class="p-1.5 text-zinc-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors" title="<?= __('services.categories.edit') ?>">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            </button>
            <button onclick="deleteCategory(<?= $cat['id'] ?>)"
                    class="p-1.5 text-zinc-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors" title="<?= __('services.categories.delete') ?>">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php if (empty($categories)): ?>
<div class="p-12 text-center text-zinc-500 dark:text-zinc-400 bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700">
    <p><?= __('services.categories.empty') ?></p>
    <button onclick="openCategoryModal()" class="mt-3 text-blue-600 hover:text-blue-700 text-sm font-medium">+ <?= __('services.categories.create') ?></button>
</div>
<?php endif; ?>

<!-- 카테고리 모달 -->
<?php include BASE_PATH . '/resources/views/admin/services/category-modal.php'; ?>

<!-- SortableJS + 카테고리 JS -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
<script>
var catTranslations = <?= json_encode($catTranslatedMap, JSON_UNESCAPED_UNICODE) ?>;
var catMultilangTempKey = null;
var categoriesApiUrl = '<?= $adminUrl ?>/services/settings/categories';

function getCategoryLangKey(field) {
    var catId = document.getElementById('catId').value;
    if (catId) return 'category.' + catId + '.' + field;
    if (!catMultilangTempKey) {
        catMultilangTempKey = 'category.tmp_' + Date.now() + '_' + Math.random().toString(36).substr(2, 6);
    }
    return catMultilangTempKey + '.' + field;
}

function openCategoryMultilang(field) {
    var langKey = getCategoryLangKey(field);
    var inputId = field === 'name' ? 'catName' : 'catDescription';
    if (typeof openMultilangModal === 'function') {
        openMultilangModal(langKey, inputId, 'text');
    }
}

function openCategoryModal() {
    catMultilangTempKey = null;
    document.getElementById('catModalTitle').textContent = '<?= __('services.categories.create') ?>';
    document.getElementById('catId').value = '';
    document.getElementById('catName').value = '';
    document.getElementById('catSlug').value = '';
    document.getElementById('catDescription').value = '';
    document.getElementById('catParentId').value = '';
    document.getElementById('catIsActive').checked = true;
    document.getElementById('categoryModal').classList.remove('hidden');
    console.log('Category modal opened (create)');
}

function editCategory(cat) {
    catMultilangTempKey = null;
    document.getElementById('catModalTitle').textContent = '<?= __('services.categories.edit') ?>';
    document.getElementById('catId').value = cat.id;
    var tr = catTranslations[cat.id] || {};
    document.getElementById('catName').value = tr.name || cat.name;
    document.getElementById('catSlug').value = cat.slug;
    document.getElementById('catDescription').value = tr.description || cat.description || '';
    document.getElementById('catParentId').value = cat.parent_id || '';
    document.getElementById('catIsActive').checked = !!parseInt(cat.is_active);
    document.getElementById('categoryModal').classList.remove('hidden');
    console.log('Category modal opened (edit)', cat.id);
}

function closeCategoryModal() {
    document.getElementById('categoryModal').classList.add('hidden');
}

function saveCategory() {
    var id = document.getElementById('catId').value;
    var formData = new FormData();
    formData.append('action', id ? 'update_category' : 'create_category');
    if (id) formData.append('id', id);
    formData.append('name', document.getElementById('catName').value);
    formData.append('slug', document.getElementById('catSlug').value);
    formData.append('description', document.getElementById('catDescription').value);
    formData.append('parent_id', document.getElementById('catParentId').value);
    if (document.getElementById('catIsActive').checked) formData.append('is_active', '1');

    fetch(categoriesApiUrl, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                if (!id && data.id && catMultilangTempKey) {
                    migrateCategoryMultilangKeys(catMultilangTempKey, 'category.' + data.id);
                }
                showAlert(data.message, 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                showAlert(data.message, 'error');
            }
        })
        .catch(err => { showAlert('Error: ' + err, 'error'); });
}

function migrateCategoryMultilangKeys(oldPrefix, newPrefix) {
    var fields = ['name', 'description'];
    fields.forEach(function(field) {
        var oldKey = oldPrefix + '.' + field;
        var newKey = newPrefix + '.' + field;
        console.log('[Categories] Migrate multilang key:', oldKey, '→', newKey);
        fetch('<?= $adminUrl ?>/api/translations?action=rename', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ old_key: oldKey, new_key: newKey })
        })
            .then(r => r.json())
            .then(data => console.log('[Categories] Migrate result:', data))
            .catch(err => console.error('[Categories] Migration error:', err));
    });
}

function deleteCategory(id) {
    if (!confirm('<?= __('services.categories.confirm_delete') ?>')) return;
    var formData = new FormData();
    formData.append('action', 'delete_category');
    formData.append('id', id);

    fetch(categoriesApiUrl, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                showAlert(data.message, 'error');
            }
        })
        .catch(err => { showAlert('Error: ' + err, 'error'); });
}

function showAlert(msg, type) {
    var box = document.getElementById('alertBox');
    box.className = 'mb-6 p-4 rounded-lg border ' + (type === 'success'
        ? 'bg-green-50 dark:bg-green-900/30 text-green-800 dark:text-green-300 border-green-200 dark:border-green-800'
        : 'bg-red-50 dark:bg-red-900/30 text-red-800 dark:text-red-300 border-red-200 dark:border-red-800');
    box.textContent = msg;
    box.classList.remove('hidden');
    setTimeout(() => box.classList.add('hidden'), 5000);
}

// SortableJS 초기화
document.addEventListener('DOMContentLoaded', function() {
    var el = document.getElementById('categorySortable');
    if (el && typeof Sortable !== 'undefined') {
        new Sortable(el, {
            handle: '.cat-drag-handle',
            animation: 200,
            forceFallback: true,
            ghostClass: 'cat-sortable-ghost',
            dragClass: 'cat-sortable-drag',
            fallbackClass: 'cat-sortable-fallback',
            onEnd: function() {
                var ids = [];
                el.querySelectorAll('.cat-sort-item').forEach(function(item) {
                    ids.push(item.dataset.id);
                });
                var formData = new FormData();
                formData.append('action', 'reorder_categories');
                formData.append('ids', JSON.stringify(ids));
                fetch(categoriesApiUrl, { method: 'POST', body: formData })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) showAlert(data.message, 'success');
                    });
                console.log('Categories reordered:', ids);
            }
        });
    }
});
</script>
