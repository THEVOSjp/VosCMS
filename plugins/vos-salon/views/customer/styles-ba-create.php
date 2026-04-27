<?php
/**
 * Before / After 간편 등록 페이지
 * /styles/before-after/create
 * 사진 2장(Before + After)만 올리면 끝
 */

require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
if (!\RzxLib\Core\Auth\Auth::check()) {
    header('Location: ' . ($config['app_url'] ?? '') . '/login?redirect=styles/before-after/create');
    exit;
}

$currentUser = \RzxLib\Core\Auth\Auth::user();
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

$pageTitle = $_wt('title', 'Before / After') . ' ' . (__('shop.stylebook.create_btn') ?? '등록');
$seoContext = ['type' => 'sub', 'subpage_title' => $pageTitle];

// 사용자의 사업장
$myShops = [];
try {
    $myShopsStmt = $pdo->prepare("SELECT id, name FROM {$prefix}shops WHERE user_id = ? AND status = 'active' ORDER BY name");
    $myShopsStmt->execute([$currentUser['id']]);
    $myShops = $myShopsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (\Throwable $e) {}
$isAdmin = in_array($currentUser['role'] ?? '', ['admin', 'supervisor', 'manager', 'staff']);
if ($isAdmin) {
    try { $myShops = $pdo->query("SELECT id, name FROM {$prefix}shops WHERE status = 'active' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC); } catch (\Throwable $e) {}
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = trim($_POST['content'] ?? '');
    $shopId = (int)($_POST['shop_id'] ?? 0) ?: null;
    $staffName = trim($_POST['staff_name'] ?? '');

    $images = [];
    $uploadDir = BASE_PATH . '/storage/uploads/styles/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    // Before 이미지
    if (!empty($_FILES['before_image']) && $_FILES['before_image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['before_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            $fn = 'style_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($_FILES['before_image']['tmp_name'], $uploadDir . $fn)) {
                $images[] = ['url' => '/storage/uploads/styles/' . $fn, 'type' => 'before', 'media' => 'image'];
            }
        }
    }

    // After 이미지
    if (!empty($_FILES['after_image']) && $_FILES['after_image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['after_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            $fn = 'style_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($_FILES['after_image']['tmp_name'], $uploadDir . $fn)) {
                $images[] = ['url' => '/storage/uploads/styles/' . $fn, 'type' => 'after', 'media' => 'image'];
            }
        }
    }

    if (count($images) < 2) {
        $errors[] = $_wt('error_need_both', 'Before와 After 사진을 모두 올려주세요.');
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO {$prefix}before_afters (user_id, shop_id, staff_name, category, before_image, after_image, content, status) VALUES (?, ?, ?, 'hair', ?, ?, ?, 'active')");
        $stmt->execute([$currentUser['id'], $shopId, $staffName ?: null, $images[0]['url'], $images[1]['url'], $content]);
        $success = true;
    }
}
?>

<?php if ($success): ?>
<div class="max-w-lg mx-auto px-4 py-16 text-center">
    <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
    </div>
    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white mb-2"><?= __('shop.stylebook.create_success') ?? '등록되었습니다!' ?></h1>
    <div class="flex items-center justify-center gap-3 mt-6">
        <a href="<?= $baseUrl ?>/styles/before-after" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"><?= $_wt('title') ?></a>
        <a href="<?= $baseUrl ?>/styles/before-after/create" class="px-6 py-3 border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition"><?= __('shop.stylebook.create_another') ?? '추가 등록' ?></a>
    </div>
</div>

<?php else: ?>
<div class="max-w-lg mx-auto px-4 sm:px-6 py-8">
    <!-- 네비 -->
    <a href="<?= $baseUrl ?>/styles/before-after" class="inline-flex items-center gap-1 text-sm text-zinc-500 dark:text-zinc-400 hover:text-blue-600 transition mb-4">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Before / After
    </a>

    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white mb-1"><?= htmlspecialchars($pageTitle) ?></h1>
    <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-6"><?= $_wt('create_desc', 'Before와 After 사진 2장만 올려주세요.') ?></p>

    <?php if (!empty($errors)): ?>
    <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
        <?php foreach ($errors as $e): ?><p class="text-sm text-red-700 dark:text-red-300"><?= htmlspecialchars($e) ?></p><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-6">

        <!-- Before / After 사진 -->
        <div class="grid grid-cols-2 gap-4">
            <!-- Before -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= $_wt('before') ?> <span class="text-red-500">*</span></label>
                <label id="beforeBox" class="block aspect-square rounded-xl border-2 border-dashed border-zinc-300 dark:border-zinc-600 cursor-pointer hover:border-blue-400 transition overflow-hidden relative bg-zinc-50 dark:bg-zinc-800">
                    <div id="beforePlaceholder" class="flex flex-col items-center justify-center h-full text-zinc-400">
                        <svg class="w-10 h-10 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span class="text-xs"><?= $_wt('before') ?></span>
                    </div>
                    <img id="beforePreview" class="hidden absolute inset-0 w-full h-full object-cover" alt="">
                    <input type="file" name="before_image" accept="image/*" class="hidden" onchange="previewBA(this,'before')">
                </label>
            </div>
            <!-- After -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= $_wt('after') ?> <span class="text-red-500">*</span></label>
                <label id="afterBox" class="block aspect-square rounded-xl border-2 border-dashed border-zinc-300 dark:border-zinc-600 cursor-pointer hover:border-blue-400 transition overflow-hidden relative bg-zinc-50 dark:bg-zinc-800">
                    <div id="afterPlaceholder" class="flex flex-col items-center justify-center h-full text-zinc-400">
                        <svg class="w-10 h-10 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span class="text-xs"><?= $_wt('after') ?></span>
                    </div>
                    <img id="afterPreview" class="hidden absolute inset-0 w-full h-full object-cover" alt="">
                    <input type="file" name="after_image" accept="image/*" class="hidden" onchange="previewBA(this,'after')">
                </label>
            </div>
        </div>

        <!-- 한줄 설명 (선택) -->
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('shop.stylebook.description') ?? '설명' ?> <span class="text-xs text-zinc-400">(<?= __('common.optional') ?? '선택' ?>)</span></label>
            <input type="text" name="content" value="<?= htmlspecialchars($_POST['content'] ?? '') ?>" maxlength="200"
                   class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm"
                   placeholder="<?= $_wt('caption_placeholder', '시술 내용을 간단히 적어주세요') ?>">
        </div>

        <!-- 매장/디자이너 (선택) -->
        <?php if (!empty($myShops)): ?>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('shop.stylebook.select_shop') ?? '매장' ?></label>
                <select name="shop_id" class="w-full px-3 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm">
                    <option value=""><?= __('common.select') ?? '선택' ?></option>
                    <?php foreach ($myShops as $s): ?>
                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('shop.stylebook.designer') ?? '디자이너' ?></label>
                <input type="text" name="staff_name" value="<?= htmlspecialchars($_POST['staff_name'] ?? '') ?>"
                       class="w-full px-3 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm"
                       placeholder="<?= __('shop.stylebook.designer_placeholder') ?? '디자이너 이름' ?>">
            </div>
        </div>
        <?php endif; ?>

        <!-- 제출 -->
        <!-- 주의사항 -->
        <p class="text-xs text-amber-600 dark:text-amber-400 mb-4"><?= $_wt('create_warning', '⚠️ 저작권 등의 문제가 있는 게시물 삭제 시 등록 시 취득한 포인트는 차감됩니다.') ?></p>

        <button type="submit" class="w-full py-3 text-sm font-medium bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
            <?= __('shop.stylebook.submit') ?? '등록하기' ?>
        </button>
    </form>
</div>

<script>
function previewBA(input, type) {
    if (!input.files[0]) return;
    var reader = new FileReader();
    reader.onload = function(e) {
        var img = document.getElementById(type + 'Preview');
        img.src = e.target.result;
        img.classList.remove('hidden');
        document.getElementById(type + 'Placeholder').classList.add('hidden');
        document.getElementById(type + 'Box').classList.remove('border-dashed');
        document.getElementById(type + 'Box').classList.add('border-solid', 'border-blue-400');
    };
    reader.readAsDataURL(input.files[0]);
}
</script>
<?php endif; ?>
