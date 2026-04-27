<?php
/**
 * 스타일 포스트 등록 페이지
 * /styles/create
 * 로그인 필수. 사업장 운영자, 일반 고객, 관리자 모두 등록 가능.
 */

require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
if (!\RzxLib\Core\Auth\Auth::check()) {
    header('Location: ' . ($config['app_url'] ?? '') . '/login?redirect=styles/create');
    exit;
}

$currentUser = \RzxLib\Core\Auth\Auth::user();
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$currentLocale = $config['locale'] ?? (function_exists('current_locale') ? current_locale() : 'ko');

// 플러그인 번역 로드
$_shopLang = @include(BASE_PATH . '/plugins/vos-shop/lang/' . $currentLocale . '/shop.php');
if (!is_array($_shopLang)) $_shopLang = @include(BASE_PATH . '/plugins/vos-shop/lang/ko/shop.php');
if (is_array($_shopLang) && class_exists('\RzxLib\Core\I18n\Translator')) {
    \RzxLib\Core\I18n\Translator::merge('shop', $_shopLang);
}

$pageTitle = __('shop.stylebook.create_title') ?? '스타일 등록';
$seoContext = ['type' => 'sub', 'subpage_title' => $pageTitle];

// 사용자의 사업장 (운영자인 경우)
$myShops = [];
try {
    $myShopsStmt = $pdo->prepare("SELECT id, name FROM {$prefix}shops WHERE user_id = ? AND status = 'active' ORDER BY name");
    $myShopsStmt->execute([$currentUser['id']]);
    $myShops = $myShopsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (\Throwable $e) {}

// 관리자인 경우 모든 사업장 선택 가능
$isAdmin = in_array($currentUser['role'] ?? '', ['admin', 'supervisor', 'manager', 'staff']);
$allShops = [];
if ($isAdmin) {
    try {
        $allShopsStmt = $pdo->query("SELECT id, name FROM {$prefix}shops WHERE status = 'active' ORDER BY name");
        $allShops = $allShopsStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (\Throwable $e) {}
}

$shopOptions = $isAdmin ? $allShops : $myShops;

// DB에서 스타일 태그 로드
$styleTags = [];
try {
    $styleTags = $pdo->query("SELECT * FROM {$prefix}style_tags WHERE is_active = 1 ORDER BY category, sort_order")->fetchAll(PDO::FETCH_ASSOC);
} catch (\Throwable $e) {}

// 카테고리 목록
$categories = [
    'hair' => __('shop.stylebook.cat_hair') ?? '헤어',
    'nail' => __('shop.stylebook.cat_nail') ?? '네일',
    'skin' => __('shop.stylebook.cat_skin') ?? '스킨케어',
    'makeup' => __('shop.stylebook.cat_makeup') ?? '메이크업',
    'other' => __('shop.stylebook.cat_other') ?? '기타',
];

// POST 처리
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shopId = (int)($_POST['shop_id'] ?? 0) ?: null;
    $staffName = trim($_POST['staff_name'] ?? '');
    $category = trim($_POST['category'] ?? 'hair');
    $content = trim($_POST['content'] ?? '');
    $tags = $_POST['tags'] ?? [];
    if (is_string($tags)) { $tags = array_filter(array_map('trim', preg_split('/[,\s#]+/', $tags))); }

    // 사진/동영상 업로드 (최대 5개)
    $images = [];
    $imageTypes = $_POST['image_types'] ?? [];
    $allowedImg = ['jpg','jpeg','png','gif','webp'];
    $allowedVideo = ['mp4','mov','webm'];
    if (!empty($_FILES['images'])) {
        $uploadDir = BASE_PATH . '/storage/uploads/styles/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $imgCount = 0; $vidCount = 0;
        for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
            if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;
            $ext = strtolower(pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION));
            $isVideo = in_array($ext, $allowedVideo);
            $isImage = in_array($ext, $allowedImg);
            if (!$isImage && !$isVideo) continue;
            if ($isImage && $imgCount >= 10) continue;
            if ($isVideo && $vidCount >= 1) continue;
            if ($isVideo && $_FILES['images']['size'][$i] > 50 * 1024 * 1024) continue;
            $filename = 'style_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $uploadDir . $filename)) {
                $type = $imageTypes[$i] ?? 'result';
                $media = $isVideo ? 'video' : 'image';
                $images[] = ['url' => '/storage/uploads/styles/' . $filename, 'type' => $type, 'media' => $media];
                if ($isVideo) $vidCount++; else $imgCount++;
            }
        }
    }

    if (empty($images)) $errors[] = __('shop.stylebook.error_no_media') ?? '사진 또는 동영상을 1개 이상 업로드해주세요.';

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO {$prefix}style_posts (user_id, shop_id, staff_name, category, images, tags, content, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
        $stmt->execute([
            $currentUser['id'], $shopId, $staffName ?: null, $category,
            json_encode($images), json_encode(array_values($tags)), $content
        ]);
        $success = true;
    }
}
?>

