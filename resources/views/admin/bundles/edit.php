<?php
/**
 * 번들(세트 서비스) 상세 관리 페이지
 * - 기본 정보 수정 (이름, 설명, 가격, 이벤트 할인)
 * - 대표 이미지 업로드
 * - 포함 서비스 구성
 * - 스태프 연동
 */

if (!function_exists('__')) {
    require_once BASE_PATH . '/rzxlib/Core/Helpers/lang.php';
}

$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');
$currency = $siteSettings['service_currency'] ?? $config['currency'] ?? 'JPY';
$uploadDir = '/storage/uploads/bundles/';
$uploadPath = BASE_PATH . $uploadDir;

// DB 연결
try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
} catch (PDOException $e) {
    die('DB Error');
}

// AJAX 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json; charset=utf-8');
    include __DIR__ . '/edit-api.php';
    exit;
}

// 이미지 업로드 처리 (multipart)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['bundle_image'])) {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $file = $_FILES['bundle_image'];
        if ($file['error'] !== UPLOAD_ERR_OK) throw new \Exception('Upload error');
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','webp','gif'])) throw new \Exception('Invalid format');
        if (!is_dir($uploadPath)) mkdir($uploadPath, 0755, true);
        $filename = 'bundle_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = $uploadPath . $filename;
        if (!move_uploaded_file($file['tmp_name'], $dest)) throw new \Exception('Move failed');
        $imageUrl = ltrim($uploadDir, '/') . $filename;
        // DB 업데이트
        $pdo->prepare("UPDATE {$prefix}service_bundles SET image = ?, updated_at = NOW() WHERE id = ?")->execute([$imageUrl, $bundleId]);
        echo json_encode(['success' => true, 'url' => $baseUrl . '/' . $imageUrl]);
    } catch (\Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// 번들 데이터 로드
$stmt = $pdo->prepare("SELECT * FROM {$prefix}service_bundles WHERE id = ?");
$stmt->execute([$bundleId]);
$bundle = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$bundle) {
    header("Location: {$adminUrl}/bundles");
    exit;
}

