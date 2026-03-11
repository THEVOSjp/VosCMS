<?php
/**
 * RezlyX Admin Settings Page
 */
$pageTitle = '설정 - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';

// Database connection
try {
    $pdo = new PDO(
        'mysql:host=' . ($_ENV['DB_HOST'] ?? 'localhost') . ';dbname=' . ($_ENV['DB_DATABASE'] ?? 'rezlyx'),
        $_ENV['DB_USERNAME'] ?? 'root',
        $_ENV['DB_PASSWORD'] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die('데이터베이스 연결 실패: ' . $e->getMessage());
}

// Handle form submission
$message = '';
$messageType = '';

// Check if redirected after path change
if (isset($_GET['changed']) && $_GET['changed'] === '1') {
    $message = '관리자 경로가 변경되었습니다. 현재 새 경로로 접속 중입니다.';
    $messageType = 'success';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_admin_path') {
        $newAdminPath = trim($_POST['admin_path'] ?? '');

        if (empty($newAdminPath)) {
            $message = '관리자 경로를 입력해주세요.';
            $messageType = 'error';
        } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $newAdminPath)) {
            $message = '관리자 경로는 영문, 숫자, 하이픈, 언더스코어만 사용 가능합니다.';
            $messageType = 'error';
        } elseif (in_array($newAdminPath, ['api', 'assets', 'storage', 'install', 'public'])) {
            $message = '예약된 경로는 사용할 수 없습니다.';
            $messageType = 'error';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE rzx_settings SET value = ? WHERE rzx_settings.key = ?");
                $stmt->execute([$newAdminPath, 'admin_path']);

                // Redirect to new admin path
                $newAdminUrl = ($config['app_url'] ?? '') . '/' . $newAdminPath . '/settings?changed=1';
                header('Location: ' . $newAdminUrl);
                exit;
            } catch (PDOException $e) {
                $message = '저장 실패: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    } elseif ($action === 'update_site_settings') {
        $siteCategory = $_POST['site_category'] ?? '';
        $siteName = trim($_POST['site_name'] ?? '');
        $siteTagline = trim($_POST['site_tagline'] ?? '');
        $siteUrl = trim($_POST['site_url'] ?? '');
        $logoType = $_POST['logo_type'] ?? 'text';

        // 로고 이미지 업로드 처리
        $logoImage = null;
        if (isset($_FILES['logo_image']) && $_FILES['logo_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = BASE_PATH . '/storage/logos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp'];
            $fileType = $_FILES['logo_image']['type'];

            if (in_array($fileType, $allowedTypes)) {
                $extension = pathinfo($_FILES['logo_image']['name'], PATHINFO_EXTENSION);
                $fileName = 'logo_' . time() . '.' . $extension;
                $targetPath = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['logo_image']['tmp_name'], $targetPath)) {
                    $logoImage = '/storage/logos/' . $fileName;

                    // 이전 로고 파일 삭제
                    $oldLogo = $settings['logo_image'] ?? '';
                    if ($oldLogo && file_exists(BASE_PATH . $oldLogo)) {
                        @unlink(BASE_PATH . $oldLogo);
                    }
                }
            } else {
                $message = '허용되지 않는 이미지 형식입니다. (JPG, PNG, GIF, SVG, WebP만 가능)';
                $messageType = 'error';
            }
        }

        if ($messageType !== 'error') {
            try {
                $stmt = $pdo->prepare("INSERT INTO rzx_settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
                $stmt->execute(['site_category', $siteCategory]);
                $stmt->execute(['site_name', $siteName]);
                $stmt->execute(['site_tagline', $siteTagline]);
                $stmt->execute(['site_url', $siteUrl]);
                $stmt->execute(['logo_type', $logoType]);

                if ($logoImage) {
                    $stmt->execute(['logo_image', $logoImage]);
                }

                $message = '사이트 설정이 저장되었습니다.';
                $messageType = 'success';

                // 설정 다시 로드
                $settings['site_category'] = $siteCategory;
                $settings['site_name'] = $siteName;
                $settings['site_tagline'] = $siteTagline;
                $settings['site_url'] = $siteUrl;
                $settings['logo_type'] = $logoType;
                if ($logoImage) {
                    $settings['logo_image'] = $logoImage;
                }
            } catch (PDOException $e) {
                $message = '저장 실패: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    } elseif ($action === 'delete_logo') {
        // 로고 이미지 삭제
        try {
            $oldLogo = $settings['logo_image'] ?? '';
            if ($oldLogo && file_exists(BASE_PATH . $oldLogo)) {
                @unlink(BASE_PATH . $oldLogo);
            }

            $stmt = $pdo->prepare("DELETE FROM rzx_settings WHERE `key` = ?");
            $stmt->execute(['logo_image']);

            unset($settings['logo_image']);
            $message = '로고 이미지가 삭제되었습니다.';
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = '삭제 실패: ' . $e->getMessage();
            $messageType = 'error';
        }
    } elseif ($action === 'update_mail_settings') {
        // 메일 설정 저장
        $mailFromName = trim($_POST['mail_from_name'] ?? '');
        $mailFromAddress = trim($_POST['mail_from_address'] ?? '');
        $mailReplyTo = trim($_POST['mail_reply_to'] ?? '');
        $mailMethod = $_POST['mail_method'] ?? 'mail';
        $mailApplyAll = isset($_POST['mail_apply_all']) ? '1' : '0';

        // SMTP 설정
        $smtpHost = trim($_POST['smtp_host'] ?? '');
        $smtpPort = trim($_POST['smtp_port'] ?? '587');
        $smtpEncryption = $_POST['smtp_encryption'] ?? 'tls';
        $smtpUsername = trim($_POST['smtp_username'] ?? '');
        $smtpPassword = $_POST['smtp_password'] ?? '';

        try {
            $stmt = $pdo->prepare("INSERT INTO rzx_settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
            $stmt->execute(['mail_from_name', $mailFromName]);
            $stmt->execute(['mail_from_address', $mailFromAddress]);
            $stmt->execute(['mail_reply_to', $mailReplyTo]);
            $stmt->execute(['mail_method', $mailMethod]);
            $stmt->execute(['mail_apply_all', $mailApplyAll]);

            // SMTP 설정 저장
            $stmt->execute(['smtp_host', $smtpHost]);
            $stmt->execute(['smtp_port', $smtpPort]);
            $stmt->execute(['smtp_encryption', $smtpEncryption]);
            $stmt->execute(['smtp_username', $smtpUsername]);

            // 비밀번호는 입력된 경우에만 업데이트
            if (!empty($smtpPassword)) {
                $stmt->execute(['smtp_password', $smtpPassword]);
            }

            $message = '메일 설정이 저장되었습니다.';
            $messageType = 'success';

            // 설정 다시 로드
            $settings['mail_from_name'] = $mailFromName;
            $settings['mail_from_address'] = $mailFromAddress;
            $settings['mail_reply_to'] = $mailReplyTo;
            $settings['mail_method'] = $mailMethod;
            $settings['mail_apply_all'] = $mailApplyAll;
            $settings['smtp_host'] = $smtpHost;
            $settings['smtp_port'] = $smtpPort;
            $settings['smtp_encryption'] = $smtpEncryption;
            $settings['smtp_username'] = $smtpUsername;
        } catch (PDOException $e) {
            $message = '저장 실패: ' . $e->getMessage();
            $messageType = 'error';
        }
    } elseif ($action === 'update_seo_settings') {
        // SEO 설정 저장
        $seoDescription = trim($_POST['seo_description'] ?? '');
        $seoKeywords = trim($_POST['seo_keywords'] ?? '');
        $seoRobots = $_POST['seo_robots'] ?? 'index';
        $googleVerification = trim($_POST['google_verification'] ?? '');
        $naverVerification = trim($_POST['naver_verification'] ?? '');
        $gaTrackingId = trim($_POST['ga_tracking_id'] ?? '');
        $gtmId = trim($_POST['gtm_id'] ?? '');

        // OG 이미지 업로드 처리
        $ogImage = null;
        if (isset($_FILES['og_image']) && $_FILES['og_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = BASE_PATH . '/storage/seo/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            $fileType = $_FILES['og_image']['type'];

            if (in_array($fileType, $allowedTypes)) {
                $extension = pathinfo($_FILES['og_image']['name'], PATHINFO_EXTENSION);
                $fileName = 'og_image_' . time() . '.' . $extension;
                $targetPath = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['og_image']['tmp_name'], $targetPath)) {
                    $ogImage = '/storage/seo/' . $fileName;

                    // 이전 OG 이미지 삭제
                    $oldOgImage = $settings['og_image'] ?? '';
                    if ($oldOgImage && file_exists(BASE_PATH . $oldOgImage)) {
                        @unlink(BASE_PATH . $oldOgImage);
                    }
                }
            } else {
                $message = '허용되지 않는 이미지 형식입니다. (JPG, PNG, WebP만 가능)';
                $messageType = 'error';
            }
        }

        if ($messageType !== 'error') {
            try {
                $stmt = $pdo->prepare("INSERT INTO rzx_settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
                $stmt->execute(['seo_description', $seoDescription]);
                $stmt->execute(['seo_keywords', $seoKeywords]);
                $stmt->execute(['seo_robots', $seoRobots]);
                $stmt->execute(['google_verification', $googleVerification]);
                $stmt->execute(['naver_verification', $naverVerification]);
                $stmt->execute(['ga_tracking_id', $gaTrackingId]);
                $stmt->execute(['gtm_id', $gtmId]);

                if ($ogImage) {
                    $stmt->execute(['og_image', $ogImage]);
                }

                $message = __('admin.settings.seo.success');
                $messageType = 'success';

                // 설정 다시 로드
                $settings['seo_description'] = $seoDescription;
                $settings['seo_keywords'] = $seoKeywords;
                $settings['seo_robots'] = $seoRobots;
                $settings['google_verification'] = $googleVerification;
                $settings['naver_verification'] = $naverVerification;
                $settings['ga_tracking_id'] = $gaTrackingId;
                $settings['gtm_id'] = $gtmId;
                if ($ogImage) {
                    $settings['og_image'] = $ogImage;
                }
            } catch (PDOException $e) {
                $message = '저장 실패: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    } elseif ($action === 'delete_og_image') {
        // OG 이미지 삭제
        try {
            $oldOgImage = $settings['og_image'] ?? '';
            if ($oldOgImage && file_exists(BASE_PATH . $oldOgImage)) {
                @unlink(BASE_PATH . $oldOgImage);
            }

            $stmt = $pdo->prepare("DELETE FROM rzx_settings WHERE `key` = ?");
            $stmt->execute(['og_image']);

            unset($settings['og_image']);
            $message = '대표 이미지가 삭제되었습니다.';
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = '삭제 실패: ' . $e->getMessage();
            $messageType = 'error';
        }
    } elseif ($action === 'update_pwa_settings') {
        // PWA 설정 저장
        $pwaFrontEnabled = isset($_POST['pwa_front_enabled']) ? '1' : '0';
        $pwaFrontName = trim($_POST['pwa_front_name'] ?? '');
        $pwaFrontShortName = trim($_POST['pwa_front_short_name'] ?? '');
        $pwaFrontDescription = trim($_POST['pwa_front_description'] ?? '');
        $pwaFrontThemeColor = trim($_POST['pwa_front_theme_color'] ?? '#3b82f6');
        $pwaFrontBgColor = trim($_POST['pwa_front_bg_color'] ?? '#ffffff');

        $pwaAdminEnabled = isset($_POST['pwa_admin_enabled']) ? '1' : '0';
        $pwaAdminName = trim($_POST['pwa_admin_name'] ?? '');
        $pwaAdminShortName = trim($_POST['pwa_admin_short_name'] ?? '');
        $pwaAdminThemeColor = trim($_POST['pwa_admin_theme_color'] ?? '#18181b');
        $pwaAdminBgColor = trim($_POST['pwa_admin_bg_color'] ?? '#18181b');

        try {
            $stmt = $pdo->prepare("INSERT INTO rzx_settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
            $stmt->execute(['pwa_front_enabled', $pwaFrontEnabled]);
            $stmt->execute(['pwa_front_name', $pwaFrontName]);
            $stmt->execute(['pwa_front_short_name', $pwaFrontShortName]);
            $stmt->execute(['pwa_front_description', $pwaFrontDescription]);
            $stmt->execute(['pwa_front_theme_color', $pwaFrontThemeColor]);
            $stmt->execute(['pwa_front_bg_color', $pwaFrontBgColor]);

            $stmt->execute(['pwa_admin_enabled', $pwaAdminEnabled]);
            $stmt->execute(['pwa_admin_name', $pwaAdminName]);
            $stmt->execute(['pwa_admin_short_name', $pwaAdminShortName]);
            $stmt->execute(['pwa_admin_theme_color', $pwaAdminThemeColor]);
            $stmt->execute(['pwa_admin_bg_color', $pwaAdminBgColor]);

            // 프론트 아이콘 업로드
            if (isset($_FILES['pwa_front_icon']) && $_FILES['pwa_front_icon']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = BASE_PATH . '/storage/pwa/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $allowedTypes = ['image/png', 'image/x-icon', 'image/svg+xml'];
                $fileType = $_FILES['pwa_front_icon']['type'];

                if (in_array($fileType, $allowedTypes)) {
                    $extension = pathinfo($_FILES['pwa_front_icon']['name'], PATHINFO_EXTENSION);
                    $fileName = 'front_icon_' . time() . '.' . $extension;
                    $targetPath = $uploadDir . $fileName;

                    if (move_uploaded_file($_FILES['pwa_front_icon']['tmp_name'], $targetPath)) {
                        // 이전 아이콘 삭제
                        $oldIcon = $settings['pwa_front_icon'] ?? '';
                        if ($oldIcon && file_exists(BASE_PATH . $oldIcon)) {
                            @unlink(BASE_PATH . $oldIcon);
                        }
                        $stmt->execute(['pwa_front_icon', '/storage/pwa/' . $fileName]);
                    }
                }
            }

            // 관리자 아이콘 업로드
            if (isset($_FILES['pwa_admin_icon']) && $_FILES['pwa_admin_icon']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = BASE_PATH . '/storage/pwa/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $allowedTypes = ['image/png', 'image/x-icon', 'image/svg+xml'];
                $fileType = $_FILES['pwa_admin_icon']['type'];

                if (in_array($fileType, $allowedTypes)) {
                    $extension = pathinfo($_FILES['pwa_admin_icon']['name'], PATHINFO_EXTENSION);
                    $fileName = 'admin_icon_' . time() . '.' . $extension;
                    $targetPath = $uploadDir . $fileName;

                    if (move_uploaded_file($_FILES['pwa_admin_icon']['tmp_name'], $targetPath)) {
                        // 이전 아이콘 삭제
                        $oldIcon = $settings['pwa_admin_icon'] ?? '';
                        if ($oldIcon && file_exists(BASE_PATH . $oldIcon)) {
                            @unlink(BASE_PATH . $oldIcon);
                        }
                        $stmt->execute(['pwa_admin_icon', '/storage/pwa/' . $fileName]);
                    }
                }
            }

            $message = __('admin.settings.pwa.success');
            $messageType = 'success';

            // 설정 다시 로드
            $settings['pwa_front_enabled'] = $pwaFrontEnabled;
            $settings['pwa_front_name'] = $pwaFrontName;
            $settings['pwa_front_short_name'] = $pwaFrontShortName;
            $settings['pwa_front_description'] = $pwaFrontDescription;
            $settings['pwa_front_theme_color'] = $pwaFrontThemeColor;
            $settings['pwa_front_bg_color'] = $pwaFrontBgColor;
            $settings['pwa_admin_enabled'] = $pwaAdminEnabled;
            $settings['pwa_admin_name'] = $pwaAdminName;
            $settings['pwa_admin_short_name'] = $pwaAdminShortName;
            $settings['pwa_admin_theme_color'] = $pwaAdminThemeColor;
            $settings['pwa_admin_bg_color'] = $pwaAdminBgColor;
        } catch (PDOException $e) {
            $message = '저장 실패: ' . $e->getMessage();
            $messageType = 'error';
        }
    } elseif ($action === 'delete_pwa_front_icon') {
        // 프론트 PWA 아이콘 삭제
        try {
            $oldIcon = $settings['pwa_front_icon'] ?? '';
            if ($oldIcon && file_exists(BASE_PATH . $oldIcon)) {
                @unlink(BASE_PATH . $oldIcon);
            }
            $stmt = $pdo->prepare("DELETE FROM rzx_settings WHERE `key` = ?");
            $stmt->execute(['pwa_front_icon']);
            unset($settings['pwa_front_icon']);
            $message = __('admin.settings.pwa.icon_deleted');
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = '삭제 실패: ' . $e->getMessage();
            $messageType = 'error';
        }
    } elseif ($action === 'delete_pwa_admin_icon') {
        // 관리자 PWA 아이콘 삭제
        try {
            $oldIcon = $settings['pwa_admin_icon'] ?? '';
            if ($oldIcon && file_exists(BASE_PATH . $oldIcon)) {
                @unlink(BASE_PATH . $oldIcon);
            }
            $stmt = $pdo->prepare("DELETE FROM rzx_settings WHERE `key` = ?");
            $stmt->execute(['pwa_admin_icon']);
            unset($settings['pwa_admin_icon']);
            $message = __('admin.settings.pwa.icon_deleted');
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = '삭제 실패: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Load current settings
$settings = [];
$stmt = $pdo->query("SELECT rzx_settings.key, value FROM rzx_settings");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['key']] = $row['value'];
}

// Base URLs for navigation
$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');
?>
<!DOCTYPE html>
<html lang="<?php echo $config['locale'] ?? 'ko'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>

    <!-- PWA Admin -->
    <link rel="manifest" href="<?php echo $baseUrl; ?>/admin-manifest.json">
    <meta name="theme-color" content="#18181b">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="RezlyX Admin">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="application-name" content="RezlyX Admin">
    <link rel="apple-touch-icon" href="<?php echo $baseUrl; ?>/assets/icons/admin-icon-192x192.png">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class'
        }
    </script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css">
    <style>
        body { font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, sans-serif; }
    </style>
    <script>
        if (localStorage.getItem('darkMode') === 'true' ||
            (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="bg-zinc-100 dark:bg-zinc-900 min-h-screen transition-colors">
    <div class="flex">
        <!-- Sidebar -->
        <?php include __DIR__ . '/partials/admin-sidebar.php'; ?>

        <!-- Main Content -->
        <main class="flex-1 ml-64">
            <!-- Top Bar -->
            <header class="bg-white dark:bg-zinc-800 shadow-sm h-16 flex items-center justify-between px-6 transition-colors">
                <h1 class="text-xl font-semibold text-zinc-900 dark:text-white"><?= __('admin.settings.title') ?></h1>
                <div class="flex items-center space-x-4">
                    <!-- Language Selector -->
                    <div class="relative">
                        <button id="langBtn" class="flex items-center px-3 py-1.5 text-sm text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                            </svg>
                            <span id="currentLang"><?php echo strtoupper($config['locale'] ?? 'ko'); ?></span>
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div id="langDropdown" class="hidden absolute right-0 mt-2 w-32 bg-white dark:bg-zinc-800 rounded-lg shadow-lg border border-zinc-200 dark:border-zinc-700 py-1 z-50">
                            <a href="?lang=ko" class="block px-4 py-2 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700">한국어</a>
                            <a href="?lang=en" class="block px-4 py-2 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700">English</a>
                            <a href="?lang=ja" class="block px-4 py-2 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700">日本語</a>
                        </div>
                    </div>

                    <!-- Dark Mode Toggle -->
                    <button id="darkModeBtn" class="p-2 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition" title="<?= __('admin.dark_mode') ?>">
                        <svg id="sunIcon" class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <svg id="moonIcon" class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                    </button>

                    <span class="text-sm text-zinc-500 dark:text-zinc-400"><?php echo date('Y-m-d H:i'); ?></span>
                    <div class="flex items-center">
                        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mr-2">Admin</span>
                        <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white text-sm font-medium">A</div>
                    </div>
                </div>
            </header>

            <!-- Settings Content -->
            <div class="p-6">
                <?php if ($message): ?>
                <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200'; ?>">
                    <div class="flex items-center">
                        <?php if ($messageType === 'success'): ?>
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <?php else: ?>
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Admin Path Settings -->
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 mb-6 transition-colors">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4"><?= __('admin.settings.admin_path.title') ?></h2>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-4">
                        <?= __('admin.settings.admin_path.description') ?><br>
                        <?= __('admin.settings.admin_path.current_url') ?>: <code class="bg-zinc-100 dark:bg-zinc-700 px-2 py-1 rounded text-blue-600 dark:text-blue-400"><?php echo htmlspecialchars($config['app_url'] ?? ''); ?>/<?php echo htmlspecialchars($settings['admin_path'] ?? 'admin'); ?>/</code>
                    </p>

                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="update_admin_path">
                        <div>
                            <label for="admin_path" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.settings.admin_path.label') ?></label>
                            <div class="flex items-center space-x-2">
                                <span class="text-zinc-500 dark:text-zinc-400">/</span>
                                <input type="text" name="admin_path" id="admin_path"
                                       value="<?php echo htmlspecialchars($settings['admin_path'] ?? 'admin'); ?>"
                                       class="flex-1 px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="admin"
                                       pattern="[a-zA-Z0-9_-]+"
                                       required>
                                <span class="text-zinc-500 dark:text-zinc-400">/</span>
                            </div>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1"><?= __('admin.settings.admin_path.hint') ?></p>
                        </div>
                        <div class="flex items-center justify-between pt-4 border-t dark:border-zinc-700">
                            <p class="text-sm text-amber-600">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <?= __('admin.settings.admin_path.warning') ?>
                            </p>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition">
                                <?= __('admin.settings.admin_path.button') ?>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Site Settings -->
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 mb-6 transition-colors">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4"><?= __('admin.settings.site.title') ?></h2>
                    <form method="POST" enctype="multipart/form-data" class="space-y-6">
                        <input type="hidden" name="action" value="update_site_settings">

                        <!-- 사이트 분류 -->
                        <div>
                            <label for="site_category" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.settings.site.category_label') ?></label>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2"><?= __('admin.settings.site.category_description') ?></p>
                            <?php
                            $currentCategory = $settings['site_category'] ?? '';
                            $categoryKeys = ['beauty_salon', 'nail_salon', 'skincare', 'massage', 'hospital', 'dental', 'studio', 'restaurant', 'accommodation', 'sports', 'education', 'consulting', 'pet', 'car', 'other'];
                            $categories = ['' => __('admin.settings.site.category_placeholder')];
                            foreach ($categoryKeys as $key) {
                                $categories[$key] = __('admin.settings.site.categories.' . $key);
                            }
                            ?>
                            <select name="site_category" id="site_category"
                                    class="w-full md:w-1/2 px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <?php foreach ($categories as $value => $label): ?>
                                <option value="<?php echo $value; ?>" <?php echo $currentCategory === $value ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($label); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- 사이트 이름 (다국어 지원) -->
                            <div>
                                <label for="site_name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.settings.site.name') ?></label>
                                <div class="flex items-center gap-2">
                                    <input type="text" name="site_name" id="site_name"
                                           value="<?php echo htmlspecialchars($settings['site_name'] ?? ''); ?>"
                                           class="flex-1 px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <button type="button" onclick="openMultilangModal('site.name', 'site_name')"
                                            class="p-2 text-blue-600 hover:text-blue-700 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition"
                                            title="<?= __('admin.settings.multilang.button_title') ?>">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label for="site_url" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.settings.site.url') ?></label>
                                <input type="url" name="site_url" id="site_url"
                                       value="<?php echo htmlspecialchars($settings['site_url'] ?? ''); ?>"
                                       class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <!-- 사이트 제목 (다국어 지원) -->
                        <div>
                            <label for="site_tagline" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.settings.site.tagline') ?></label>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2"><?= __('admin.settings.site.tagline_hint') ?></p>
                            <div class="flex items-center gap-2">
                                <input type="text" name="site_tagline" id="site_tagline"
                                       value="<?php echo htmlspecialchars($settings['site_tagline'] ?? ''); ?>"
                                       class="flex-1 px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="<?= __('admin.settings.multilang.placeholder') ?>">
                                <button type="button" onclick="openMultilangModal('site.tagline', 'site_tagline')"
                                        class="p-2 text-blue-600 hover:text-blue-700 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition"
                                        title="<?= __('admin.settings.multilang.button_title') ?>">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- 로고 설정 -->
                        <div class="border-t dark:border-zinc-700 pt-6">
                            <h3 class="text-md font-semibold text-zinc-900 dark:text-white mb-4"><?= __('admin.settings.logo.title') ?></h3>

                            <!-- 로고 형식 선택 -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('admin.settings.logo.type_label') ?></label>
                                <div class="flex flex-wrap gap-4">
                                    <?php $currentLogoType = $settings['logo_type'] ?? 'text'; ?>
                                    <label class="flex items-center cursor-pointer">
                                        <input type="radio" name="logo_type" value="text"
                                               <?php echo $currentLogoType === 'text' ? 'checked' : ''; ?>
                                               class="w-4 h-4 text-blue-600 border-zinc-300 focus:ring-blue-500">
                                        <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300"><?= __('admin.settings.logo.type_text') ?></span>
                                    </label>
                                    <label class="flex items-center cursor-pointer">
                                        <input type="radio" name="logo_type" value="image"
                                               <?php echo $currentLogoType === 'image' ? 'checked' : ''; ?>
                                               class="w-4 h-4 text-blue-600 border-zinc-300 focus:ring-blue-500">
                                        <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300"><?= __('admin.settings.logo.type_image') ?></span>
                                    </label>
                                    <label class="flex items-center cursor-pointer">
                                        <input type="radio" name="logo_type" value="image_text"
                                               <?php echo $currentLogoType === 'image_text' ? 'checked' : ''; ?>
                                               class="w-4 h-4 text-blue-600 border-zinc-300 focus:ring-blue-500">
                                        <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300"><?= __('admin.settings.logo.type_image_text') ?></span>
                                    </label>
                                </div>
                            </div>

                            <!-- 로고 이미지 업로드 -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('admin.settings.logo.image_label') ?></label>

                                <?php if (!empty($settings['logo_image'])): ?>
                                <!-- 현재 로고 미리보기 -->
                                <div class="mb-3 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg inline-block">
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2"><?= __('admin.settings.logo.current') ?>:</p>
                                    <img src="<?php echo $baseUrl . htmlspecialchars($settings['logo_image']); ?>"
                                         alt="<?= __('admin.settings.logo.current') ?>" class="max-h-16 object-contain">
                                </div>
                                <?php endif; ?>

                                <div class="flex items-center gap-4">
                                    <div class="flex-1">
                                        <input type="file" name="logo_image" id="logo_image"
                                               accept="image/jpeg,image/png,image/gif,image/svg+xml,image/webp"
                                               class="block w-full text-sm text-zinc-500 dark:text-zinc-400
                                                      file:mr-4 file:py-2 file:px-4
                                                      file:rounded-lg file:border-0
                                                      file:text-sm file:font-medium
                                                      file:bg-blue-50 file:text-blue-700
                                                      dark:file:bg-blue-900/30 dark:file:text-blue-400
                                                      hover:file:bg-blue-100 dark:hover:file:bg-blue-900/50
                                                      cursor-pointer">
                                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400"><?= __('admin.settings.logo.hint') ?></p>
                                    </div>

                                    <?php if (!empty($settings['logo_image'])): ?>
                                    <button type="button" onclick="deleteLogo()" class="px-3 py-2 text-sm text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition">
                                        <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                        <?= __('admin.settings.logo.delete') ?>
                                    </button>
                                    <?php endif; ?>
                                </div>

                                <!-- 이미지 미리보기 -->
                                <div id="logoPreview" class="mt-3 hidden">
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2"><?= __('admin.settings.logo.preview') ?>:</p>
                                    <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg inline-block">
                                        <img id="logoPreviewImg" src="" alt="<?= __('admin.settings.logo.preview') ?>" class="max-h-16 object-contain">
                                    </div>
                                </div>
                            </div>

                            <!-- 로고 미리보기 (실제 표시 형태) -->
                            <div class="p-4 bg-zinc-100 dark:bg-zinc-900 rounded-lg">
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2"><?= __('admin.settings.logo.display_preview') ?>:</p>
                                <div id="logoDisplayPreview" class="flex items-center text-xl font-bold text-blue-600 dark:text-blue-400">
                                    <?php
                                    $logoType = $settings['logo_type'] ?? 'text';
                                    $siteName = $settings['site_name'] ?? 'RezlyX';
                                    $logoImage = $settings['logo_image'] ?? '';

                                    if ($logoType === 'image' && $logoImage): ?>
                                        <img src="<?php echo $baseUrl . htmlspecialchars($logoImage); ?>" alt="<?php echo htmlspecialchars($siteName); ?>" class="h-10 object-contain">
                                    <?php elseif ($logoType === 'image_text' && $logoImage): ?>
                                        <img src="<?php echo $baseUrl . htmlspecialchars($logoImage); ?>" alt="" class="h-10 object-contain mr-2">
                                        <span><?php echo htmlspecialchars($siteName); ?></span>
                                    <?php else: ?>
                                        <span><?php echo htmlspecialchars($siteName); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end pt-4 border-t dark:border-zinc-700">
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition">
                                <?= __('admin.buttons.save') ?>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Mail Settings -->
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 mb-6 transition-colors">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2">메일 설정</h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-6">가입환영 메일, 인증메일, 알림 등 발송에 사용되는 메일 설정입니다.</p>

                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="action" value="update_mail_settings">

                        <!-- 기본 발신자 정보 -->
                        <div class="border-b dark:border-zinc-700 pb-6">
                            <h3 class="text-md font-semibold text-zinc-900 dark:text-white mb-4">발신자 정보</h3>

                            <!-- 기본 발신자 이름 -->
                            <div class="mb-4">
                                <label for="mail_from_name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">기본 발신자 이름</label>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2">가입환영 메일, 인증메일, 알림 등을 발송할 때 사용할 이름입니다. 사이트 이름 사용을 권장합니다.</p>
                                <input type="text" name="mail_from_name" id="mail_from_name"
                                       value="<?php echo htmlspecialchars($settings['mail_from_name'] ?? ($settings['site_name'] ?? 'RezlyX')); ?>"
                                       class="w-full max-w-md px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="사이트 이름">
                            </div>

                            <!-- 기본 발신자 이메일 -->
                            <div class="mb-4">
                                <label for="mail_from_address" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">기본 발신자 이메일</label>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2">가입환영 메일, 인증메일, 알림 등을 발송할 때 사용할 메일 주소입니다.</p>
                                <div class="flex items-center gap-4 flex-wrap">
                                    <input type="email" name="mail_from_address" id="mail_from_address"
                                           value="<?php echo htmlspecialchars($settings['mail_from_address'] ?? ''); ?>"
                                           class="w-full max-w-md px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                           placeholder="noreply@example.com">
                                    <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300 cursor-pointer">
                                        <input type="checkbox" name="mail_apply_all" value="1"
                                               <?php echo ($settings['mail_apply_all'] ?? '0') === '1' ? 'checked' : ''; ?>
                                               class="w-4 h-4 text-blue-600 border-zinc-300 rounded focus:ring-blue-500">
                                        <span>일괄 적용</span>
                                    </label>
                                </div>
                                <p class="text-xs text-amber-600 dark:text-amber-400 mt-2">
                                    <svg class="inline w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    SMTP 또는 외부 API 사용시 일괄 적용 옵션을 선택하지 않으면 스팸으로 취급될 가능성이 높아지니 주의하십시오.
                                </p>
                            </div>

                            <!-- Reply-To 주소 -->
                            <div class="mb-4">
                                <label for="mail_reply_to" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Reply-To 주소</label>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2">받는이가 "답장"을 클릭했을 때 발신자 주소가 아닌 다른 주소로 답장을 받을 수 있습니다. 발송 방법에 따라 이 기능을 지원하지 않을 수도 있습니다.</p>
                                <input type="email" name="mail_reply_to" id="mail_reply_to"
                                       value="<?php echo htmlspecialchars($settings['mail_reply_to'] ?? ''); ?>"
                                       class="w-full max-w-md px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="support@example.com">
                            </div>
                        </div>

                        <!-- 발송 방법 -->
                        <div class="border-b dark:border-zinc-700 pb-6">
                            <h3 class="text-md font-semibold text-zinc-900 dark:text-white mb-4">발송 방법</h3>

                            <div class="mb-4">
                                <label for="mail_method" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">메일 발송 방법</label>
                                <select name="mail_method" id="mail_method"
                                        class="w-full max-w-md px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        onchange="toggleSmtpSettings(this.value)">
                                    <option value="mail" <?php echo ($settings['mail_method'] ?? 'mail') === 'mail' ? 'selected' : ''; ?>>PHP mail() 함수</option>
                                    <option value="smtp" <?php echo ($settings['mail_method'] ?? '') === 'smtp' ? 'selected' : ''; ?>>SMTP</option>
                                </select>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-2" id="mail_method_hint">
                                    PHP에 내장된 mail() 함수를 사용합니다. 발송 성공률이 매우 낮습니다.<br>
                                    이 옵션은 추후 지원되지 않을 수 있으니, 가능하면 다른 방식을 선택하시기 바랍니다.
                                </p>
                            </div>

                            <!-- SMTP 설정 (SMTP 선택 시 표시) -->
                            <div id="smtp_settings" class="mt-6 p-4 bg-zinc-50 dark:bg-zinc-700/50 rounded-lg <?php echo ($settings['mail_method'] ?? 'mail') !== 'smtp' ? 'hidden' : ''; ?>">
                                <h4 class="text-sm font-semibold text-zinc-900 dark:text-white mb-4">SMTP 서버 설정</h4>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- SMTP 호스트 -->
                                    <div>
                                        <label for="smtp_host" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">SMTP 호스트</label>
                                        <input type="text" name="smtp_host" id="smtp_host"
                                               value="<?php echo htmlspecialchars($settings['smtp_host'] ?? ''); ?>"
                                               class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                               placeholder="smtp.gmail.com">
                                    </div>

                                    <!-- SMTP 포트 -->
                                    <div>
                                        <label for="smtp_port" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">SMTP 포트</label>
                                        <input type="text" name="smtp_port" id="smtp_port"
                                               value="<?php echo htmlspecialchars($settings['smtp_port'] ?? '587'); ?>"
                                               class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                               placeholder="587">
                                    </div>

                                    <!-- 암호화 방식 -->
                                    <div>
                                        <label for="smtp_encryption" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">암호화 방식</label>
                                        <select name="smtp_encryption" id="smtp_encryption"
                                                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                            <option value="tls" <?php echo ($settings['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                            <option value="ssl" <?php echo ($settings['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                            <option value="" <?php echo ($settings['smtp_encryption'] ?? '') === '' ? 'selected' : ''; ?>>없음</option>
                                        </select>
                                    </div>

                                    <!-- SMTP 사용자명 -->
                                    <div>
                                        <label for="smtp_username" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">SMTP 사용자명</label>
                                        <input type="text" name="smtp_username" id="smtp_username"
                                               value="<?php echo htmlspecialchars($settings['smtp_username'] ?? ''); ?>"
                                               class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                               placeholder="your-email@gmail.com">
                                    </div>

                                    <!-- SMTP 비밀번호 -->
                                    <div class="md:col-span-2">
                                        <label for="smtp_password" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">SMTP 비밀번호</label>
                                        <input type="password" name="smtp_password" id="smtp_password"
                                               class="w-full max-w-md px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                               placeholder="<?php echo !empty($settings['smtp_password']) ? '••••••••' : '앱 비밀번호 또는 SMTP 비밀번호'; ?>">
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">비밀번호를 변경하려면 입력하세요. 빈칸으로 두면 기존 값이 유지됩니다.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end pt-4">
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition">
                                저장
                            </button>
                        </div>
                    </form>
                </div>

                <script>
                function toggleSmtpSettings(method) {
                    const smtpSettings = document.getElementById('smtp_settings');
                    const hint = document.getElementById('mail_method_hint');

                    if (method === 'smtp') {
                        smtpSettings.classList.remove('hidden');
                        hint.innerHTML = 'SMTP 서버를 통해 메일을 발송합니다. 발송 성공률이 높고 안정적입니다.';
                    } else {
                        smtpSettings.classList.add('hidden');
                        hint.innerHTML = 'PHP에 내장된 mail() 함수를 사용합니다. 발송 성공률이 매우 낮습니다.<br>이 옵션은 추후 지원되지 않을 수 있으니, 가능하면 다른 방식을 선택하시기 바랍니다.';
                    }
                }
                </script>

                <!-- SEO Settings -->
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 mb-6 transition-colors">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2"><?= __('admin.settings.seo.title') ?></h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-6"><?= __('admin.settings.seo.description') ?></p>

                    <form method="POST" enctype="multipart/form-data" class="space-y-6">
                        <input type="hidden" name="action" value="update_seo_settings">

                        <!-- 메타 태그 섹션 -->
                        <div class="border-b dark:border-zinc-700 pb-6">
                            <h3 class="text-md font-semibold text-zinc-900 dark:text-white mb-4"><?= __('admin.settings.seo.meta.title') ?></h3>

                            <!-- 메타 설명 (다국어 지원) -->
                            <div class="mb-4">
                                <label for="seo_description" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.settings.seo.meta.description_label') ?></label>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2"><?= __('admin.settings.seo.meta.description_hint') ?></p>
                                <div class="flex items-start gap-2">
                                    <textarea name="seo_description" id="seo_description" rows="3"
                                              class="flex-1 px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                              maxlength="200"><?php echo htmlspecialchars($settings['seo_description'] ?? ''); ?></textarea>
                                    <button type="button" onclick="openMultilangModal('seo.description', 'seo_description')"
                                            class="p-2 text-blue-600 hover:text-blue-700 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition"
                                            title="<?= __('admin.settings.multilang.button_title') ?>">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                                        </svg>
                                    </button>
                                </div>
                                <div class="text-xs text-zinc-400 mt-1"><span id="descCharCount">0</span>/200</div>
                            </div>

                            <!-- 메타 키워드 (다국어 지원) -->
                            <div>
                                <label for="seo_keywords" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.settings.seo.meta.keywords_label') ?></label>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2"><?= __('admin.settings.seo.meta.keywords_hint') ?></p>
                                <div class="flex items-center gap-2">
                                    <input type="text" name="seo_keywords" id="seo_keywords"
                                           value="<?php echo htmlspecialchars($settings['seo_keywords'] ?? ''); ?>"
                                           class="flex-1 px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                           placeholder="<?= __('admin.settings.seo.meta.keywords_placeholder') ?>">
                                    <button type="button" onclick="openMultilangModal('seo.keywords', 'seo_keywords')"
                                            class="p-2 text-blue-600 hover:text-blue-700 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition"
                                            title="<?= __('admin.settings.multilang.button_title') ?>">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- 소셜 미디어 (Open Graph) 섹션 -->
                        <div class="border-b dark:border-zinc-700 pb-6">
                            <h3 class="text-md font-semibold text-zinc-900 dark:text-white mb-2"><?= __('admin.settings.seo.og.title') ?></h3>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-4"><?= __('admin.settings.seo.og.description') ?></p>

                            <!-- OG 이미지 -->
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('admin.settings.seo.og.image_label') ?></label>

                                <?php if (!empty($settings['og_image'])): ?>
                                <div class="mb-3 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg inline-block">
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2"><?= __('admin.settings.seo.og.image_current') ?>:</p>
                                    <img src="<?php echo $baseUrl . htmlspecialchars($settings['og_image']); ?>"
                                         alt="OG Image" class="max-h-32 object-contain rounded">
                                </div>
                                <?php endif; ?>

                                <div class="flex items-center gap-4">
                                    <div class="flex-1">
                                        <input type="file" name="og_image" id="og_image"
                                               accept="image/jpeg,image/png,image/webp"
                                               class="block w-full text-sm text-zinc-500 dark:text-zinc-400
                                                      file:mr-4 file:py-2 file:px-4
                                                      file:rounded-lg file:border-0
                                                      file:text-sm file:font-medium
                                                      file:bg-blue-50 file:text-blue-700
                                                      dark:file:bg-blue-900/30 dark:file:text-blue-400
                                                      hover:file:bg-blue-100 dark:hover:file:bg-blue-900/50
                                                      cursor-pointer">
                                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400"><?= __('admin.settings.seo.og.image_hint') ?></p>
                                    </div>

                                    <?php if (!empty($settings['og_image'])): ?>
                                    <button type="button" onclick="deleteOgImage()" class="px-3 py-2 text-sm text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition">
                                        <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                        <?= __('admin.settings.seo.og.image_delete') ?>
                                    </button>
                                    <?php endif; ?>
                                </div>

                                <!-- OG 이미지 미리보기 -->
                                <div id="ogImagePreview" class="mt-3 hidden">
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2"><?= __('admin.settings.seo.og.image_preview') ?>:</p>
                                    <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg inline-block">
                                        <img id="ogImagePreviewImg" src="" alt="Preview" class="max-h-32 object-contain rounded">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 검색 엔진 설정 섹션 -->
                        <div class="border-b dark:border-zinc-700 pb-6">
                            <h3 class="text-md font-semibold text-zinc-900 dark:text-white mb-4"><?= __('admin.settings.seo.search_engine.title') ?></h3>

                            <!-- Robots 설정 -->
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('admin.settings.seo.search_engine.robots_label') ?></label>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2"><?= __('admin.settings.seo.search_engine.robots_hint') ?></p>
                                <?php $currentRobots = $settings['seo_robots'] ?? 'index'; ?>
                                <div class="flex flex-wrap gap-4">
                                    <label class="flex items-center cursor-pointer">
                                        <input type="radio" name="seo_robots" value="index"
                                               <?php echo $currentRobots === 'index' ? 'checked' : ''; ?>
                                               class="w-4 h-4 text-blue-600 border-zinc-300 focus:ring-blue-500">
                                        <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300"><?= __('admin.settings.seo.search_engine.robots_index') ?></span>
                                    </label>
                                    <label class="flex items-center cursor-pointer">
                                        <input type="radio" name="seo_robots" value="noindex"
                                               <?php echo $currentRobots === 'noindex' ? 'checked' : ''; ?>
                                               class="w-4 h-4 text-blue-600 border-zinc-300 focus:ring-blue-500">
                                        <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300"><?= __('admin.settings.seo.search_engine.robots_noindex') ?></span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- 웹마스터 도구 인증 섹션 -->
                        <div class="border-b dark:border-zinc-700 pb-6">
                            <h3 class="text-md font-semibold text-zinc-900 dark:text-white mb-4"><?= __('admin.settings.seo.webmaster.title') ?></h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Google Search Console -->
                                <div>
                                    <label for="google_verification" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.settings.seo.webmaster.google_label') ?></label>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2"><?= __('admin.settings.seo.webmaster.google_hint') ?></p>
                                    <input type="text" name="google_verification" id="google_verification"
                                           value="<?php echo htmlspecialchars($settings['google_verification'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                           placeholder="<?= __('admin.settings.seo.webmaster.google_placeholder') ?>">
                                </div>

                                <!-- 네이버 웹마스터 -->
                                <div>
                                    <label for="naver_verification" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.settings.seo.webmaster.naver_label') ?></label>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2"><?= __('admin.settings.seo.webmaster.naver_hint') ?></p>
                                    <input type="text" name="naver_verification" id="naver_verification"
                                           value="<?php echo htmlspecialchars($settings['naver_verification'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                           placeholder="<?= __('admin.settings.seo.webmaster.naver_placeholder') ?>">
                                </div>
                            </div>
                        </div>

                        <!-- 분석 도구 연동 섹션 -->
                        <div>
                            <h3 class="text-md font-semibold text-zinc-900 dark:text-white mb-4"><?= __('admin.settings.seo.analytics.title') ?></h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Google Analytics -->
                                <div>
                                    <label for="ga_tracking_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.settings.seo.analytics.ga_label') ?></label>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2"><?= __('admin.settings.seo.analytics.ga_hint') ?></p>
                                    <input type="text" name="ga_tracking_id" id="ga_tracking_id"
                                           value="<?php echo htmlspecialchars($settings['ga_tracking_id'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                           placeholder="<?= __('admin.settings.seo.analytics.ga_placeholder') ?>">
                                </div>

                                <!-- Google Tag Manager -->
                                <div>
                                    <label for="gtm_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.settings.seo.analytics.gtm_label') ?></label>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2"><?= __('admin.settings.seo.analytics.gtm_hint') ?></p>
                                    <input type="text" name="gtm_id" id="gtm_id"
                                           value="<?php echo htmlspecialchars($settings['gtm_id'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                           placeholder="<?= __('admin.settings.seo.analytics.gtm_placeholder') ?>">
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end pt-4 border-t dark:border-zinc-700">
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition">
                                <?= __('admin.buttons.save') ?>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- PWA Settings -->
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 mb-6 transition-colors">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2"><?= __('admin.settings.pwa.title') ?></h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-6"><?= __('admin.settings.pwa.description') ?></p>

                    <form method="POST" enctype="multipart/form-data" class="space-y-8">
                        <input type="hidden" name="action" value="update_pwa_settings">

                        <!-- 프론트엔드 PWA 설정 -->
                        <div class="border dark:border-zinc-700 rounded-lg p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="text-md font-semibold text-zinc-900 dark:text-white"><?= __('admin.settings.pwa.front.title') ?></h3>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400"><?= __('admin.settings.pwa.front.description') ?></p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="pwa_front_enabled" value="1"
                                           <?php echo ($settings['pwa_front_enabled'] ?? '1') === '1' ? 'checked' : ''; ?>
                                           class="sr-only peer">
                                    <div class="w-11 h-6 bg-zinc-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-zinc-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                                </label>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- 앱 이름 -->
                                <div>
                                    <label for="pwa_front_name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.settings.pwa.front.app_name') ?></label>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2"><?= __('admin.settings.pwa.front.app_name_hint') ?></p>
                                    <input type="text" name="pwa_front_name" id="pwa_front_name"
                                           value="<?php echo htmlspecialchars($settings['pwa_front_name'] ?? 'RezlyX'); ?>"
                                           class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <!-- 짧은 이름 -->
                                <div>
                                    <label for="pwa_front_short_name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.settings.pwa.front.short_name') ?></label>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2"><?= __('admin.settings.pwa.front.short_name_hint') ?></p>
                                    <input type="text" name="pwa_front_short_name" id="pwa_front_short_name" maxlength="12"
                                           value="<?php echo htmlspecialchars($settings['pwa_front_short_name'] ?? 'RezlyX'); ?>"
                                           class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <!-- 앱 설명 -->
                                <div class="md:col-span-2">
                                    <label for="pwa_front_description" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.settings.pwa.front.app_description') ?></label>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2"><?= __('admin.settings.pwa.front.app_description_hint') ?></p>
                                    <input type="text" name="pwa_front_description" id="pwa_front_description"
                                           value="<?php echo htmlspecialchars($settings['pwa_front_description'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <!-- 테마 색상 -->
                                <div>
                                    <label for="pwa_front_theme_color" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.settings.pwa.front.theme_color') ?></label>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2"><?= __('admin.settings.pwa.front.theme_color_hint') ?></p>
                                    <div class="flex items-center gap-2">
                                        <input type="color" name="pwa_front_theme_color" id="pwa_front_theme_color"
                                               value="<?php echo htmlspecialchars($settings['pwa_front_theme_color'] ?? '#3b82f6'); ?>"
                                               class="w-12 h-10 rounded border border-zinc-300 dark:border-zinc-600 cursor-pointer">
                                        <input type="text" id="pwa_front_theme_color_text"
                                               value="<?php echo htmlspecialchars($settings['pwa_front_theme_color'] ?? '#3b82f6'); ?>"
                                               class="flex-1 px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                               placeholder="#3b82f6">
                                    </div>
                                </div>

                                <!-- 배경 색상 -->
                                <div>
                                    <label for="pwa_front_bg_color" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.settings.pwa.front.background_color') ?></label>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2"><?= __('admin.settings.pwa.front.background_color_hint') ?></p>
                                    <div class="flex items-center gap-2">
                                        <input type="color" name="pwa_front_bg_color" id="pwa_front_bg_color"
                                               value="<?php echo htmlspecialchars($settings['pwa_front_bg_color'] ?? '#ffffff'); ?>"
                                               class="w-12 h-10 rounded border border-zinc-300 dark:border-zinc-600 cursor-pointer">
                                        <input type="text" id="pwa_front_bg_color_text"
                                               value="<?php echo htmlspecialchars($settings['pwa_front_bg_color'] ?? '#ffffff'); ?>"
                                               class="flex-1 px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                               placeholder="#ffffff">
                                    </div>
                                </div>

                                <!-- 앱 아이콘 -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.settings.pwa.front.icon') ?></label>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2"><?= __('admin.settings.pwa.front.icon_hint') ?></p>

                                    <?php if (!empty($settings['pwa_front_icon'])): ?>
                                    <div class="mb-3 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg inline-block">
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2"><?= __('admin.settings.pwa.front.icon_current') ?>:</p>
                                        <img src="<?php echo $baseUrl . htmlspecialchars($settings['pwa_front_icon']); ?>"
                                             alt="Front Icon" class="w-16 h-16 object-contain rounded">
                                    </div>
                                    <?php endif; ?>

                                    <div class="flex items-center gap-4">
                                        <input type="file" name="pwa_front_icon" id="pwa_front_icon"
                                               accept="image/png,image/svg+xml,image/x-icon"
                                               class="block w-full text-sm text-zinc-500 dark:text-zinc-400
                                                      file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0
                                                      file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700
                                                      dark:file:bg-blue-900/30 dark:file:text-blue-400
                                                      hover:file:bg-blue-100 dark:hover:file:bg-blue-900/50 cursor-pointer">

                                        <?php if (!empty($settings['pwa_front_icon'])): ?>
                                        <button type="button" onclick="deletePwaIcon('front')" class="px-3 py-2 text-sm text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition whitespace-nowrap">
                                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                            <?= __('admin.buttons.delete') ?>
                                        </button>
                                        <?php endif; ?>
                                    </div>

                                    <!-- 새 아이콘 미리보기 -->
                                    <div id="pwaFrontIconPreview" class="mt-3 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg inline-block hidden">
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2"><?= __('admin.settings.pwa.front.icon_preview') ?>:</p>
                                        <img id="pwaFrontIconPreviewImg" src="" alt="New Icon Preview" class="w-16 h-16 object-contain rounded">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 관리자 PWA 설정 -->
                        <div class="border dark:border-zinc-700 rounded-lg p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="text-md font-semibold text-zinc-900 dark:text-white"><?= __('admin.settings.pwa.admin.title') ?></h3>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400"><?= __('admin.settings.pwa.admin.description') ?></p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="pwa_admin_enabled" value="1"
                                           <?php echo ($settings['pwa_admin_enabled'] ?? '1') === '1' ? 'checked' : ''; ?>
                                           class="sr-only peer">
                                    <div class="w-11 h-6 bg-zinc-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-zinc-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                                </label>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- 앱 이름 -->
                                <div>
                                    <label for="pwa_admin_name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.settings.pwa.admin.app_name') ?></label>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2"><?= __('admin.settings.pwa.admin.app_name_hint') ?></p>
                                    <input type="text" name="pwa_admin_name" id="pwa_admin_name"
                                           value="<?php echo htmlspecialchars($settings['pwa_admin_name'] ?? 'RezlyX Admin'); ?>"
                                           class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <!-- 짧은 이름 -->
                                <div>
                                    <label for="pwa_admin_short_name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.settings.pwa.admin.short_name') ?></label>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2"><?= __('admin.settings.pwa.admin.short_name_hint') ?></p>
                                    <input type="text" name="pwa_admin_short_name" id="pwa_admin_short_name" maxlength="12"
                                           value="<?php echo htmlspecialchars($settings['pwa_admin_short_name'] ?? 'Admin'); ?>"
                                           class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <!-- 테마 색상 -->
                                <div>
                                    <label for="pwa_admin_theme_color" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.settings.pwa.admin.theme_color') ?></label>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2"><?= __('admin.settings.pwa.admin.theme_color_hint') ?></p>
                                    <div class="flex items-center gap-2">
                                        <input type="color" name="pwa_admin_theme_color" id="pwa_admin_theme_color"
                                               value="<?php echo htmlspecialchars($settings['pwa_admin_theme_color'] ?? '#18181b'); ?>"
                                               class="w-12 h-10 rounded border border-zinc-300 dark:border-zinc-600 cursor-pointer">
                                        <input type="text" id="pwa_admin_theme_color_text"
                                               value="<?php echo htmlspecialchars($settings['pwa_admin_theme_color'] ?? '#18181b'); ?>"
                                               class="flex-1 px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                               placeholder="#18181b">
                                    </div>
                                </div>

                                <!-- 배경 색상 -->
                                <div>
                                    <label for="pwa_admin_bg_color" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.settings.pwa.admin.background_color') ?></label>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2"><?= __('admin.settings.pwa.admin.background_color_hint') ?></p>
                                    <div class="flex items-center gap-2">
                                        <input type="color" name="pwa_admin_bg_color" id="pwa_admin_bg_color"
                                               value="<?php echo htmlspecialchars($settings['pwa_admin_bg_color'] ?? '#18181b'); ?>"
                                               class="w-12 h-10 rounded border border-zinc-300 dark:border-zinc-600 cursor-pointer">
                                        <input type="text" id="pwa_admin_bg_color_text"
                                               value="<?php echo htmlspecialchars($settings['pwa_admin_bg_color'] ?? '#18181b'); ?>"
                                               class="flex-1 px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                               placeholder="#18181b">
                                    </div>
                                </div>

                                <!-- 앱 아이콘 -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.settings.pwa.admin.icon') ?></label>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2"><?= __('admin.settings.pwa.admin.icon_hint') ?></p>

                                    <?php if (!empty($settings['pwa_admin_icon'])): ?>
                                    <div class="mb-3 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg inline-block">
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2"><?= __('admin.settings.pwa.admin.icon_current') ?>:</p>
                                        <img src="<?php echo $baseUrl . htmlspecialchars($settings['pwa_admin_icon']); ?>"
                                             alt="Admin Icon" class="w-16 h-16 object-contain rounded">
                                    </div>
                                    <?php endif; ?>

                                    <div class="flex items-center gap-4">
                                        <input type="file" name="pwa_admin_icon" id="pwa_admin_icon"
                                               accept="image/png,image/svg+xml,image/x-icon"
                                               class="block w-full text-sm text-zinc-500 dark:text-zinc-400
                                                      file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0
                                                      file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700
                                                      dark:file:bg-blue-900/30 dark:file:text-blue-400
                                                      hover:file:bg-blue-100 dark:hover:file:bg-blue-900/50 cursor-pointer">

                                        <?php if (!empty($settings['pwa_admin_icon'])): ?>
                                        <button type="button" onclick="deletePwaIcon('admin')" class="px-3 py-2 text-sm text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition whitespace-nowrap">
                                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                            <?= __('admin.buttons.delete') ?>
                                        </button>
                                        <?php endif; ?>
                                    </div>

                                    <!-- 새 아이콘 미리보기 -->
                                    <div id="pwaAdminIconPreview" class="mt-3 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg inline-block hidden">
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2"><?= __('admin.settings.pwa.admin.icon_preview') ?>:</p>
                                        <img id="pwaAdminIconPreviewImg" src="" alt="New Icon Preview" class="w-16 h-16 object-contain rounded">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end pt-4 border-t dark:border-zinc-700">
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition">
                                <?= __('admin.buttons.save') ?>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- System Info -->
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 transition-colors">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4"><?= __('admin.settings.system_info.title') ?></h2>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <span class="text-zinc-500 dark:text-zinc-400"><?= __('admin.settings.system_info.php_version') ?></span>
                            <p class="font-medium text-zinc-900 dark:text-white"><?php echo PHP_VERSION; ?></p>
                        </div>
                        <div>
                            <span class="text-zinc-500 dark:text-zinc-400"><?= __('admin.settings.system_info.environment') ?></span>
                            <p class="font-medium text-zinc-900 dark:text-white"><?php echo $_ENV['APP_ENV'] ?? 'local'; ?></p>
                        </div>
                        <div>
                            <span class="text-zinc-500 dark:text-zinc-400"><?= __('admin.settings.system_info.timezone') ?></span>
                            <p class="font-medium text-zinc-900 dark:text-white"><?php echo $_ENV['APP_TIMEZONE'] ?? 'Asia/Seoul'; ?></p>
                        </div>
                        <div>
                            <span class="text-zinc-500 dark:text-zinc-400"><?= __('admin.settings.system_info.debug_mode') ?></span>
                            <p class="font-medium text-zinc-900 dark:text-white"><?php echo ($_ENV['APP_DEBUG'] ?? 'false') === 'true' ? __('admin.settings.system_info.enabled') : __('admin.settings.system_info.disabled'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- 다국어 입력 모달 컴포넌트 -->
    <?php include __DIR__ . '/components/multilang-modal.php'; ?>

    <script>
        // Language dropdown toggle
        const langBtn = document.getElementById('langBtn');
        const langDropdown = document.getElementById('langDropdown');

        langBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            langDropdown.classList.toggle('hidden');
            console.log('Language dropdown toggled');
        });

        document.addEventListener('click', () => {
            langDropdown.classList.add('hidden');
        });

        // Dark mode toggle
        const darkModeBtn = document.getElementById('darkModeBtn');

        darkModeBtn.addEventListener('click', () => {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('darkMode', isDark);
            console.log('Dark mode:', isDark ? 'enabled' : 'disabled');
        });

        // 로고 이미지 미리보기
        const logoInput = document.getElementById('logo_image');
        const logoPreview = document.getElementById('logoPreview');
        const logoPreviewImg = document.getElementById('logoPreviewImg');

        if (logoInput) {
            logoInput.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        logoPreviewImg.src = e.target.result;
                        logoPreview.classList.remove('hidden');
                        console.log('Logo preview updated');
                    };
                    reader.readAsDataURL(file);
                } else {
                    logoPreview.classList.add('hidden');
                }
            });
        }

        // 로고 삭제
        function deleteLogo() {
            if (confirm('<?= __('admin.settings.logo.delete_confirm') ?>')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="action" value="delete_logo">';
                document.body.appendChild(form);
                form.submit();
            }
        }

        // OG 이미지 삭제
        function deleteOgImage() {
            if (confirm('<?= __('admin.settings.seo.og.image_delete_confirm') ?>')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="action" value="delete_og_image">';
                document.body.appendChild(form);
                form.submit();
            }
        }

        // PWA 아이콘 삭제
        function deletePwaIcon(type) {
            const confirmMsg = '<?= __('admin.settings.logo.delete_confirm') ?>';
            if (confirm(confirmMsg)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="action" value="delete_pwa_' + type + '_icon">';
                document.body.appendChild(form);
                form.submit();
            }
        }

        // PWA 아이콘 미리보기
        function setupPwaIconPreview(inputId, previewId, previewImgId) {
            const input = document.getElementById(inputId);
            const preview = document.getElementById(previewId);
            const previewImg = document.getElementById(previewImgId);

            if (input && preview && previewImg) {
                input.addEventListener('change', (e) => {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            previewImg.src = e.target.result;
                            preview.classList.remove('hidden');
                            console.log('PWA Icon preview updated:', inputId);
                        };
                        reader.readAsDataURL(file);
                    } else {
                        preview.classList.add('hidden');
                    }
                });
            }
        }

        // PWA 색상 입력 동기화
        function setupColorSync(colorInputId, textInputId) {
            const colorInput = document.getElementById(colorInputId);
            const textInput = document.getElementById(textInputId);

            if (colorInput && textInput) {
                // 컬러 피커에서 텍스트로
                colorInput.addEventListener('input', () => {
                    textInput.value = colorInput.value.toUpperCase();
                    console.log('Color synced:', colorInput.value);
                });

                // 텍스트에서 컬러 피커로
                textInput.addEventListener('input', () => {
                    const value = textInput.value;
                    if (/^#[0-9A-Fa-f]{6}$/.test(value)) {
                        colorInput.value = value;
                    }
                });

                // 텍스트 입력 시 # 자동 추가
                textInput.addEventListener('blur', () => {
                    let value = textInput.value.trim();
                    if (value && !value.startsWith('#')) {
                        value = '#' + value;
                    }
                    if (/^#[0-9A-Fa-f]{6}$/.test(value)) {
                        textInput.value = value.toUpperCase();
                        colorInput.value = value;
                    }
                });
            }
        }

        // OG 이미지 미리보기
        const ogImageInput = document.getElementById('og_image');
        const ogImagePreview = document.getElementById('ogImagePreview');
        const ogImagePreviewImg = document.getElementById('ogImagePreviewImg');

        if (ogImageInput) {
            ogImageInput.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        ogImagePreviewImg.src = e.target.result;
                        ogImagePreview.classList.remove('hidden');
                        console.log('OG Image preview updated');
                    };
                    reader.readAsDataURL(file);
                } else {
                    ogImagePreview.classList.add('hidden');
                }
            });
        }

        // 메타 설명 글자 수 카운트
        const seoDescInput = document.getElementById('seo_description');
        const descCharCount = document.getElementById('descCharCount');

        if (seoDescInput && descCharCount) {
            // 초기 글자 수 표시
            descCharCount.textContent = seoDescInput.value.length;

            seoDescInput.addEventListener('input', () => {
                descCharCount.textContent = seoDescInput.value.length;
            });
        }

        // 사이트 관리 메뉴 토글
        function toggleSiteMenu() {
            const subMenu = document.getElementById('siteSubMenu');
            const arrow = document.getElementById('siteMenuArrow');

            if (subMenu.classList.contains('hidden')) {
                subMenu.classList.remove('hidden');
                arrow.style.transform = 'rotate(180deg)';
                localStorage.setItem('siteMenuOpen', 'true');
            } else {
                subMenu.classList.add('hidden');
                arrow.style.transform = 'rotate(0deg)';
                localStorage.setItem('siteMenuOpen', 'false');
            }
            console.log('Site menu toggled');
        }

        // 페이지 로드 시 메뉴 상태 복원
        document.addEventListener('DOMContentLoaded', () => {
            if (localStorage.getItem('siteMenuOpen') === 'true') {
                const subMenu = document.getElementById('siteSubMenu');
                const arrow = document.getElementById('siteMenuArrow');
                if (subMenu && arrow) {
                    subMenu.classList.remove('hidden');
                    arrow.style.transform = 'rotate(180deg)';
                }
            }

            // PWA 아이콘 미리보기 초기화
            setupPwaIconPreview('pwa_front_icon', 'pwaFrontIconPreview', 'pwaFrontIconPreviewImg');
            setupPwaIconPreview('pwa_admin_icon', 'pwaAdminIconPreview', 'pwaAdminIconPreviewImg');

            // PWA 색상 입력 동기화 초기화
            setupColorSync('pwa_front_theme_color', 'pwa_front_theme_color_text');
            setupColorSync('pwa_front_bg_color', 'pwa_front_bg_color_text');
            setupColorSync('pwa_admin_theme_color', 'pwa_admin_theme_color_text');
            setupColorSync('pwa_admin_bg_color', 'pwa_admin_bg_color_text');
        });
    </script>

    <!-- PWA Admin Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', async () => {
                try {
                    const registration = await navigator.serviceWorker.register('/admin-sw.js', { scope: '/admin' });
                    console.log('[Admin PWA] Service Worker registered:', registration.scope);

                    registration.addEventListener('updatefound', () => {
                        const newWorker = registration.installing;
                        console.log('[Admin PWA] New service worker installing...');

                        newWorker.addEventListener('statechange', () => {
                            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                console.log('[Admin PWA] New version available');
                                showAdminUpdateNotification();
                            }
                        });
                    });
                } catch (error) {
                    console.error('[Admin PWA] Service Worker registration failed:', error);
                }
            });
        }

        function showAdminUpdateNotification() {
            const banner = document.createElement('div');
            banner.className = 'fixed bottom-4 right-4 bg-blue-600 text-white p-4 rounded-lg shadow-lg z-50 max-w-sm';
            banner.innerHTML = `
                <p class="text-sm font-medium mb-2">새 버전이 있습니다</p>
                <div class="flex gap-2">
                    <button onclick="location.reload()" class="flex-1 bg-white text-blue-600 px-3 py-1.5 rounded text-sm font-medium hover:bg-blue-50">업데이트</button>
                    <button onclick="this.parentElement.parentElement.remove()" class="px-3 py-1.5 text-sm hover:bg-blue-700 rounded">나중에</button>
                </div>
            `;
            document.body.appendChild(banner);
        }

        // Admin PWA Install
        let adminDeferredPrompt;

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            adminDeferredPrompt = e;
            console.log('[Admin PWA] Install prompt ready');
        });

        async function installAdminPWA() {
            if (!adminDeferredPrompt) return;
            adminDeferredPrompt.prompt();
            const { outcome } = await adminDeferredPrompt.userChoice;
            console.log('[Admin PWA] Install outcome:', outcome);
            adminDeferredPrompt = null;
        }
    </script>
</body>
</html>
