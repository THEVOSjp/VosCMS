<?php
/**
 * RezlyX Admin Settings - General (Admin Path)
 * 관리자 접속 경로 설정
 */

// Initialize database and settings
require_once __DIR__ . '/_init.php';

$pageTitle = __('admin.settings.tabs.general') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
$currentSettingsPage = 'general';

// Check if redirected after path change
if (isset($_GET['changed']) && $_GET['changed'] === '1') {
    $message = __('admin.settings.admin_path.changed');
    $messageType = 'success';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_timezone') {
        $newTimezone = trim($_POST['site_timezone'] ?? '');
        if (empty($newTimezone) || !in_array($newTimezone, timezone_identifiers_list())) {
            $message = __('admin.settings.timezone.error_invalid');
            $messageType = 'error';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO rzx_settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
                $stmt->execute(['site_timezone', $newTimezone]);
                $settings['site_timezone'] = $newTimezone;
                date_default_timezone_set($newTimezone);
                $message = __('admin.settings.timezone.saved');
                $messageType = 'success';
            } catch (PDOException $e) {
                $message = __('admin.settings.error_save') . ': ' . $e->getMessage();
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
            $message = __('admin.settings.success');
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = __('admin.settings.error_save') . ': ' . $e->getMessage();
            $messageType = 'error';
        }
    }

    if ($action === 'update_admin_path') {
        $newAdminPath = trim($_POST['admin_path'] ?? '');

        if (empty($newAdminPath)) {
            $message = __('admin.settings.admin_path.error_empty');
            $messageType = 'error';
        } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $newAdminPath)) {
            $message = __('admin.settings.admin_path.error_invalid');
            $messageType = 'error';
        } elseif (in_array($newAdminPath, ['api', 'assets', 'storage', 'install', 'public'])) {
            $message = __('admin.settings.admin_path.error_reserved');
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
                $message = __('admin.settings.error_save') . ': ' . $e->getMessage();
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
    $headerTitle = __('admin.settings.admin_path.title');
    $headerDescription = __('admin.settings.admin_path.description') . '<br>' . __('admin.settings.admin_path.current_url') . ': <code class="bg-zinc-100 dark:bg-zinc-700 px-2 py-1 rounded text-blue-600 dark:text-blue-400">' . htmlspecialchars($config['app_url'] ?? '') . '/' . htmlspecialchars($settings['admin_path'] ?? 'admin') . '/</code>';
    $headerIconColor = ''; $headerActions = '';
    include __DIR__ . '/../components/settings-header.php';
    ?>

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

<!-- Timezone Settings -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 mb-6 transition-colors">
    <?php
    $headerIcon = 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z';
    $headerTitle = __('admin.settings.timezone.title');
    $headerDescription = __('admin.settings.timezone.description') . '<br>' . __('admin.settings.timezone.current') . ': <code class="bg-zinc-100 dark:bg-zinc-700 px-2 py-1 rounded text-blue-600 dark:text-blue-400">' . htmlspecialchars(date_default_timezone_get()) . '</code> (' . date('Y-m-d H:i:s') . ')';
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
            <label for="site_timezone" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.settings.timezone.label') ?></label>
            <select name="site_timezone" id="site_timezone"
                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <?php foreach ($commonTimezones as $tz => $label): ?>
                <option value="<?= htmlspecialchars($tz) ?>" <?= $currentTz === $tz ? 'selected' : '' ?>><?= htmlspecialchars($tz) ?> <?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
                <optgroup label="<?= __('admin.settings.timezone.all_timezones') ?>">
                    <?php foreach (timezone_identifiers_list() as $tz):
                        if (isset($commonTimezones[$tz])) continue;
                    ?>
                    <option value="<?= htmlspecialchars($tz) ?>" <?= $currentTz === $tz ? 'selected' : '' ?>><?= htmlspecialchars($tz) ?></option>
                    <?php endforeach; ?>
                </optgroup>
            </select>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1"><?= __('admin.settings.timezone.hint') ?></p>
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
    $headerTitle = __('admin.settings.site.country.label');
    $headerDescription = __('admin.settings.site.country.hint');
    $headerIconColor = ''; $headerActions = '';
    include __DIR__ . '/../components/settings-header.php';
    ?>

    <?php $countryValue = $settings['site_country'] ?? ''; ?>
    <?php $currencyValue = $settings['service_currency'] ?? 'KRW'; ?>

    <form method="POST" class="space-y-4">
        <input type="hidden" name="action" value="update_country_currency">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="site_country" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.settings.site.country.label') ?></label>
                <select name="site_country" id="site_country"
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="" <?= $countryValue === '' ? 'selected' : '' ?>>-- <?= __('admin.settings.site.country.select') ?> --</option>
                    <option value="KR" <?= $countryValue === 'KR' ? 'selected' : '' ?>>🇰🇷 <?= __('admin.settings.site.country.kr') ?></option>
                    <option value="JP" <?= $countryValue === 'JP' ? 'selected' : '' ?>>🇯🇵 <?= __('admin.settings.site.country.jp') ?></option>
                    <option value="US" <?= $countryValue === 'US' ? 'selected' : '' ?>>🇺🇸 <?= __('admin.settings.site.country.us') ?></option>
                    <option value="CN" <?= $countryValue === 'CN' ? 'selected' : '' ?>>🇨🇳 <?= __('admin.settings.site.country.cn') ?></option>
                    <option value="TW" <?= $countryValue === 'TW' ? 'selected' : '' ?>>🇹🇼 <?= __('admin.settings.site.country.tw') ?></option>
                    <option value="DE" <?= $countryValue === 'DE' ? 'selected' : '' ?>>🇩🇪 <?= __('admin.settings.site.country.de') ?></option>
                    <option value="FR" <?= $countryValue === 'FR' ? 'selected' : '' ?>>🇫🇷 <?= __('admin.settings.site.country.fr') ?></option>
                    <option value="ES" <?= $countryValue === 'ES' ? 'selected' : '' ?>>🇪🇸 <?= __('admin.settings.site.country.es') ?></option>
                    <option value="GB" <?= $countryValue === 'GB' ? 'selected' : '' ?>>🇬🇧 <?= __('admin.settings.site.country.gb') ?></option>
                    <option value="AU" <?= $countryValue === 'AU' ? 'selected' : '' ?>>🇦🇺 <?= __('admin.settings.site.country.au') ?></option>
                    <option value="ID" <?= $countryValue === 'ID' ? 'selected' : '' ?>>🇮🇩 <?= __('admin.settings.site.country.id') ?></option>
                    <option value="MN" <?= $countryValue === 'MN' ? 'selected' : '' ?>>🇲🇳 <?= __('admin.settings.site.country.mn') ?></option>
                    <option value="RU" <?= $countryValue === 'RU' ? 'selected' : '' ?>>🇷🇺 <?= __('admin.settings.site.country.ru') ?></option>
                    <option value="TR" <?= $countryValue === 'TR' ? 'selected' : '' ?>>🇹🇷 <?= __('admin.settings.site.country.tr') ?></option>
                    <option value="VN" <?= $countryValue === 'VN' ? 'selected' : '' ?>>🇻🇳 <?= __('admin.settings.site.country.vn') ?></option>
                    <option value="SG" <?= $countryValue === 'SG' ? 'selected' : '' ?>>🇸🇬 <?= __('admin.settings.site.country.sg') ?></option>
                    <option value="NZ" <?= $countryValue === 'NZ' ? 'selected' : '' ?>>🇳🇿 <?= __('admin.settings.site.country.nz') ?></option>
                </select>
            </div>
            <div>
                <label for="service_currency" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.settings.site.currency.label') ?></label>
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
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1"><?= __('admin.settings.site.currency.hint') ?></p>
            </div>
        </div>
        <div class="flex items-center justify-end pt-4 border-t dark:border-zinc-700">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition">
                <?= __('common.buttons.save') ?>
            </button>
        </div>
    </form>
</div>

<?php
$pageContent = ob_get_clean();

// Render layout with content
include __DIR__ . '/_layout.php';
