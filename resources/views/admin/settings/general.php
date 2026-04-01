<?php
/**
 * RezlyX Admin Settings - General (Admin Path)
 * 관리자 접속 경로 설정
 */

// Initialize database and settings
require_once __DIR__ . '/_init.php';

$pageTitle = __('settings.tabs.general') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
$currentSettingsPage = 'general';

// Check if redirected after path change
if (isset($_GET['changed']) && $_GET['changed'] === '1') {
    $message = __('settings.admin_path.changed');
    $messageType = 'success';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_timezone') {
        $newTimezone = trim($_POST['site_timezone'] ?? '');
        if (empty($newTimezone) || !in_array($newTimezone, timezone_identifiers_list())) {
            $message = __('settings.timezone.error_invalid');
            $messageType = 'error';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO rzx_settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
                $stmt->execute(['site_timezone', $newTimezone]);
                $settings['site_timezone'] = $newTimezone;
                date_default_timezone_set($newTimezone);
                $message = __('settings.timezone.saved');
                $messageType = 'success';
            } catch (PDOException $e) {
                $message = __('settings.error_save') . ': ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }

    if ($action === 'update_country_currency') {
        $siteCountry = trim($_POST['site_country'] ?? '');
        $serviceCurrency = trim($_POST['service_currency'] ?? 'KRW');
        try {
            $stmt = $pdo->prepare("INSERT INTO rzx_settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
            $stmt->execute(['site_country', $siteCountry]);
            $stmt->execute(['service_currency', $serviceCurrency]);
            $settings['site_country'] = $siteCountry;
            $settings['service_currency'] = $serviceCurrency;
            $message = __('settings.success');
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = __('settings.error_save') . ': ' . $e->getMessage();
            $messageType = 'error';
        }
    }

    if ($action === 'update_business_hours') {
        $bhStart = trim($_POST['business_hour_start'] ?? '09:00');
        $bhEnd   = trim($_POST['business_hour_end'] ?? '22:00');
        try {
            $stmt = $pdo->prepare("INSERT INTO rzx_settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
            $stmt->execute(['business_hour_start', $bhStart]);
            $stmt->execute(['business_hour_end', $bhEnd]);
            $settings['business_hour_start'] = $bhStart;
            $settings['business_hour_end'] = $bhEnd;
            $message = __('settings.success');
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = __('settings.error_save') . ': ' . $e->getMessage();
            $messageType = 'error';
        }
    }

    if ($action === 'update_payment') {
        $pgw = trim($_POST['payment_gateway'] ?? 'stripe');
        $pubKey = trim($_POST['payment_public_key'] ?? '');
        $secKey = trim($_POST['payment_secret_key'] ?? '');
        $testMode = isset($_POST['payment_test_mode']) ? '1' : '0';
        $enabled = isset($_POST['payment_enabled']) ? '1' : '0';
        try {
            $paymentConfig = json_encode([
                'gateway' => $pgw,
                'public_key' => $pubKey,
                'secret_key' => $secKey,
                'test_mode' => $testMode,
                'enabled' => $enabled,
            ], JSON_UNESCAPED_UNICODE);
            $stmt = $pdo->prepare("INSERT INTO rzx_settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
            $stmt->execute(['payment_config', $paymentConfig]);
            $settings['payment_config'] = $paymentConfig;
            $message = __('settings.success');
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = __('settings.error_save') . ': ' . $e->getMessage();
            $messageType = 'error';
        }
    }

    if ($action === 'update_admin_path') {
        $newAdminPath = trim($_POST['admin_path'] ?? '');

        if (empty($newAdminPath)) {
            $message = __('settings.admin_path.error_empty');
            $messageType = 'error';
        } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $newAdminPath)) {
            $message = __('settings.admin_path.error_invalid');
            $messageType = 'error';
        } elseif (in_array($newAdminPath, ['api', 'assets', 'storage', 'install', 'public'])) {
            $message = __('settings.admin_path.error_reserved');
            $messageType = 'error';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO rzx_settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
                $stmt->execute(['admin_path', $newAdminPath]);

                // Redirect to new admin path
                $newAdminUrl = ($config['app_url'] ?? '') . '/' . $newAdminPath . '/settings/general?changed=1';
                header('Location: ' . $newAdminUrl);
                exit;
            } catch (PDOException $e) {
                $message = __('settings.error_save') . ': ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

// Start output buffering for page content
ob_start();
?>

<!-- Sub Navigation Tabs (Link style) -->
<?php include __DIR__ . '/_settings_nav.php'; ?>

<!-- Admin Path Settings -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 mb-6 transition-colors">
    <?php
    $headerIcon = 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z';
    $headerTitle = __('settings.admin_path.title');
    $headerDescription = __('settings.admin_path.description') . '<br>' . __('settings.admin_path.current_url') . ': <code class="bg-zinc-100 dark:bg-zinc-700 px-2 py-1 rounded text-blue-600 dark:text-blue-400">' . htmlspecialchars($config['app_url'] ?? '') . '/' . htmlspecialchars($settings['admin_path'] ?? 'admin') . '/</code>';
    $headerIconColor = ''; $headerActions = '';
    include __DIR__ . '/../components/settings-header.php';
    ?>

    <form method="POST" class="space-y-4">
        <input type="hidden" name="action" value="update_admin_path">
        <div>
            <label for="admin_path" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('settings.admin_path.label') ?></label>
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
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1"><?= __('settings.admin_path.hint') ?></p>
        </div>
        <div class="flex items-center justify-between pt-4 border-t dark:border-zinc-700">
            <p class="text-sm text-amber-600">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <?= __('settings.admin_path.warning') ?>
            </p>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition">
                <?= __('settings.admin_path.button') ?>
            </button>
        </div>
    </form>
</div>

<!-- Timezone Settings -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 mb-6 transition-colors">
    <?php
    $headerIcon = 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z';
    $headerTitle = __('settings.timezone.title');
    $headerDescription = __('settings.timezone.description') . '<br>' . __('settings.timezone.current') . ': <code class="bg-zinc-100 dark:bg-zinc-700 px-2 py-1 rounded text-blue-600 dark:text-blue-400">' . htmlspecialchars(date_default_timezone_get()) . '</code> (' . date('Y-m-d H:i:s') . ')';
    $headerIconColor = ''; $headerActions = '';
    include __DIR__ . '/../components/settings-header.php';
    ?>

    <?php
    $commonTimezones = [
        'Asia/Seoul' => '(UTC+09:00) 서울',
        'Asia/Tokyo' => '(UTC+09:00) 도쿄',
        'Asia/Shanghai' => '(UTC+08:00) 상하이/베이징',
        'Asia/Taipei' => '(UTC+08:00) 타이베이',
        'Asia/Hong_Kong' => '(UTC+08:00) 홍콩',
        'Asia/Singapore' => '(UTC+08:00) 싱가포르',
        'Asia/Bangkok' => '(UTC+07:00) 방콕',
        'Asia/Ho_Chi_Minh' => '(UTC+07:00) 호치민',
        'Asia/Jakarta' => '(UTC+07:00) 자카르타',
        'Asia/Kolkata' => '(UTC+05:30) 콜카타',
        'Asia/Dubai' => '(UTC+04:00) 두바이',
        'Asia/Ulaanbaatar' => '(UTC+08:00) 울란바토르',
        'Europe/Moscow' => '(UTC+03:00) 모스크바',
        'Europe/Istanbul' => '(UTC+03:00) 이스탄불',
        'Europe/Berlin' => '(UTC+01:00) 베를린',
        'Europe/Paris' => '(UTC+01:00) 파리',
        'Europe/Madrid' => '(UTC+01:00) 마드리드',
        'Europe/London' => '(UTC+00:00) 런던',
        'America/New_York' => '(UTC-05:00) 뉴욕',
        'America/Chicago' => '(UTC-06:00) 시카고',
        'America/Denver' => '(UTC-07:00) 덴버',
        'America/Los_Angeles' => '(UTC-08:00) 로스앤젤레스',
        'Pacific/Honolulu' => '(UTC-10:00) 호놀룰루',
        'Pacific/Auckland' => '(UTC+12:00) 오클랜드',
        'Australia/Sydney' => '(UTC+10:00) 시드니',
    ];
    $currentTz = $settings['site_timezone'] ?? date_default_timezone_get();
    ?>

    <form method="POST" class="space-y-4">
        <input type="hidden" name="action" value="update_timezone">
        <div>
            <label for="site_timezone" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('settings.timezone.label') ?></label>
            <select name="site_timezone" id="site_timezone"
                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <?php foreach ($commonTimezones as $tz => $label): ?>
                <option value="<?= htmlspecialchars($tz) ?>" <?= $currentTz === $tz ? 'selected' : '' ?>><?= htmlspecialchars($tz) ?> <?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
                <optgroup label="<?= __('settings.timezone.all_timezones') ?>">
                    <?php foreach (timezone_identifiers_list() as $tz):
                        if (isset($commonTimezones[$tz])) continue;
                    ?>
                    <option value="<?= htmlspecialchars($tz) ?>" <?= $currentTz === $tz ? 'selected' : '' ?>><?= htmlspecialchars($tz) ?></option>
                    <?php endforeach; ?>
                </optgroup>
            </select>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1"><?= __('settings.timezone.hint') ?></p>
        </div>
        <div class="flex items-center justify-end pt-4 border-t dark:border-zinc-700">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition">
                <?= __('common.buttons.save') ?>
            </button>
        </div>
    </form>
</div>

<!-- Country & Currency Settings -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 mb-6 transition-colors">
    <?php
    $headerIcon = 'M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9';
    $headerTitle = __('settings.site.country.label');
    $headerDescription = __('settings.site.country.hint');
    $headerIconColor = ''; $headerActions = '';
    include __DIR__ . '/../components/settings-header.php';
    ?>

    <?php $countryValue = $settings['site_country'] ?? ''; ?>
    <?php $currencyValue = $settings['service_currency'] ?? 'KRW'; ?>

    <form method="POST" class="space-y-4">
        <input type="hidden" name="action" value="update_country_currency">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="site_country" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('settings.site.country.label') ?></label>
                <select name="site_country" id="site_country"
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="" <?= $countryValue === '' ? 'selected' : '' ?>>-- <?= __('settings.site.country.select') ?> --</option>
                    <option value="KR" <?= $countryValue === 'KR' ? 'selected' : '' ?>>🇰🇷 <?= __('settings.site.country.kr') ?></option>
                    <option value="JP" <?= $countryValue === 'JP' ? 'selected' : '' ?>>🇯🇵 <?= __('settings.site.country.jp') ?></option>
                    <option value="US" <?= $countryValue === 'US' ? 'selected' : '' ?>>🇺🇸 <?= __('settings.site.country.us') ?></option>
                    <option value="CN" <?= $countryValue === 'CN' ? 'selected' : '' ?>>🇨🇳 <?= __('settings.site.country.cn') ?></option>
                    <option value="TW" <?= $countryValue === 'TW' ? 'selected' : '' ?>>🇹🇼 <?= __('settings.site.country.tw') ?></option>
                    <option value="DE" <?= $countryValue === 'DE' ? 'selected' : '' ?>>🇩🇪 <?= __('settings.site.country.de') ?></option>
                    <option value="FR" <?= $countryValue === 'FR' ? 'selected' : '' ?>>🇫🇷 <?= __('settings.site.country.fr') ?></option>
                    <option value="ES" <?= $countryValue === 'ES' ? 'selected' : '' ?>>🇪🇸 <?= __('settings.site.country.es') ?></option>
                    <option value="GB" <?= $countryValue === 'GB' ? 'selected' : '' ?>>🇬🇧 <?= __('settings.site.country.gb') ?></option>
                    <option value="AU" <?= $countryValue === 'AU' ? 'selected' : '' ?>>🇦🇺 <?= __('settings.site.country.au') ?></option>
                    <option value="ID" <?= $countryValue === 'ID' ? 'selected' : '' ?>>🇮🇩 <?= __('settings.site.country.id') ?></option>
                    <option value="MN" <?= $countryValue === 'MN' ? 'selected' : '' ?>>🇲🇳 <?= __('settings.site.country.mn') ?></option>
                    <option value="RU" <?= $countryValue === 'RU' ? 'selected' : '' ?>>🇷🇺 <?= __('settings.site.country.ru') ?></option>
                    <option value="TR" <?= $countryValue === 'TR' ? 'selected' : '' ?>>🇹🇷 <?= __('settings.site.country.tr') ?></option>
                    <option value="VN" <?= $countryValue === 'VN' ? 'selected' : '' ?>>🇻🇳 <?= __('settings.site.country.vn') ?></option>
                    <option value="SG" <?= $countryValue === 'SG' ? 'selected' : '' ?>>🇸🇬 <?= __('settings.site.country.sg') ?></option>
                    <option value="NZ" <?= $countryValue === 'NZ' ? 'selected' : '' ?>>🇳🇿 <?= __('settings.site.country.nz') ?></option>
                </select>
            </div>
            <div>
                <label for="service_currency" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('settings.site.currency.label') ?></label>
                <select name="service_currency" id="service_currency"
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="KRW" <?= $currencyValue === 'KRW' ? 'selected' : '' ?>>KRW (₩)</option>
                    <option value="USD" <?= $currencyValue === 'USD' ? 'selected' : '' ?>>USD ($)</option>
                    <option value="JPY" <?= $currencyValue === 'JPY' ? 'selected' : '' ?>>JPY (¥)</option>
                    <option value="EUR" <?= $currencyValue === 'EUR' ? 'selected' : '' ?>>EUR (€)</option>
                    <option value="CNY" <?= $currencyValue === 'CNY' ? 'selected' : '' ?>>CNY (¥)</option>
                    <option value="GBP" <?= $currencyValue === 'GBP' ? 'selected' : '' ?>>GBP (£)</option>
                    <option value="AUD" <?= $currencyValue === 'AUD' ? 'selected' : '' ?>>AUD (A$)</option>
                    <option value="SGD" <?= $currencyValue === 'SGD' ? 'selected' : '' ?>>SGD (S$)</option>
                    <option value="NZD" <?= $currencyValue === 'NZD' ? 'selected' : '' ?>>NZD (NZ$)</option>
                    <option value="IDR" <?= $currencyValue === 'IDR' ? 'selected' : '' ?>>IDR (Rp)</option>
                    <option value="MNT" <?= $currencyValue === 'MNT' ? 'selected' : '' ?>>MNT (₮)</option>
                    <option value="RUB" <?= $currencyValue === 'RUB' ? 'selected' : '' ?>>RUB (₽)</option>
                    <option value="TRY" <?= $currencyValue === 'TRY' ? 'selected' : '' ?>>TRY (₺)</option>
                    <option value="VND" <?= $currencyValue === 'VND' ? 'selected' : '' ?>>VND (₫)</option>
                    <option value="TWD" <?= $currencyValue === 'TWD' ? 'selected' : '' ?>>TWD (NT$)</option>
                </select>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1"><?= __('settings.site.currency.hint') ?></p>
            </div>
        </div>
        <div class="flex items-center justify-end pt-4 border-t dark:border-zinc-700">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition">
                <?= __('common.buttons.save') ?>
            </button>
        </div>
    </form>
</div>

<!-- Business Hours Settings -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 mb-6 transition-colors">
    <?php
    $headerIcon = 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z';
    $headerTitle = __('settings.business_hours_title');
    $headerDescription = __('settings.business_hours_desc');
    $headerIconColor = ''; $headerActions = '';
    include __DIR__ . '/../components/settings-header.php';
    ?>

    <?php
    $bhStart = $settings['business_hour_start'] ?? '09:00';
    $bhEnd   = $settings['business_hour_end'] ?? '22:00';
    ?>

    <form method="POST" class="space-y-4">
        <input type="hidden" name="action" value="update_business_hours">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="business_hour_start" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('settings.business_hour_start') ?></label>
                <input type="time" name="business_hour_start" id="business_hour_start" value="<?= htmlspecialchars($bhStart) ?>"
                       class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label for="business_hour_end" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('settings.business_hour_end') ?></label>
                <input type="time" name="business_hour_end" id="business_hour_end" value="<?= htmlspecialchars($bhEnd) ?>"
                       class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
        <p class="text-xs text-zinc-500 dark:text-zinc-400"><?= __('settings.business_hours_hint') ?></p>
        <div class="flex items-center justify-end pt-4 border-t dark:border-zinc-700">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition">
                <?= __('common.buttons.save') ?>
            </button>
        </div>
    </form>
</div>

<?php
// 결제 설정 로드
$_payConf = json_decode($settings['payment_config'] ?? '{}', true) ?: [];
$_payGateway = $_payConf['gateway'] ?? 'stripe';
$_payPubKey = $_payConf['public_key'] ?? '';
$_paySecKey = $_payConf['secret_key'] ?? '';
$_payTestMode = ($_payConf['test_mode'] ?? '1') === '1';
$_payEnabled = ($_payConf['enabled'] ?? '0') === '1';
$_payMaskedSec = $_paySecKey ? str_repeat('•', 12) . substr($_paySecKey, -8) : '';
?>

<!-- 온라인 결제 설정 -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6 mt-6">
    <div class="flex items-center gap-3 mb-2">
        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100"><?= __('settings.payment_config.title') ?? '온라인 결제 설정' ?></h3>
    </div>
    <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4"><?= __('settings.payment_config.description') ?? '온라인 결제를 위한 PG사 API 키를 설정합니다.' ?></p>

    <form method="POST" class="space-y-4" id="paymentSettingsForm">
        <input type="hidden" name="action" value="update_payment">

        <!-- 결제 활성화 -->
        <div class="flex items-center justify-between py-3 border-b dark:border-zinc-700">
            <div>
                <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('settings.payment_config.enabled') ?? '온라인 결제 활성화' ?></p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400"><?= __('settings.payment_config.enabled_hint') ?? '활성화하면 고객이 예약 시 온라인 결제를 할 수 있습니다.' ?></p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="payment_enabled" value="1" <?= $_payEnabled ? 'checked' : '' ?> class="sr-only peer">
                <div class="w-11 h-6 bg-zinc-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
            </label>
        </div>

        <!-- PG사 선택 -->
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('settings.payment_config.gateway') ?? 'PG사 선택' ?></label>
            <select name="payment_gateway" id="payment_gateway"
                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                <option value="stripe" <?= $_payGateway === 'stripe' ? 'selected' : '' ?>>Stripe (<?= __('settings.payment_config.region_global') ?? 'Global' ?>)</option>
                <option value="toss" <?= $_payGateway === 'toss' ? 'selected' : '' ?>>Toss Payments (<?= __('settings.payment_config.region_kr') ?? '한국' ?>)</option>
                <option value="payjp" <?= $_payGateway === 'payjp' ? 'selected' : '' ?>>PAY.JP (<?= __('settings.payment_config.region_jp') ?? '日本' ?>)</option>
                <option value="portone" <?= $_payGateway === 'portone' ? 'selected' : '' ?>>PortOne (<?= __('settings.payment_config.region_kr') ?? '한국' ?>)</option>
            </select>
        </div>

        <!-- 테스트 모드 -->
        <div class="flex items-center justify-between py-3 border-b dark:border-zinc-700">
            <div>
                <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('settings.payment_config.test_mode') ?? '테스트 모드' ?></p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400"><?= __('settings.payment_config.test_mode_hint') ?? '테스트 모드에서는 실제 결제가 이루어지지 않습니다.' ?></p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="payment_test_mode" value="1" <?= $_payTestMode ? 'checked' : '' ?> class="sr-only peer">
                <div class="w-11 h-6 bg-zinc-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-500"></div>
            </label>
        </div>

        <!-- API 키 -->
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                <span id="pubKeyLabel">Publishable Key</span>
            </label>
            <input type="text" name="payment_public_key" value="<?= htmlspecialchars($_payPubKey) ?>"
                   placeholder="pk_test_..."
                   class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 font-mono text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                <span id="secKeyLabel">Secret Key</span>
            </label>
            <div class="relative">
                <input type="password" name="payment_secret_key" id="paySecKeyInput"
                       value="<?= htmlspecialchars($_paySecKey) ?>"
                       placeholder="sk_test_..."
                       class="w-full px-3 py-2 pr-10 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 font-mono text-sm">
                <button type="button" onclick="var i=document.getElementById('paySecKeyInput');i.type=i.type==='password'?'text':'password';console.log('[Payment] Toggle key visibility');"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                </button>
            </div>
            <?php if ($_paySecKey): ?>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1"><?= __('settings.payment_config.key_saved') ?? '키가 저장되어 있습니다.' ?> (<?= $_payMaskedSec ?>)</p>
            <?php endif; ?>
        </div>

        <!-- 상태 표시 -->
        <?php if ($_payEnabled && $_payPubKey && $_paySecKey): ?>
        <div class="flex items-center gap-2 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span class="text-sm text-green-700 dark:text-green-300"><?= __('settings.payment_config.status_ready') ?? '결제 시스템이 준비되었습니다.' ?> (<?= $_payTestMode ? __('settings.payment_config.test_mode') ?? '테스트 모드' : __('settings.payment_config.live_mode') ?? '라이브 모드' ?>)</span>
        </div>
        <?php elseif ($_payEnabled): ?>
        <div class="flex items-center gap-2 p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
            <span class="text-sm text-amber-700 dark:text-amber-300"><?= __('settings.payment_config.status_incomplete') ?? 'API 키를 입력해주세요.' ?></span>
        </div>
        <?php endif; ?>

        <div class="flex items-center justify-end pt-4 border-t dark:border-zinc-700">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition">
                <?= __('common.buttons.save') ?>
            </button>
        </div>
    </form>

    <!-- PG사 안내 -->
    <div class="mt-6 pt-4 border-t dark:border-zinc-700">
        <h4 class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-3"><?= __('settings.payment_config.gateway_info') ?? '결제 대행사 안내' ?></h4>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <a href="https://stripe.com" target="_blank" class="flex items-center gap-3 p-3 rounded-lg border border-zinc-200 dark:border-zinc-700 hover:border-blue-300 dark:hover:border-blue-600 transition group">
                <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center shrink-0">
                    <span class="text-indigo-600 dark:text-indigo-400 font-bold text-sm">S</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200 group-hover:text-blue-600">Stripe</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 truncate"><?= __('settings.payment_config.stripe_desc') ?? '글로벌 결제 — 46개국, 135개 통화 지원' ?></p>
                </div>
                <svg class="w-4 h-4 text-zinc-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            </a>
            <a href="https://www.tosspayments.com" target="_blank" class="flex items-center gap-3 p-3 rounded-lg border border-zinc-200 dark:border-zinc-700 hover:border-blue-300 dark:hover:border-blue-600 transition group">
                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center shrink-0">
                    <span class="text-blue-600 dark:text-blue-400 font-bold text-sm">T</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200 group-hover:text-blue-600">Toss Payments</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 truncate"><?= __('settings.payment_config.toss_desc') ?? '한국 결제 — 카드, 계좌이체, 간편결제' ?></p>
                </div>
                <svg class="w-4 h-4 text-zinc-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            </a>
            <a href="https://pay.jp" target="_blank" class="flex items-center gap-3 p-3 rounded-lg border border-zinc-200 dark:border-zinc-700 hover:border-blue-300 dark:hover:border-blue-600 transition group">
                <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center shrink-0">
                    <span class="text-green-600 dark:text-green-400 font-bold text-sm">P</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200 group-hover:text-blue-600">PAY.JP</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 truncate"><?= __('settings.payment_config.payjp_desc') ?? '일본 결제 — 카드, 콘비니 결제' ?></p>
                </div>
                <svg class="w-4 h-4 text-zinc-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            </a>
            <a href="https://portone.io" target="_blank" class="flex items-center gap-3 p-3 rounded-lg border border-zinc-200 dark:border-zinc-700 hover:border-blue-300 dark:hover:border-blue-600 transition group">
                <div class="w-10 h-10 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center shrink-0">
                    <span class="text-orange-600 dark:text-orange-400 font-bold text-sm">P</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200 group-hover:text-blue-600">PortOne</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 truncate"><?= __('settings.payment_config.portone_desc') ?? '한국 통합결제 — 다양한 PG사 연동' ?></p>
                </div>
                <svg class="w-4 h-4 text-zinc-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            </a>
        </div>
    </div>
</div>

<script>
console.log('[Payment Settings] Initialized');
document.getElementById('payment_gateway').addEventListener('change', function() {
    var gw = this.value;
    var labels = {
        stripe: ['Publishable Key', 'Secret Key', 'pk_test_...', 'sk_test_...'],
        toss: ['클라이언트 키', '시크릿 키', 'test_ck_...', 'test_sk_...'],
        payjp: ['公開鍵', '秘密鍵', 'pk_test_...', 'sk_test_...'],
        portone: ['가맹점 식별코드', 'API Secret', 'imp_...', 'secret_...']
    };
    var l = labels[gw] || labels.stripe;
    document.getElementById('pubKeyLabel').textContent = l[0];
    document.getElementById('secKeyLabel').textContent = l[1];
    document.querySelector('[name=payment_public_key]').placeholder = l[2];
    document.getElementById('paySecKeyInput').placeholder = l[3];
    console.log('[Payment Settings] Gateway changed to:', gw);
});
</script>

<?php
$pageContent = ob_get_clean();

// Render layout with content
include __DIR__ . '/_layout.php';
