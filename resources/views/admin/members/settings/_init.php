<?php
/**
 * RezlyX Admin Members Settings - Initialization
 * Common setup for all member settings sub-pages
 */

// Base URL calculation
// 1. 환경변수에서 읽기 시도
// 2. config에서 읽기 시도 (app_url 또는 url 키)
// 3. 자동 계산 (현재 스크립트 경로 기반)
if (!empty($_ENV['APP_URL'])) {
    $baseUrl = rtrim($_ENV['APP_URL'], '/');
} elseif (!empty($config['app_url'])) {
    $baseUrl = rtrim($config['app_url'], '/');
} elseif (!empty($config['url'])) {
    $baseUrl = rtrim($config['url'], '/');
} else {
    // 자동 계산: SCRIPT_NAME에서 /resources 이전 경로 추출
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    if (preg_match('#^(.*?)/(resources|admin|index\.php)#', $scriptName, $matches)) {
        $baseUrl = $matches[1];
    } else {
        // 폴백: 빈 문자열 (루트에서 실행)
        $baseUrl = '';
    }
}
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');

// Database connection
try {
    $pdo = new PDO(
        'mysql:host=' . ($_ENV['DB_HOST'] ?? 'localhost') . ';dbname=' . ($_ENV['DB_DATABASE'] ?? 'rezlyx') . ';charset=utf8mb4',
        $_ENV['DB_USERNAME'] ?? 'root',
        $_ENV['DB_PASSWORD'] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
    $dbConnected = true;
} catch (PDOException $e) {
    $dbConnected = false;
    $dbError = $e->getMessage();
}

// Load all settings from database (topbar 언어 선택기 등에 필요)
$settings = [];
$memberSettings = [];
if ($dbConnected) {
    try {
        $stmt = $pdo->query("SELECT `key`, `value` FROM {$prefix}settings");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['key']] = $row['value'];
            // member_ 접두사 설정은 별도 배열에도 저장
            if (str_starts_with($row['key'], 'member_')) {
                $memberSettings[$row['key']] = $row['value'];
            }
        }
    } catch (PDOException $e) {
        // Settings table might not exist yet
    }
}

// Load translations for current locale
$currentLocale = $config['locale'] ?? 'ko';
$translations = [];
if ($dbConnected) {
    try {
        // Check if translations table exists
        $tableCheck = $pdo->query("SHOW TABLES LIKE '{$prefix}translations'");
        if ($tableCheck->rowCount() > 0) {
            // Load translations for term fields
            $stmt = $pdo->prepare("SELECT lang_key, content FROM {$prefix}translations WHERE locale = ? AND lang_key LIKE 'term.%'");
            $stmt->execute([$currentLocale]);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $translations[$row['lang_key']] = $row['content'];
            }
        }
    } catch (PDOException $e) {
        // Translations table might not exist yet
    }
}

/**
 * Get translated value for a field
 * @param string $langKey Translation key (e.g., 'term.1.title')
 * @param string $defaultKey Settings key (e.g., 'member_term_1_title')
 * @param array $translations Loaded translations
 * @param array $memberSettings Member settings
 * @return string
 */
function getTranslatedValue(string $langKey, string $defaultKey, array $translations, array $memberSettings): string
{
    // First check if translation exists for current locale
    if (!empty($translations[$langKey])) {
        return $translations[$langKey];
    }
    // Fall back to default value
    return $memberSettings[$defaultKey] ?? '';
}

// Default member settings
$defaultMemberSettings = [
    // 기본 설정
    'member_registration_mode' => 'yes',
    'member_registration_url_key' => '',
    'member_email_verification' => '1',
    'member_email_validity_days' => '1',
    'member_show_profile_photo' => '1',
    'member_logout_on_password_change' => '1',
    'member_password_recovery_method' => 'link',
    'member_auto_login' => '0',
    'member_default_group' => 'member',

    // 기능 설정
    'member_view_scrap' => '1',
    'member_view_bookmark' => '1',
    'member_view_posts' => '1',
    'member_view_comments' => '1',
    'member_auto_login_manage' => '1',

    // 약관 설정 (5개 약관)
    'member_term_1_title' => '',
    'member_term_1_content' => '',
    'member_term_1_consent' => 'disabled',
    'member_term_2_title' => '',
    'member_term_2_content' => '',
    'member_term_2_consent' => 'disabled',
    'member_term_3_title' => '',
    'member_term_3_content' => '',
    'member_term_3_consent' => 'disabled',
    'member_term_4_title' => '',
    'member_term_4_content' => '',
    'member_term_4_consent' => 'disabled',
    'member_term_5_title' => '',
    'member_term_5_content' => '',
    'member_term_5_consent' => 'disabled',

    // 회원가입 설정
    'member_register_fields' => 'name,email,password,phone',
    'member_register_captcha' => '0',
    'member_welcome_email' => '1',
    'member_register_redirect_url' => '',
    'member_email_provider_mode' => 'none',
    'member_email_provider_list' => '',

    // 로그인 설정
    'member_login_method' => 'email',
    'member_remember_me' => '1',
    'member_login_attempts' => '5',
    'member_lockout_duration' => '30',
    'member_brute_force' => '1',
    'member_brute_force_attempts' => '10',
    'member_brute_force_seconds' => '300',
    'member_single_device' => '0',
    'member_login_redirect_url' => '',
    'member_logout_redirect_url' => '',

    // 디자인 설정
    'member_skin' => 'default',
    'member_social_login_enabled' => '0',
    'member_social_google' => '0',
    'member_social_line' => '0',
    'member_social_kakao' => '0',
];

// Merge defaults with database settings
$memberSettings = array_merge($defaultMemberSettings, $memberSettings);

// Flash message variables
$message = $message ?? '';
$messageType = $messageType ?? 'success';