// 번들 서비스 항목
$itemsStmt = $pdo->prepare("
    SELECT bi.service_id, bi.sort_order, s.name, s.price, s.duration, s.image
    FROM {$prefix}service_bundle_items bi
    JOIN {$prefix}services s ON bi.service_id = s.id
    WHERE bi.bundle_id = ?
    ORDER BY bi.sort_order
");
$itemsStmt->execute([$bundleId]);
$bundleItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

// 전체 서비스 목록
$allServices = $pdo->query("SELECT id, name, price, duration, image FROM {$prefix}services WHERE is_active = 1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// 연동된 스태프
$staffBundleStmt = $pdo->prepare("SELECT staff_id FROM {$prefix}staff_bundles WHERE bundle_id = ?");
$staffBundleStmt->execute([$bundleId]);
$linkedStaffIds = $staffBundleStmt->fetchAll(PDO::FETCH_COLUMN);

// 전체 스태프 목록
$allStaff = $pdo->query("SELECT id, name, avatar, is_active, is_visible FROM {$prefix}staff WHERE is_active = 1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// 이벤트 활성 여부
$now = date('Y-m-d H:i:s');
$isEventActive = !empty($bundle['event_price']) && !empty($bundle['event_start']) && !empty($bundle['event_end'])
    && $bundle['event_start'] <= $now && $bundle['event_end'] >= $now;

$pageTitle = htmlspecialchars($bundle['name']) . ' - ' . __('bundles.edit_page') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
$pageHeaderTitle = __('bundles.edit_page');
$currentLocale = $config['locale'] ?? 'ko';
?>
<?php include __DIR__ . '/../reservations/_head.php'; ?>
            <!-- 브레드크럼 -->
            <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400 mb-4">
                <a href="<?= $adminUrl ?>/bundles" class="hover:text-blue-600"><?= __('bundles.title') ?></a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-zinc-900 dark:text-white font-medium"><?= htmlspecialchars($bundle['name']) ?></span>
            </div>

            <div id="msgArea"></div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- 좌측: 기본 정보 + 이미지 -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- 대표 이미지 -->
                    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border dark:border-zinc-700 overflow-hidden">
                        <div class="p-4 border-b dark:border-zinc-700">
                            <h3 class="font-semibold text-zinc-900 dark:text-white"><?= __('bundles.edit_image') ?></h3>
                        </div>
                        <div class="p-4">
                            <div id="imagePreview" class="relative w-full aspect-video bg-zinc-100 dark:bg-zinc-700 rounded-lg overflow-hidden mb-3 flex items-center justify-center cursor-pointer group" onclick="document.getElementById('imageInput').click()">
                                <?php if ($bundle['image']): ?>
                                <?php $_bdlImgUrl = $bundle['image']; if (!str_starts_with($_bdlImgUrl, 'http')) $_bdlImgUrl = $baseUrl . '/' . ltrim($_bdlImgUrl, '/'); ?>
                                <img src="<?= htmlspecialchars($_bdlImgUrl) ?>" class="w-full h-full object-cover" id="previewImg">
                                <?php else: ?>
                                <div id="noImagePlaceholder" class="text-center">
                                    <svg class="w-12 h-12 mx-auto text-zinc-300 dark:text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <p class="text-xs text-zinc-400 mt-2"><?= __('bundles.click_upload') ?></p>
                                </div>
                                <img src="" class="w-full h-full object-cover hidden" id="previewImg">
                                <?php endif; ?>
                                <div class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
                                    <span class="text-white text-sm font-medium"><?= __('bundles.change_image') ?></span>
                                </div>
                            </div>
                            <input type="file" id="imageInput" accept="image/*" class="hidden">
                            <?php if ($bundle['image']): ?>
                            <button onclick="removeImage()" class="w-full text-sm text-red-500 hover:text-red-700 py-1"><?= __('bundles.remove_image') ?></button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- 상태/활성 -->
                    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border dark:border-zinc-700 p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('bundles.status') ?></span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="fmActive" class="sr-only peer" <?= $bundle['is_active'] ? 'checked' : '' ?>>
                                <div class="w-11 h-6 bg-zinc-200 peer-focus:outline-none rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-zinc-500 dark:text-zinc-400"><?= __('bundles.display_order') ?></span>
                            <input type="number" id="fmOrder" value="<?= (int)$bundle['display_order'] ?>" min="0" class="w-20 text-right px-2 py-1 border rounded dark:bg-zinc-700 dark:border-zinc-600 dark:text-white text-sm">
                        </div>
                    </div>
                </div>

                <!-- 우측: 상세 설정 -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- 기본 정보 -->
                    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border dark:border-zinc-700">
                        <div class="p-4 border-b dark:border-zinc-700">
                            <h3 class="font-semibold text-zinc-900 dark:text-white"><?= __('bundles.basic_info') ?></h3>
                        </div>
                        <div class="p-4 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('bundles.name') ?> <span class="text-red-500">*</span></label>
                                <?php rzx_multilang_input('fmName', $bundle['name'], 'bundle.' . $bundle['id'] . '.name', ['required' => true]); ?>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('bundles.desc') ?></label>
                                <?php rzx_multilang_input('fmDesc', $bundle['description'] ?? '', 'bundle.' . $bundle['id'] . '.description', ['type' => 'textarea', 'rows' => 2]); ?>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('bundles.bundle_price') ?> (<?= $currency ?>)</label>
                                    <input type="number" id="fmPrice" value="<?= $bundle['bundle_price'] ?>" min="0" step="100" class="w-full px-3 py-2 border rounded-lg dark:bg-zinc-700 dark:border-zinc-600 dark:text-white focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('bundles.original_price') ?></label>
                                    <div id="origPriceDisplay" class="px-3 py-2 bg-zinc-50 dark:bg-zinc-700/50 border rounded-lg text-zinc-500 dark:text-zinc-400">
                                        <?= number_format(array_sum(array_column($bundleItems, 'price'))) ?> <?= $currency ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 이벤트 할인 -->
                    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border dark:border-zinc-700 <?= $isEventActive ? 'ring-2 ring-orange-400' : '' ?>">
                        <div class="p-4 border-b dark:border-zinc-700 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <h3 class="font-semibold text-zinc-900 dark:text-white"><?= __('bundles.event_discount') ?></h3>
                                <?php if ($isEventActive): ?>
                                <span class="px-2 py-0.5 bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400 text-xs font-medium rounded-full"><?= __('bundles.event_active') ?></span>
                                <?php endif; ?>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="eventToggle" class="sr-only peer" <?= !empty($bundle['event_price']) ? 'checked' : '' ?>>
                                <div class="w-11 h-6 bg-zinc-200 peer-focus:outline-none rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-500"></div>
                            </label>
                        </div>
                        <div id="eventFields" class="p-4 space-y-4 <?= empty($bundle['event_price']) ? 'hidden' : '' ?>">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('bundles.event_price') ?> (<?= $currency ?>)</label>
                                    <input type="number" id="fmEventPrice" value="<?= $bundle['event_price'] ?? '' ?>" min="0" step="100" class="w-full px-3 py-2 border rounded-lg dark:bg-zinc-700 dark:border-zinc-600 dark:text-white focus:ring-2 focus:ring-orange-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('bundles.event_label') ?></label>
                                    <input type="text" id="fmEventLabel" value="<?= htmlspecialchars($bundle['event_label'] ?? '') ?>" placeholder="<?= __('bundles.event_label_placeholder') ?>" class="w-full px-3 py-2 border rounded-lg dark:bg-zinc-700 dark:border-zinc-600 dark:text-white focus:ring-2 focus:ring-orange-500">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('bundles.event_start') ?></label>
                                    <input type="datetime-local" id="fmEventStart" value="<?= $bundle['event_start'] ? date('Y-m-d\TH:i', strtotime($bundle['event_start'])) : '' ?>" class="w-full px-3 py-2 border rounded-lg dark:bg-zinc-700 dark:border-zinc-600 dark:text-white focus:ring-2 focus:ring-orange-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('bundles.event_end') ?></label>
                                    <input type="datetime-local" id="fmEventEnd" value="<?= $bundle['event_end'] ? date('Y-m-d\TH:i', strtotime($bundle['event_end'])) : '' ?>" class="w-full px-3 py-2 border rounded-lg dark:bg-zinc-700 dark:border-zinc-600 dark:text-white focus:ring-2 focus:ring-orange-500">
                                </div>
                            </div>
                            <div id="eventDiscountInfo" class="text-sm text-orange-600 dark:text-orange-400 font-medium"></div>
                        </div>
                    </div>

                    <!-- 포함 서비스 -->
                    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border dark:border-zinc-700">
                        <div class="p-4 border-b dark:border-zinc-700 flex items-center justify-between">
                            <h3 class="font-semibold text-zinc-900 dark:text-white"><?= __('bundles.included_services') ?> (<span id="svcCount"><?= count($bundleItems) ?></span>)</h3>
                            <button onclick="openServicePicker()" class="px-3 py-1.5 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <?= __('bundles.add_service') ?>
                            </button>
                        </div>
                        <div id="serviceList" class="divide-y dark:divide-zinc-700">
                            <?php if (empty($bundleItems)): ?>
                            <div class="p-8 text-center text-zinc-400"><?= __('bundles.no_services') ?></div>
                            <?php else: ?>
                            <?php foreach ($bundleItems as $item): ?>
                            <div class="flex items-center gap-3 p-3 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 svc-item" data-id="<?= $item['service_id'] ?>">
                                <svg class="w-5 h-5 text-zinc-300 cursor-grab drag-handle" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/></svg>
                                <?php if ($item['image']): ?>
                                <img src="<?= htmlspecialchars($baseUrl . '/' . $item['image']) ?>" class="w-10 h-10 rounded object-cover flex-shrink-0">
                                <?php else: ?>
                                <div class="w-10 h-10 rounded bg-zinc-100 dark:bg-zinc-700 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                </div>
                                <?php endif; ?>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-zinc-900 dark:text-white truncate"><?= htmlspecialchars($item['name']) ?></p>
                                    <p class="text-xs text-zinc-500"><?= number_format((float)$item['price']) ?> <?= $currency ?> · <?= $item['duration'] ?><?= __('bundles.min') ?></p>
                                </div>
                                <button onclick="removeService('<?= $item['service_id'] ?>')" class="p-1 text-red-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- 스태프 연동 -->
                    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border dark:border-zinc-700">
                        <div class="p-4 border-b dark:border-zinc-700">
                            <h3 class="font-semibold text-zinc-900 dark:text-white"><?= __('bundles.linked_staff') ?></h3>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1"><?= __('bundles.linked_staff_desc') ?></p>
                        </div>
                        <div class="p-4 grid grid-cols-2 sm:grid-cols-3 gap-3" id="staffGrid">
                            <?php foreach ($allStaff as $s): $isLinked = in_array($s['id'], $linkedStaffIds); ?>
                            <label class="flex items-center gap-2 p-2 rounded-lg border cursor-pointer transition <?= $isLinked ? 'border-blue-400 bg-blue-50 dark:bg-blue-900/20 dark:border-blue-600' : 'border-zinc-200 dark:border-zinc-700 hover:border-zinc-300' ?>" data-staff="<?= $s['id'] ?>">
                                <input type="checkbox" class="staff-check rounded" value="<?= $s['id'] ?>" <?= $isLinked ? 'checked' : '' ?>>
                                <?php if ($s['avatar']): ?>
                                <?php $_sAvatar = $s['avatar']; if (!str_starts_with($_sAvatar, 'http')) $_sAvatar = $baseUrl . $_sAvatar; ?>
                                <img src="<?= htmlspecialchars($_sAvatar) ?>" class="w-8 h-8 rounded-full object-cover">
                                <?php else: ?>
                                <div class="w-8 h-8 rounded-full bg-zinc-200 dark:bg-zinc-600 flex items-center justify-center text-xs font-bold text-zinc-500"><?= mb_substr($s['name'], 0, 1) ?></div>
                                <?php endif; ?>
                                <span class="text-sm text-zinc-700 dark:text-zinc-300 truncate"><?= htmlspecialchars($s['name']) ?></span>
                                <?php if (!$s['is_visible']): ?>
                                <span class="text-xs text-zinc-400">(<?= __('bundles.hidden') ?>)</span>
                                <?php endif; ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 하단 저장 바 -->
            <div class="sticky bottom-0 mt-6 -mx-6 px-6 py-4 bg-white/80 dark:bg-zinc-800/80 backdrop-blur border-t dark:border-zinc-700 flex items-center justify-between">
                <a href="<?= $adminUrl ?>/bundles" class="px-4 py-2 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg"><?= __('bundles.back_to_list') ?></a>
                <div class="flex items-center gap-3">
                    <button onclick="deleteBundle()" class="px-4 py-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg"><?= __('bundles.delete') ?></button>
                    <button onclick="saveAll()" id="btnSaveAll" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <?= __('bundles.save') ?>
                    </button>
                </div>
            </div>
            </div>
        </main>
    </div>

<!-- 서비스 추가 모달 -->
<div id="svcPickerModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50" onclick="closeServicePicker()"></div>
    <div class="absolute inset-y-0 right-0 w-full max-w-md bg-white dark:bg-zinc-800 shadow-xl flex flex-col">
        <div class="flex items-center justify-between p-4 border-b dark:border-zinc-700">
            <h3 class="font-semibold dark:text-white"><?= __('bundles.add_service') ?></h3>
            <button onclick="closeServicePicker()" class="p-1 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded">
                <svg class="w-5 h-5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="p-3">
            <input type="text" id="svcSearch" placeholder="<?= __('bundles.search_service') ?>" class="w-full px-3 py-2 border rounded-lg dark:bg-zinc-700 dark:border-zinc-600 dark:text-white text-sm">
        </div>
        <div class="flex-1 overflow-y-auto p-3 space-y-1" id="svcPickerList">
            <?php foreach ($allServices as $svc): ?>
            <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 cursor-pointer svc-picker-item" data-name="<?= htmlspecialchars(strtolower($svc['name'])) ?>" data-id="<?= $svc['id'] ?>">
                <input type="checkbox" class="svc-picker-check rounded" value="<?= $svc['id'] ?>" data-name="<?= htmlspecialchars($svc['name']) ?>" data-price="<?= $svc['price'] ?>" data-duration="<?= $svc['duration'] ?>" data-image="<?= htmlspecialchars($svc['image'] ?? '') ?>">
                <div class="flex-1">
                    <p class="text-sm font-medium dark:text-zinc-200"><?= htmlspecialchars($svc['name']) ?></p>
                    <p class="text-xs text-zinc-400"><?= number_format((float)$svc['price']) ?> <?= $currency ?> · <?= $svc['duration'] ?><?= __('bundles.min') ?></p>
                </div>
            </label>
            <?php endforeach; ?>
        </div>
        <div class="p-4 border-t dark:border-zinc-700">
            <button onclick="addSelectedServices()" class="w-full py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium"><?= __('bundles.add_selected') ?></button>
        </div>
    </div>
</div>

<?php include BASE_PATH . '/resources/views/admin/components/multilang-modal.php'; ?>
<?php include BASE_PATH . '/resources/views/admin/partials/result-modal.php'; ?>
<script src="<?= $baseUrl ?>/assets/js/result-modal.js"></script>
<?php include __DIR__ . '/edit-js.php'; ?>
    </div>
    </main>
</div>
</body>
</html>
