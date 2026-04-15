<?php
/**
 * License Manager - Admin Head
 */
$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');
$locale = $config['locale'] ?? ($_SESSION['locale'] ?? 'ko');

// 다국어 로드
$_lmLocale = $locale;
$_lmLangFile = __DIR__ . '/../../lang/' . $_lmLocale . '.php';
if (!file_exists($_lmLangFile)) $_lmLangFile = __DIR__ . '/../../lang/en.php';
$_lmLang = file_exists($_lmLangFile) ? require $_lmLangFile : [];

if (!function_exists('__lm')) {
    function __lm(string $key, string $default = ''): string {
        global $_lmLang;
        return $_lmLang[$key] ?? $default ?: $key;
    }
}

$pageTitle = __lm('title') . ' - ' . ($config['app_name'] ?? 'VosCMS') . ' Admin';
$pageHeaderTitle = __lm('title');

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
try {
    $_lmPdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    die('<div class="p-4 bg-red-50 text-red-600 rounded-lg">DB Error</div>');
}
?>
<?php include BASE_PATH . '/resources/views/admin/reservations/_head.php'; ?>
