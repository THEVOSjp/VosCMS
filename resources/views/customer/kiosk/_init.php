<?php
/**
 * 키오스크 공통 초기화
 * 변수: $ks, $kioskTheme, $kioskBgType, $kioskBgImage, $kioskBgVideo, $kioskBgOverlay,
 *       $kioskIdleTimeout, $kioskLogoOverride, $kioskLogoSrc, $siteName,
 *       $isLight, $textColor, $subTextColor, $footerColor, $btnBg, $btnText, $adminUrl
 */

require_once BASE_PATH . '/rzxlib/Core/Modules/LanguageModule.php';
use RzxLib\Core\Modules\LanguageModule;

if (!function_exists('__')) {
    require_once BASE_PATH . '/rzxlib/Core/Helpers/lang.php';
}

$currentLocale = function_exists('current_locale') ? current_locale() : ($config['locale'] ?? 'ko');
$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$siteName = $siteSettings['site_name'] ?? ($config['app_name'] ?? 'RezlyX');
$logoType = $siteSettings['logo_type'] ?? 'text';
$logoImage = $siteSettings['logo_image'] ?? '';

// ─── 키오스크 설정 로드 ───
$ks = [];
$stmt = $pdo->prepare("SELECT `key`, `value` FROM {$prefix}settings WHERE `key` LIKE 'kiosk_%'");
$stmt->execute();
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $ks[$row['key']] = $row['value'];
}

$kioskTheme       = $ks['kiosk_theme'] ?? 'dark';
$kioskBgType      = $ks['kiosk_bg_type'] ?? 'gradient';
$kioskBgImage     = $ks['kiosk_bg_image'] ?? '';
$kioskBgVideo     = $ks['kiosk_bg_video'] ?? '';
$kioskBgOverlay   = (int)($ks['kiosk_bg_overlay'] ?? 50);
$kioskIdleTimeout = (int)($ks['kiosk_idle_timeout'] ?? 60);
$kioskLogoOverride = $ks['kiosk_logo_override'] ?? '';

// 로고: 키오스크 전용 로고 > 사이트 로고
$kioskLogoSrc = '';
if ($kioskLogoOverride) {
    $kioskLogoSrc = $kioskLogoOverride;
} elseif ($logoType === 'image' && $logoImage) {
    $kioskLogoSrc = $baseUrl . '/storage/' . $logoImage;
}

// 테마별 색상
$isLight    = ($kioskTheme === 'light');
$textColor  = $isLight ? 'text-zinc-900' : 'text-white';
$subTextColor = $isLight ? 'text-zinc-500' : 'text-zinc-400';
$footerColor  = $isLight ? 'text-zinc-400' : 'text-zinc-500';
$btnBg   = $isLight ? 'bg-black/5 border-black/10 hover:bg-black/10 hover:border-black/20' : 'bg-white/10 border-white/20 hover:bg-white/20 hover:border-white/40';
$btnText = $isLight ? 'text-zinc-900' : 'text-white';

// 다국어 번역 헬퍼: rzx_translations에서 현재 로케일 값 조회
function kioskTranslation(PDO $pdo, string $prefix, string $key, string $locale): string {
    $stmt = $pdo->prepare("SELECT content FROM {$prefix}translations WHERE lang_key = ? AND locale = ? LIMIT 1");
    $stmt->execute([$key, $locale]);
    return $stmt->fetchColumn() ?: '';
}
