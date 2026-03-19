<?php
/**
 * RezlyX Admin - 스태프 관리 페이지 (목록 + 추가/수정 모달)
 */

if (!function_exists('__')) {
    require_once BASE_PATH . '/rzxlib/Core/Helpers/lang.php';
}

$pageTitle = __('staff.title') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');
$uploadDir = '/storage/uploads/staff/';
$uploadPath = BASE_PATH . $uploadDir;

// DB 연결
$staffList = [];
$positions = [];
$services = [];
$settings = [];

try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'],
        $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

    // 설정 로드
    $stmt = $pdo->query("SELECT `key`, `value` FROM {$prefix}settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['key']] = $row['value'];
    }

    // POST 처리
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json; charset=utf-8');
        $action = $_POST['action'] ?? '';

        // 사진 업로드 헬퍼
        function uploadStaffAvatar($file, $uploadPath, $baseUrl, $uploadDir) {
            if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] === 0) return null;
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) return null;
            if ($file['size'] > 5 * 1024 * 1024) return null; // 5MB 제한
            if (!is_dir($uploadPath)) mkdir($uploadPath, 0755, true);
            $filename = 'staff_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $dest = $uploadPath . $filename;
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                return $baseUrl . $uploadDir . $filename;
            }
            return null;
        }

        // 스태프 추가
        if ($action === 'create') {
            $name = trim($_POST['name'] ?? '');
            if ($name === '') {
                echo json_encode(['success' => false, 'message' => __('staff.error.name_required')]);
                exit;
            }

            $avatar = null;
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $avatar = uploadStaffAvatar($_FILES['avatar'], $uploadPath, $baseUrl, $uploadDir);
            }
            // 파일 업로드가 없으면 회원 프로필 사진 URL 사용
            if (!$avatar && !empty($_POST['member_avatar_url'])) {
                $avatar = trim($_POST['member_avatar_url']);
            }

            $positionId = (int)($_POST['position_id'] ?? 0) ?: null;
            $nameI18n = $_POST['name_i18n'] ?? null;
            $bioI18n = $_POST['bio_i18n'] ?? null;

            $stmt = $pdo->prepare("SELECT MAX(sort_order) FROM {$prefix}staff");
            $stmt->execute();
            $maxSort = (int)$stmt->fetchColumn();

            $userId = trim($_POST['user_id'] ?? '') ?: null;

            $designationFee = max(0, (float)($_POST['designation_fee'] ?? 0));

            // 배너 업로드
            $banner = null;
            if (isset($_FILES['banner']) && $_FILES['banner']['error'] === UPLOAD_ERR_OK) {
                $banner = uploadStaffAvatar($_FILES['banner'], $uploadPath, $baseUrl, $uploadDir);
            }

            $sql = "INSERT INTO {$prefix}staff (user_id, name, name_i18n, email, phone, avatar, banner, bio, bio_i18n, greeting_before, greeting_after, designation_fee, position_id, is_active, sort_order)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $userId,
                $name,
                $nameI18n ? json_encode(json_decode($nameI18n, true), JSON_UNESCAPED_UNICODE) : null,
                trim($_POST['email'] ?? '') ?: null,
                trim($_POST['phone'] ?? '') ?: null,
                $avatar,
                $banner,
                trim($_POST['bio'] ?? '') ?: null,
                $bioI18n ? json_encode(json_decode($bioI18n, true), JSON_UNESCAPED_UNICODE) : null,
                trim($_POST['greeting_before'] ?? '') ?: null,
                trim($_POST['greeting_after'] ?? '') ?: null,
                $designationFee,
                $positionId,
                $maxSort + 1,
            ]);

            $newStaffId = $pdo->lastInsertId();

            // 담당 서비스 저장
            $serviceIds = isset($_POST['service_ids']) ? json_decode($_POST['service_ids'], true) : [];
            if (!empty($serviceIds) && is_array($serviceIds)) {
                $ssStmt = $pdo->prepare("INSERT INTO {$prefix}staff_services (staff_id, service_id) VALUES (?, ?)");
                foreach ($serviceIds as $sid) {
                    $ssStmt->execute([$newStaffId, $sid]);
                }
            }

            // 담당 번들 저장
            $bundleIds = isset($_POST['bundle_ids']) ? json_decode($_POST['bundle_ids'], true) : [];
            if (!empty($bundleIds) && is_array($bundleIds)) {
                $sbStmt = $pdo->prepare("INSERT INTO {$prefix}staff_bundles (staff_id, bundle_id) VALUES (?, ?)");
                foreach ($bundleIds as $bid) {
                    $sbStmt->execute([$newStaffId, $bid]);
                }
            }

            echo json_encode(['success' => true, 'message' => __('staff.success.created')]);
            exit;
        }

        // 스태프 수정
        if ($action === 'update') {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            if (!$id || $name === '') {
                echo json_encode(['success' => false, 'message' => __('staff.error.name_required')]);
                exit;
            }

            $userId = trim($_POST['user_id'] ?? '') ?: null;
            $designationFee = max(0, (float)($_POST['designation_fee'] ?? 0));
            $fields = ['user_id = ?', 'name = ?', 'email = ?', 'phone = ?', 'bio = ?', 'designation_fee = ?', 'position_id = ?', 'is_active = ?', 'is_visible = ?'];
            $positionId = (int)($_POST['position_id'] ?? 0) ?: null;
            $params = [
                $userId,
                $name,
                trim($_POST['email'] ?? '') ?: null,
                trim($_POST['phone'] ?? '') ?: null,
                trim($_POST['bio'] ?? '') ?: null,
                $designationFee,
                $positionId,
                isset($_POST['is_active']) ? 1 : 0,
                isset($_POST['is_visible']) ? 1 : 0,
            ];

            // 다국어
            $nameI18n = $_POST['name_i18n'] ?? null;
            $bioI18n = $_POST['bio_i18n'] ?? null;
            $fields[] = 'name_i18n = ?';
            $params[] = $nameI18n ? json_encode(json_decode($nameI18n, true), JSON_UNESCAPED_UNICODE) : null;
            $fields[] = 'bio_i18n = ?';
            $params[] = $bioI18n ? json_encode(json_decode($bioI18n, true), JSON_UNESCAPED_UNICODE) : null;

            // 인사말
            $fields[] = 'greeting_before = ?';
            $params[] = trim($_POST['greeting_before'] ?? '') ?: null;
            $fields[] = 'greeting_after = ?';
            $params[] = trim($_POST['greeting_after'] ?? '') ?: null;
            $greetBeforeI18n = $_POST['greeting_before_i18n'] ?? null;
            $greetAfterI18n = $_POST['greeting_after_i18n'] ?? null;

            // 사진 업로드
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $avatar = uploadStaffAvatar($_FILES['avatar'], $uploadPath, $baseUrl, $uploadDir);
                if ($avatar) {
                    $fields[] = 'avatar = ?';
                    $params[] = $avatar;
                }
            }
            // 파일 업로드가 없으면 회원 프로필 사진 URL 사용
            if (!isset($avatar) || !$avatar) {
                $memberAvatarUrl = trim($_POST['member_avatar_url'] ?? '');
                if ($memberAvatarUrl) {
                    $fields[] = 'avatar = ?';
                    $params[] = $memberAvatarUrl;
                }
            }
            // 사진 삭제
            if (isset($_POST['remove_avatar']) && $_POST['remove_avatar'] === '1') {
                $fields[] = 'avatar = NULL';
            }

            // 배너 업로드
            if (isset($_FILES['banner']) && $_FILES['banner']['error'] === UPLOAD_ERR_OK) {
                $bannerUrl = uploadStaffAvatar($_FILES['banner'], $uploadPath, $baseUrl, $uploadDir);
                if ($bannerUrl) {
                    $fields[] = 'banner = ?';
                    $params[] = $bannerUrl;
                }
            }
            // 배너 삭제
            if (isset($_POST['remove_banner']) && $_POST['remove_banner'] === '1') {
                $fields[] = 'banner = NULL';
            }

            $params[] = $id;
            $sql = "UPDATE {$prefix}staff SET " . implode(', ', $fields) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            // 담당 서비스 갱신
            $pdo->prepare("DELETE FROM {$prefix}staff_services WHERE staff_id = ?")->execute([$id]);
            $serviceIds = isset($_POST['service_ids']) ? json_decode($_POST['service_ids'], true) : [];
            if (!empty($serviceIds) && is_array($serviceIds)) {
                $ssStmt = $pdo->prepare("INSERT INTO {$prefix}staff_services (staff_id, service_id) VALUES (?, ?)");
                foreach ($serviceIds as $sid) {
                    $ssStmt->execute([$id, $sid]);
                }
            }

            // 담당 번들 갱신
            $pdo->prepare("DELETE FROM {$prefix}staff_bundles WHERE staff_id = ?")->execute([$id]);
            $bundleIds = isset($_POST['bundle_ids']) ? json_decode($_POST['bundle_ids'], true) : [];
            if (!empty($bundleIds) && is_array($bundleIds)) {
                $sbStmt = $pdo->prepare("INSERT INTO {$prefix}staff_bundles (staff_id, bundle_id) VALUES (?, ?)");
                foreach ($bundleIds as $bid) {
                    $sbStmt->execute([$id, $bid]);
                }
            }

            echo json_encode(['success' => true, 'message' => __('staff.success.updated')]);
            exit;
        }

        // 회원 검색 (이름이 암호화되어 있으므로 전체 복호화 후 반환)
        if ($action === 'search_members') {
            require_once BASE_PATH . '/rzxlib/Core/Helpers/Encryption.php';

            $stmt = $pdo->prepare("SELECT id, name, email, phone, profile_image FROM {$prefix}users
                WHERE status = 'active' ORDER BY created_at DESC LIMIT 500");
            $stmt->execute();
            $allUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $results = [];
            foreach ($allUsers as $u) {
                $profileImg = $u['profile_image'] ?? '';
                if ($profileImg && !str_starts_with($profileImg, 'http')) {
                    $profileImg = $baseUrl . $profileImg;
                }
                $results[] = [
                    'id' => $u['id'],
                    'name' => \RzxLib\Core\Helpers\Encryption::decrypt($u['name']),
                    'email' => $u['email'] ?? '',
                    'phone' => \RzxLib\Core\Helpers\Encryption::decrypt($u['phone'] ?? ''),
                    'avatar' => $profileImg,
                ];
            }

            echo json_encode(['success' => true, 'members' => $results], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 스태프 삭제
        if ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id) {
                $pdo->prepare("DELETE FROM {$prefix}staff_services WHERE staff_id = ?")->execute([$id]);
                $pdo->prepare("DELETE FROM {$prefix}staff WHERE id = ?")->execute([$id]);
                echo json_encode(['success' => true, 'message' => __('staff.success.deleted')]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid ID']);
            }
            exit;
        }

        echo json_encode(['success' => false, 'message' => 'Unknown action']);
        exit;
    }

    // 스태프 목록 로드
    $staffList = $pdo->query("SELECT s.*, p.name as position_name FROM {$prefix}staff s LEFT JOIN {$prefix}staff_positions p ON s.position_id = p.id ORDER BY s.sort_order ASC, s.name ASC")->fetchAll(PDO::FETCH_ASSOC);

    // 활성 직책 목록
    $positions = $pdo->query("SELECT id, name FROM {$prefix}staff_positions WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);

    // 서비스 목록
    $services = $pdo->query("SELECT id, name FROM {$prefix}services WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);

    // 서비스 다국어 번역 로드
    $currentLocale = $config['locale'] ?? 'ko';
    $defaultLocale = $settings['default_language'] ?? 'ko';
    $localeChain = array_unique(array_filter([$currentLocale, 'en', $defaultLocale]));
    $lcPlaceholders = implode(',', array_fill(0, count($localeChain), '?'));
    $trStmt = $pdo->prepare("SELECT lang_key, locale, content FROM {$prefix}translations
        WHERE locale IN ({$lcPlaceholders}) AND lang_key LIKE 'service.%.name'");
    $trStmt->execute(array_values($localeChain));
    $svcTranslations = [];
    while ($tr = $trStmt->fetch(PDO::FETCH_ASSOC)) {
        $svcTranslations[$tr['lang_key']][$tr['locale']] = $tr['content'];
    }

    // 서비스 이름 다국어 헬퍼
    function getServiceTranslated($svcId, $default) {
        global $svcTranslations, $localeChain;
        $key = "service.{$svcId}.name";
        if (isset($svcTranslations[$key])) {
            foreach ($localeChain as $loc) {
                if (!empty($svcTranslations[$key][$loc])) return $svcTranslations[$key][$loc];
            }
        }
        return $default;
    }

    // 스태프별 담당 서비스 매핑
    $staffServices = [];
    $ssRows = $pdo->query("SELECT staff_id, service_id FROM {$prefix}staff_services")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($ssRows as $row) {
        $staffServices[$row['staff_id']][] = $row['service_id'];
    }

    // 번들 목록
    $allBundles = [];
    $staffBundlesMap = [];
    try {
        $allBundles = $pdo->query("SELECT id, name, bundle_price, is_active FROM {$prefix}service_bundles WHERE is_active = 1 ORDER BY display_order, name")->fetchAll(PDO::FETCH_ASSOC);
        $sbRows = $pdo->query("SELECT staff_id, bundle_id FROM {$prefix}staff_bundles")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($sbRows as $row) {
            $staffBundlesMap[$row['staff_id']][] = $row['bundle_id'];
        }
    } catch (PDOException $e) {
        // service_bundles/staff_bundles 테이블 미존재 시 무시
    }

    $dbConnected = true;
} catch (PDOException $e) {
    $dbConnected = false;
    $dbError = $e->getMessage();
}

// 지원 언어 목록
$supportedLangs = json_decode($settings['supported_languages'] ?? '["ko","en","ja"]', true) ?: ['ko', 'en', 'ja'];
$langNativeNames = ['ko'=>'한국어','en'=>'English','ja'=>'日本語','zh_CN'=>'简体中文','zh_TW'=>'繁體中文','de'=>'Deutsch','es'=>'Español','fr'=>'Français','vi'=>'Tiếng Việt','id'=>'Bahasa Indonesia','mn'=>'Монгол','ru'=>'Русский','tr'=>'Türkçe'];
?>
<!DOCTYPE html>
<html lang="<?= $config['locale'] ?? 'ko' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css">
    <!-- Cropper.js for avatar editing -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
    <style>body { font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, sans-serif; }</style>
    <script>
        if (localStorage.getItem('darkMode') === 'true' ||
            (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="bg-zinc-100 dark:bg-zinc-900 min-h-screen transition-colors">
    <div class="flex">
        <?php include dirname(__DIR__) . '/partials/admin-sidebar.php'; ?>
        <main class="flex-1 ml-64">
            <?php include dirname(__DIR__) . '/partials/admin-topbar.php'; ?>

            <div class="p-8">
                <!-- 알림 -->
                <div id="alertBox" class="mb-6 p-4 rounded-lg border hidden"></div>

                <!-- 헤더 -->
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white"><?= __('staff.title') ?></h1>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1"><?= __('staff.description') ?></p>
                    </div>
                    <button type="button" onclick="openStaffModal()"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <?= __('staff.create') ?>
                    </button>
                </div>

                <!-- 스태프 카드 그리드 -->
                <?php if (!empty($staffList)): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="staffGrid">
                    <?php foreach ($staffList as $s):
                        $nameI18n = $s['name_i18n'] ? json_decode($s['name_i18n'], true) : [];
                        $bioI18n = $s['bio_i18n'] ? json_decode($s['bio_i18n'], true) : [];
                    ?>
                    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm overflow-hidden group flex flex-col <?= !$s['is_active'] ? 'opacity-60' : '' ?>" id="staff-card-<?= $s['id'] ?>">
                        <!-- 배너 헤더 -->
                        <div class="relative h-20 bg-gradient-to-r from-blue-500 to-purple-500 overflow-hidden">
                            <?php if (!empty($s['banner'])): ?>
                            <img src="<?= htmlspecialchars($s['banner']) ?>" class="w-full h-full object-cover" alt="">
                            <?php endif; ?>
                            <!-- 상태 배지 -->
                            <div class="absolute top-2 right-2 flex gap-1">
                                <?php if (!$s['is_active']): ?>
                                <span class="px-1.5 py-0.5 text-[10px] font-medium rounded-full bg-red-600 text-white shadow"><?= __('staff.fields.badge_inactive') ?></span>
                                <?php endif; ?>
                                <?php if (!($s['is_visible'] ?? 1)): ?>
                                <span class="px-1.5 py-0.5 text-[10px] font-medium rounded-full bg-zinc-800/70 text-white shadow"><?= __('staff.fields.badge_hidden') ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="relative px-5 pb-4 flex-1">
                            <!-- 아바타 (배너 아래 겹침) -->
                            <div class="absolute -top-7 left-5 w-14 h-14 rounded-full bg-white dark:bg-zinc-800 border-2 border-white dark:border-zinc-800 shadow overflow-hidden">
                                <?php if ($s['avatar']): ?>
                                <img src="<?= htmlspecialchars($s['avatar']) ?>" class="w-full h-full object-cover" alt="">
                                <?php else: ?>
                                <div class="w-full h-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center text-lg font-bold text-zinc-500"><?= mb_substr($s['name'], 0, 1) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="pt-9">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-base font-semibold text-zinc-900 dark:text-white truncate"><?= htmlspecialchars($s['name']) ?></h3>
                                </div>
                                    <?php if ($s['position_name']): ?>
                                    <p class="text-xs text-blue-600 dark:text-blue-400 mt-0.5"><?= htmlspecialchars($s['position_name']) ?></p>
                                    <?php endif; ?>
                                    <?php if ($s['email'] || $s['phone']): ?>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 truncate"><?= htmlspecialchars($s['email'] ?? '') ?><?= $s['email'] && $s['phone'] ? ' · ' : '' ?><?= htmlspecialchars($s['phone'] ?? '') ?></p>
                                    <?php endif; ?>
                                    <?php if ($s['bio']): ?>
                                    <p class="text-xs text-zinc-400 mt-1 line-clamp-2"><?= htmlspecialchars(mb_substr($s['bio'], 0, 60)) ?><?= mb_strlen($s['bio']) > 60 ? '...' : '' ?></p>
                                    <?php endif; ?>
                                    <?php
                                        $myServices = $staffServices[$s['id']] ?? [];
                                        if (!empty($myServices)):
                                            $svcNames = [];
                                            foreach ($services as $svc) {
                                                if (in_array($svc['id'], $myServices)) $svcNames[] = getServiceTranslated($svc['id'], $svc['name']);
                                            }
                                    ?>
                                    <div class="flex flex-wrap gap-1 mt-2">
                                        <?php foreach ($svcNames as $sn): ?>
                                        <span class="px-1.5 py-0.5 text-[10px] font-medium rounded bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400"><?= htmlspecialchars($sn) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (($settings['staff_designation_fee_enabled'] ?? '0') === '1' && (float)$s['designation_fee'] > 0): ?>
                                    <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 mt-1 text-[10px] font-medium rounded bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <?= __('staff.fields.designation_fee') ?>: <?= number_format((float)$s['designation_fee']) ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <!-- 액션 바 -->
                        <div class="px-5 py-3 bg-zinc-50 dark:bg-zinc-700/30 border-t border-zinc-100 dark:border-zinc-700 flex justify-end gap-1 mt-auto">
                            <button type="button" onclick='openStaffModal(<?= htmlspecialchars(json_encode([
                                "id" => $s["id"], "user_id" => $s["user_id"], "name" => $s["name"], "email" => $s["email"],
                                "phone" => $s["phone"], "bio" => $s["bio"], "avatar" => $s["avatar"],
                                "banner" => $s["banner"] ?? null,
                                "greeting_before" => $s["greeting_before"] ?? "",
                                "greeting_after" => $s["greeting_after"] ?? "",
                                "card_number" => $s["card_number"] ?? "",
                                "position_id" => $s["position_id"], "is_active" => $s["is_active"], "is_visible" => $s["is_visible"] ?? 1,
                                "name_i18n" => $nameI18n, "bio_i18n" => $bioI18n,
                                "designation_fee" => (float)($s["designation_fee"] ?? 0),
                                "service_ids" => $staffServices[$s["id"]] ?? [],
                                "bundle_ids" => $staffBundlesMap[$s["id"]] ?? [],
                            ], JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>)'
                                    class="px-3 py-1.5 text-xs font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-600 rounded-lg transition">
                                <?= __('staff.edit') ?>
                            </button>
                            <button type="button" onclick="deleteStaff(<?= $s['id'] ?>, '<?= htmlspecialchars($s['name'], ENT_QUOTES) ?>')"
                                    class="px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition">
                                <?= __('staff.delete') ?>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-12 text-center">
                    <p class="text-zinc-400"><?= __('staff.empty') ?></p>
                </div>
                <?php endif; ?>
            </div>

            <?php include __DIR__ . '/index-js.php'; ?>
        </main>
    </div>
</body>
</html>
