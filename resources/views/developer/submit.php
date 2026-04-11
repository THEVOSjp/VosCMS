<?php
/**
 * Developer - 아이템 제출 페이지
 */
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['developer_id'])) { header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/developer/login'); exit; }

include __DIR__ . '/partials/_layout_head.php';
$pageTitle = __mp('dev_submit');

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
try {
    $pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4", $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
    $categories = $pdo->query("SELECT * FROM {$prefix}mp_categories WHERE is_active = 1 ORDER BY sort_order")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $categories = []; }
$locale = $_mpLocale ?? 'ko';
?>

<div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white mb-6"><?= __mp('submit_title') ?></h1>

    <div id="result" class="hidden mb-4 p-4 rounded-lg text-sm"></div>

    <form id="submitForm" enctype="multipart/form-data" class="space-y-6">
        <!-- 기본 정보 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <h2 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wider mb-4"><?= __mp('submit_basic_info') ?></h2>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __mp('submit_item_type') ?> *</label>
                    <select name="item_type" required class="w-full px-3 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm">
                        <option value="plugin"><?= __mp('plugins') ?> (Plugin)</option>
                        <option value="widget"><?= __mp('widgets') ?> (Widget)</option>
                        <option value="theme"><?= __mp('themes') ?> (Theme)</option>
                        <option value="skin"><?= __mp('skins') ?> (Skin)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __mp('submit_name_en') ?> *</label>
                    <input type="text" id="name_en" required class="w-full px-3 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-900 dark:text-white bg-white dark:bg-zinc-700" placeholder="<?= __mp('submit_name_en_hint') ?>">
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __mp('submit_name_local') ?></label>
                    <input type="text" id="name_local" class="w-full px-3 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-900 dark:text-white bg-white dark:bg-zinc-700" placeholder="<?= __mp('submit_name_local_hint') ?>">
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __mp('submit_short_desc') ?></label>
                    <input type="text" id="short_desc" maxlength="200" class="w-full px-3 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-900 dark:text-white bg-white dark:bg-zinc-700" placeholder="<?= __mp('submit_short_desc_hint') ?>">
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __mp('submit_description') ?></label>
                    <div id="description-editor"></div>
                    <textarea id="description" class="hidden"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __mp('submit_category') ?></label>
                        <select name="category_id" class="w-full px-3 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm">
                            <option value=""><?= __mp('submit_category_none') ?></option>
                            <?php foreach ($categories as $cat):
                                $cn = json_decode($cat['name'], true);
                            ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cn[$locale] ?? $cn['en'] ?? $cat['slug']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __mp('submit_tags') ?></label>
                        <input type="text" name="tags" class="w-full px-3 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-900 dark:text-white bg-white dark:bg-zinc-700" placeholder="<?= __mp('submit_tags_hint') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- 파일 업로드 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <h2 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wider mb-4"><?= __mp('submit_files') ?></h2>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __mp('submit_package') ?> * <span class="text-zinc-400 font-normal"><?= __mp('submit_package_max') ?></span></label>
                    <input type="file" name="package" accept=".zip" required class="w-full text-sm text-zinc-700 dark:text-zinc-300 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700 file:font-medium hover:file:bg-indigo-100">
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __mp('submit_icon') ?></label>
                    <input type="file" name="icon" accept="image/*" class="w-full text-sm text-zinc-700 dark:text-zinc-300 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-zinc-50 file:text-zinc-700 file:font-medium">
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __mp('submit_screenshots') ?></label>
                    <input type="file" name="screenshots[]" accept="image/*" multiple class="w-full text-sm text-zinc-700 dark:text-zinc-300 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-zinc-50 file:text-zinc-700 file:font-medium">
                </div>
            </div>
        </div>

        <!-- 버전 + 가격 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <h2 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wider mb-4"><?= __mp('submit_version_price') ?></h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __mp('submit_version') ?> *</label>
                    <input type="text" name="version" required value="1.0.0" class="w-full px-3 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-900 dark:text-white bg-white dark:bg-zinc-700">
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __mp('submit_price') ?></label>
                    <div class="flex gap-2">
                        <input type="number" name="price" value="0" min="0" step="0.01" class="flex-1 px-3 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-900 dark:text-white bg-white dark:bg-zinc-700">
                        <select name="currency" class="px-3 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-900 dark:text-white bg-white dark:bg-zinc-700">
                            <option value="USD">USD</option>
                            <option value="JPY">JPY</option>
                            <option value="KRW">KRW</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __mp('submit_min_voscms') ?></label>
                    <input type="text" name="min_voscms" placeholder="2.0.0" class="w-full px-3 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-900 dark:text-white bg-white dark:bg-zinc-700">
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __mp('submit_min_php') ?></label>
                    <input type="text" name="min_php" placeholder="8.1" class="w-full px-3 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-900 dark:text-white bg-white dark:bg-zinc-700">
                </div>
            </div>
            <div class="mt-4">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __mp('submit_changelog') ?></label>
                <textarea name="changelog" rows="3" class="w-full px-3 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-900 dark:text-white bg-white dark:bg-zinc-700" placeholder="<?= __mp('submit_changelog_hint') ?>"></textarea>
            </div>
        </div>

        <button type="submit" id="submitBtn" class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition-colors">
            <?= __mp('submit_btn') ?>
        </button>
    </form>
</div>

<!-- Summernote WYSIWYG -->
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
<script>
$(document).ready(function() {
    $('#description-editor').summernote({
        placeholder: '<?= __mp('submit_description_hint') ?>',
        height: 250,
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'italic', 'underline', 'strikethrough']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['table', ['table']],
            ['insert', ['link', 'picture', 'video']],
            ['view', ['codeview', 'help']]
        ],
        callbacks: {
            onChange: function(contents) {
                document.getElementById('description').value = contents;
            }
        }
    });

    // 다크모드 대응
    if (document.documentElement.classList.contains('dark')) {
        $('.note-editor').addClass('bg-zinc-700 border-zinc-600');
        $('.note-editing-area .note-editable').css({'background':'#3f3f46','color':'#fff'});
        $('.note-toolbar').css({'background':'#27272a','border-color':'#52525b'});
        $('.note-toolbar .note-btn').css({'color':'#d4d4d8','background':'transparent','border':'none'});
    }
});
</script>
<style>
.note-editor.note-frame { border-radius: 0.5rem; overflow: hidden; border-color: #d4d4d8; }
.dark .note-editor.note-frame { border-color: #52525b; }
.dark .note-editing-area .note-editable { background: #3f3f46; color: #fff; }
.dark .note-toolbar { background: #27272a; border-color: #52525b; }
.dark .note-toolbar .note-btn { color: #d4d4d8; }
.dark .note-toolbar .note-btn:hover { background: #52525b; }
.dark .note-modal .modal-content { background: #27272a; color: #fff; }
.dark .note-modal .form-control { background: #3f3f46; color: #fff; border-color: #52525b; }
.note-editor .note-editable { min-height: 200px; font-family: 'Pretendard', sans-serif; font-size: 14px; }
</style>

<script>
document.getElementById('submitForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.textContent = '<?= __mp('submit_submitting') ?>';

    const fd = new FormData(this);
    // name을 JSON으로 구성
    const nameEn = document.getElementById('name_en').value;
    const nameLocal = document.getElementById('name_local').value;
    const nameObj = { en: nameEn };
    if (nameLocal) nameObj['<?= $_mpLocale ?>'] = nameLocal;
    fd.set('name', JSON.stringify(nameObj));
    fd.set('short_description', JSON.stringify({
        ko: document.getElementById('short_desc').value,
        en: document.getElementById('short_desc').value,
    }));
    const descHtml = $('#description-editor').summernote('code') || '';
    fd.set('description', JSON.stringify({
        ko: descHtml,
        en: descHtml,
    }));
    // tags를 JSON 배열로
    const tags = fd.get('tags');
    if (tags) fd.set('tags', JSON.stringify(tags.split(',').map(t => t.trim()).filter(Boolean)));

    try {
        const res = await fetch('<?= $baseUrl ?>/api/developer/submit', { method: 'POST', body: fd });
        const data = await res.json();
        const el = document.getElementById('result');
        if (data.success) {
            el.className = 'mb-4 p-4 rounded-lg text-sm bg-green-50 text-green-700';
            el.innerHTML = '<?= __mp('submit_success') ?> (ID: ' + data.queue_id + ')<br><a href="<?= $baseUrl ?>/developer/my-items" class="underline font-medium"><?= __mp('submit_check_items') ?></a>';
        } else {
            el.className = 'mb-4 p-4 rounded-lg text-sm bg-red-50 text-red-700';
            el.textContent = data.message || 'Submission failed';
        }
        el.style.display = 'block';
        window.scrollTo({top: 0, behavior: 'smooth'});
    } catch (err) {
        alert('Network error');
    }
    btn.disabled = false;
    btn.textContent = '<?= __mp('submit_btn') ?>';
});
</script>

<?php include __DIR__ . '/partials/_layout_foot.php'; ?>