<?php if ($success): ?>
<div class="max-w-lg mx-auto px-4 py-16 text-center">
    <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
    </div>
    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white mb-2"><?= __('shop.stylebook.create_success') ?? '스타일이 등록되었습니다!' ?></h1>
    <div class="flex items-center justify-center gap-3 mt-6">
        <a href="<?= $baseUrl ?>/styles" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"><?= __('shop.stylebook.title') ?? '스타일북' ?></a>
        <a href="<?= $baseUrl ?>/styles/create" class="px-6 py-3 border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition"><?= __('shop.stylebook.create_another') ?? '추가 등록' ?></a>
    </div>
</div>

<?php else: ?>
<div class="max-w-2xl mx-auto px-4 sm:px-6 py-8">
    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white mb-2"><?= htmlspecialchars($pageTitle) ?></h1>
    <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-6"><?= __('shop.stylebook.create_desc') ?? '시술 결과나 스타일 사진을 공유해주세요.' ?></p>

    <?php if (!empty($errors)): ?>
    <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
        <?php foreach ($errors as $e): ?>
        <p class="text-sm text-red-700 dark:text-red-300"><?= htmlspecialchars($e) ?></p>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-6">

        <!-- 사진 업로드 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2"><?= __('shop.stylebook.media') ?? '사진/동영상' ?> <span class="text-red-500">*</span></h2>
            <p class="text-xs text-zinc-400 mb-3"><?= __('shop.stylebook.media_hint') ?? '사진 또는 동영상을 올려주세요. Before/After도 함께 올릴 수 있습니다. (최대 5개, 동영상 50MB 이하)' ?></p>

            <div id="stylePhotosGrid" class="grid grid-cols-3 sm:grid-cols-5 gap-2 mb-3"></div>
            <label class="inline-flex items-center gap-2 px-4 py-2.5 border-2 border-dashed border-zinc-300 dark:border-zinc-600 rounded-lg cursor-pointer hover:border-blue-400 transition text-sm text-zinc-500" id="addStylePhotoBtn">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <?= __('shop.stylebook.add_media') ?? '사진 · 동영상 추가' ?>
                <input type="file" accept="image/*,video/*" multiple class="hidden" id="stylePhotoInput">
            </label>
            <div id="styleFileInputs"></div>
        </div>

        <!-- 카테고리 + 태그 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4"><?= __('shop.stylebook.style_info') ?? '스타일 정보' ?></h2>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('shop.stylebook.category') ?? '카테고리' ?></label>
                    <select name="category" class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                        <?php foreach ($categories as $ck => $cl): ?>
                        <option value="<?= $ck ?>" <?= ($_POST['category'] ?? 'hair') === $ck ? 'selected' : '' ?>><?= htmlspecialchars($cl) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('shop.stylebook.tags') ?? '태그' ?></label>
                        <?php if ($isAdmin): ?>
                        <a href="<?= $baseUrl ?>/styles/settings" class="text-xs text-blue-600 hover:underline flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <?= __('shop.stylebook.tag_settings') ?? '태그 관리' ?>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php
                    $selectedTags = $_POST['tags'] ?? [];
                    $tagsByCategory = [];
                    foreach ($styleTags as $st) { $tagsByCategory[$st['category']][] = $st; }
                    ?>
                    <div class="space-y-3">
                        <?php foreach ($tagsByCategory as $tCat => $tList):
                            $catLabel = $categories[$tCat] ?? $tCat;
                        ?>
                        <div>
                            <p class="text-xs text-zinc-400 mb-1.5"><?= htmlspecialchars($catLabel) ?></p>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach ($tList as $st):
                                    $stName = json_decode($st['name'], true) ?: [];
                                    $stLabel = $stName[$currentLocale] ?? $stName['ko'] ?? $st['slug'];
                                ?>
                                <label class="flex items-center gap-1.5 px-3 py-1.5 border border-zinc-200 dark:border-zinc-600 rounded-full text-sm text-zinc-600 dark:text-zinc-400 cursor-pointer hover:bg-blue-50 dark:hover:bg-blue-900/20 has-[:checked]:bg-blue-100 has-[:checked]:border-blue-300 has-[:checked]:text-blue-700 dark:has-[:checked]:bg-blue-900/30 dark:has-[:checked]:text-blue-400 transition">
                                    <input type="checkbox" name="tags[]" value="<?= htmlspecialchars($st['slug']) ?>" <?= in_array($st['slug'], (array)$selectedTags) ? 'checked' : '' ?> class="hidden">
                                    <?= htmlspecialchars($stLabel) ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('shop.stylebook.description') ?? '설명' ?></label>
                    <textarea name="content" rows="3" maxlength="500"
                              class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white"
                              placeholder="<?= __('shop.stylebook.description_placeholder') ?? '스타일에 대한 설명을 적어주세요.' ?>"><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- 매장/디자이너 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4"><?= __('shop.stylebook.shop_info') ?? '매장/디자이너 정보' ?></h2>
            <p class="text-xs text-zinc-400 mb-3"><?= __('shop.stylebook.shop_info_hint') ?? '선택 사항입니다. 매장과 연결하면 매장 페이지에도 표시됩니다.' ?></p>

            <div class="space-y-4">
                <?php if (!empty($shopOptions)): ?>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('shop.stylebook.select_shop') ?? '매장 선택' ?></label>
                    <select name="shop_id" class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                        <option value=""><?= __('common.select') ?? '선택' ?></option>
                        <?php foreach ($shopOptions as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= (int)($_POST['shop_id'] ?? 0) === (int)$s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('shop.stylebook.designer') ?? '디자이너' ?></label>
                    <input type="text" name="staff_name" value="<?= htmlspecialchars($_POST['staff_name'] ?? '') ?>"
                           class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white"
                           placeholder="<?= __('shop.stylebook.designer_placeholder') ?? '시술한 디자이너 이름' ?>">
                </div>
            </div>
        </div>

        <!-- 주의사항 -->
        <p class="text-xs text-amber-600 dark:text-amber-400"><?= __('shop.stylebook.create_warning') ?? '⚠️ 저작권 등의 문제가 있는 게시물 삭제 시 등록 시 취득한 포인트는 차감됩니다.' ?></p>

        <!-- 제출 -->
        <div class="flex justify-end gap-3">
            <a href="<?= $baseUrl ?>/styles" class="px-6 py-3 text-sm font-medium text-zinc-600 dark:text-zinc-400 border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition"><?= __('common.buttons.cancel') ?? '취소' ?></a>
            <button type="submit" class="px-6 py-3 text-sm font-medium bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"><?= __('shop.stylebook.submit') ?? '등록하기' ?></button>
        </div>
    </form>
</div>

<script>
var styleImgCount = 0, styleVidCount = 0;

document.getElementById('stylePhotoInput').addEventListener('change', function() {
    var grid = document.getElementById('stylePhotosGrid');
    var container = document.getElementById('styleFileInputs');
    var files = this.files;

    for (var i = 0; i < files.length; i++) {
        var file = files[i];
        var isImage = file.type.startsWith('image/');
        var isVideo = file.type.startsWith('video/');
        if (!isImage && !isVideo) continue;
        if (isImage && styleImgCount >= 10) {
            if (typeof showResultModal === 'function') showResultModal(false, '<?= __('shop.stylebook.img_limit') ?? '사진은 최대 10장까지 가능합니다.' ?>');
            continue;
        }
        if (isVideo && styleVidCount >= 1) {
            if (typeof showResultModal === 'function') showResultModal(false, '<?= __('shop.stylebook.vid_limit') ?? '동영상은 최대 1개만 가능합니다.' ?>');
            continue;
        }
        if (isVideo && file.size > 50 * 1024 * 1024) {
            var sizeMB = (file.size / 1024 / 1024).toFixed(1);
            if (typeof showResultModal === 'function') {
                showResultModal(false, '<?= __('shop.stylebook.video_size_limit') ?? '동영상은 50MB 이하만 가능합니다.' ?>' + ' (' + sizeMB + 'MB)');
            } else {
                alert('<?= __('shop.stylebook.video_size_limit') ?? '동영상은 50MB 이하만 가능합니다.' ?>' + ' (' + sizeMB + 'MB)');
            }
            continue;
        }

        if (isVideo) styleVidCount++; else styleImgCount++;
        var idx = styleImgCount + styleVidCount;

        // 개별 file input 생성
        var dt = new DataTransfer();
        dt.items.add(file);
        var hiddenInput = document.createElement('input');
        hiddenInput.type = 'file';
        hiddenInput.name = 'images[]';
        hiddenInput.style.display = 'none';
        hiddenInput.files = dt.files;
        hiddenInput.id = 'styleFile_' + idx;
        container.appendChild(hiddenInput);

        // 미리보기
        var div = document.createElement('div');
        div.className = 'relative aspect-[3/4] rounded-lg overflow-hidden bg-zinc-100 dark:bg-zinc-700 group';
        div.id = 'styleThumb_' + idx;

        if (isVideo) {
            var vid = document.createElement('video');
            vid.className = 'w-full h-full object-cover';
            vid.muted = true;
            vid.src = URL.createObjectURL(file);
            vid.addEventListener('loadeddata', function() { this.currentTime = 1; });
            div.appendChild(vid);
            var playIcon = document.createElement('div');
            playIcon.className = 'absolute inset-0 flex items-center justify-center pointer-events-none';
            playIcon.innerHTML = '<div class="w-10 h-10 bg-black/50 rounded-full flex items-center justify-center"><svg class="w-5 h-5 text-white ml-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg></div>';
            div.appendChild(playIcon);
        } else {
            var img = document.createElement('img');
            img.className = 'w-full h-full object-cover';
            var reader = new FileReader();
            reader.onload = (function(el) { return function(e) { el.src = e.target.result; }; })(img);
            reader.readAsDataURL(file);
            div.appendChild(img);
        }

        var sel = document.createElement('select');
        sel.name = 'image_types[]';
        sel.className = 'absolute bottom-1 left-1 right-1 text-[10px] px-1 py-0.5 rounded bg-black/50 text-white border-0';
        sel.innerHTML = '<option value="result"><?= __('shop.stylebook.type_result') ?? '결과' ?></option><option value="before"><?= __('shop.stylebook.type_before') ?? 'Before' ?></option><option value="after"><?= __('shop.stylebook.type_after') ?? 'After' ?></option>';

        var removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'absolute top-1 right-1 w-5 h-5 bg-red-500 text-white rounded-full text-[10px] flex items-center justify-center opacity-0 group-hover:opacity-100 transition';
        removeBtn.innerHTML = '&times;';
        removeBtn.onclick = (function(thumbId, fileId, isVid) { return function() {
            document.getElementById(thumbId).remove();
            document.getElementById(fileId).remove();
            if (isVid) styleVidCount--; else styleImgCount--;
            document.getElementById('addStylePhotoBtn').style.display = (styleImgCount >= 10 && styleVidCount >= 1) ? 'none' : '';
        }; })('styleThumb_' + idx, 'styleFile_' + idx, isVideo);

        div.appendChild(sel);
        div.appendChild(removeBtn);
        grid.appendChild(div);
    }
    document.getElementById('addStylePhotoBtn').style.display = (styleImgCount >= 10 && styleVidCount >= 1) ? 'none' : '';
    this.value = '';
});
</script>
<?php endif; ?>
