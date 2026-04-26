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

    if ($action === 'update_tax') {
        try {
            $taxConfig = json_encode([
                'tax_display' => $_POST['tax_display'] ?? 'exclusive',
                'tax_rate' => $_POST['tax_rate'] ?? '10',
                'tax_rounding' => $_POST['tax_rounding'] ?? 'round',
                'withholding' => $_POST['withholding'] ?? 'none',
            ], JSON_UNESCAPED_UNICODE);
            $stmt = $pdo->prepare("INSERT INTO rzx_settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
            $stmt->execute(['tax_config', $taxConfig]);
            $settings['tax_config'] = $taxConfig;
            $message = __('settings.success') ?? '저장되었습니다.';
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
            // 기존 설정 로드 → PG사별 키 보존
            $existConf = json_decode($settings['payment_config'] ?? '{}', true) ?: [];
            $gateways = $existConf['gateways'] ?? [];
            // v1 → v2 마이그레이션 (기존 단일 키 구조 → PG사별 구조)
            if (!empty($existConf['public_key']) && empty($gateways)) {
                $oldGw = $existConf['gateway'] ?? 'stripe';
                $gateways[$oldGw] = [
                    'public_key' => $existConf['public_key'] ?? '',
                    'secret_key' => $existConf['secret_key'] ?? '',
                    'test_mode' => $existConf['test_mode'] ?? '1',
                ];
            }
            $webhookToken = trim($_POST['payment_webhook_token'] ?? '');
            // 현재 선택한 PG사의 키 저장
            $existingWebhook = $gateways[$pgw]['webhook_token'] ?? '';
            if ($pubKey || $secKey) {
                $gateways[$pgw] = [
                    'public_key' => $pubKey,
                    'secret_key' => $secKey,
                    'test_mode' => $testMode,
                    'webhook_token' => $webhookToken ?: $existingWebhook,
                ];
            } elseif (isset($gateways[$pgw])) {
                // 키 입력 안 했으면 test_mode, webhook_token만 업데이트
                $gateways[$pgw]['test_mode'] = $testMode;
                if ($webhookToken) $gateways[$pgw]['webhook_token'] = $webhookToken;
            }
            $paymentConfig = json_encode([
                'gateway' => $pgw,
                'enabled' => $enabled,
                'gateways' => $gateways,
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

    if ($action === 'update_marketplace_cache') {
        $newTtl = (int)($_POST['cache_ttl'] ?? 300);
        if (!in_array($newTtl, [60, 300, 600, 1800, 3600], true)) $newTtl = 300;
        $pm = \RzxLib\Core\Plugin\PluginManager::getInstance();
        if ($pm) {
            $pm->setSetting('vos-autoinstall', 'cache_ttl', (string)$newTtl);
            // 기존 캐시 즉시 무효화
            foreach (glob(BASE_PATH . '/storage/cache/mp_api_*.json') ?: [] as $f) { @unlink($f); }
            $message = __('settings.marketplace_cache.saved');
            $messageType = 'success';
        }
    }

    if ($action === 'refresh_marketplace_cache') {
        $count = 0;
        foreach (glob(BASE_PATH . '/storage/cache/mp_api_*.json') ?: [] as $f) {
            if (@unlink($f)) $count++;
        }
        $message = sprintf(__('settings.marketplace_cache.refreshed'), $count);
        $messageType = 'success';
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

<!-- Marketplace Cache Settings -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 mb-6 transition-colors">
    <?php
    $headerIcon = 'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4';
    $headerTitle = __('settings.marketplace_cache.title');
    $headerDescription = __('settings.marketplace_cache.description');
    $headerIconColor = ''; $headerActions = '';
    include __DIR__ . '/../components/settings-header.php';

    $_pmCache = \RzxLib\Core\Plugin\PluginManager::getInstance();
    $_currentTtl = $_pmCache ? (string)$_pmCache->getSetting('vos-autoinstall', 'cache_ttl', '300') : '300';
    $_cacheCount = count(glob(BASE_PATH . '/storage/cache/mp_api_*.json') ?: []);
    ?>

    <form method="POST" class="space-y-4">
        <input type="hidden" name="action" value="update_marketplace_cache">
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('settings.marketplace_cache.ttl_label') ?></label>
            <select name="cache_ttl"
                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="60"   <?= $_currentTtl === '60'   ? 'selected' : '' ?>><?= __('settings.marketplace_cache.ttl_60') ?></option>
                <option value="300"  <?= $_currentTtl === '300'  ? 'selected' : '' ?>><?= __('settings.marketplace_cache.ttl_300') ?></option>
                <option value="600"  <?= $_currentTtl === '600'  ? 'selected' : '' ?>><?= __('settings.marketplace_cache.ttl_600') ?></option>
                <option value="1800" <?= $_currentTtl === '1800' ? 'selected' : '' ?>><?= __('settings.marketplace_cache.ttl_1800') ?></option>
                <option value="3600" <?= $_currentTtl === '3600' ? 'selected' : '' ?>><?= __('settings.marketplace_cache.ttl_3600') ?></option>
            </select>
        </div>
        <div class="flex items-center justify-end pt-4 border-t dark:border-zinc-700">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition">
                <?= __('settings.marketplace_cache.save') ?>
            </button>
        </div>
    </form>

    <!-- 즉시 캐시 갱신 -->
    <form method="POST" class="mt-4 pt-4 border-t dark:border-zinc-700 flex items-center justify-between gap-4">
        <input type="hidden" name="action" value="refresh_marketplace_cache">
        <p class="text-xs text-zinc-500 dark:text-zinc-400 flex-1">
            <?= __('settings.marketplace_cache.refresh_desc') ?>
            <span class="ml-1 text-zinc-400">(<?= $_cacheCount ?> files)</span>
        </p>
        <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-700 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-600 transition-colors whitespace-nowrap">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            <?= __('settings.marketplace_cache.refresh_now') ?>
        </button>
    </form>
</div>

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

<!-- 과세 설정 -->
<?php
$_taxConf = json_decode($settings['tax_config'] ?? '{}', true) ?: [];
$_taxDisplay = $_taxConf['tax_display'] ?? 'exclusive';
$_taxRate = $_taxConf['tax_rate'] ?? '10';
$_taxRounding = $_taxConf['tax_rounding'] ?? 'round';
$_withholding = $_taxConf['withholding'] ?? 'none';
?>
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 mb-6 transition-colors">
    <?php
    $headerIcon = 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z';
    $headerTitle = __('settings.tax.title') ?? '과세 설정';
    $headerDescription = __('settings.tax.desc') ?? '소비세 및 원천징수세 설정을 관리합니다.';
    $headerIconColor = ''; $headerActions = '';
    include __DIR__ . '/../components/settings-header.php';
    ?>

    <form method="POST" class="space-y-6">
        <input type="hidden" name="action" value="update_tax">

        <!-- 소비세 설정 -->
        <div>
            <h4 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200 mb-3"><?= __('settings.tax.display_title') ?? '소비세 설정' ?></h4>
            <div class="space-y-2">
                <label class="flex items-center gap-2 cursor-pointer"><input type="radio" name="tax_display" value="exclusive" class="text-blue-600" <?= $_taxDisplay === 'exclusive' ? 'checked' : '' ?>><span class="text-sm text-zinc-700 dark:text-zinc-300"><?= __('settings.tax.exclusive') ?? '세금 별도 표시' ?></span></label>
                <label class="flex items-center gap-2 cursor-pointer"><input type="radio" name="tax_display" value="exclusive_invoice" class="text-blue-600" <?= $_taxDisplay === 'exclusive_invoice' ? 'checked' : '' ?>><span class="text-sm text-zinc-700 dark:text-zinc-300"><?= __('settings.tax.exclusive_invoice') ?? '세금 별도 표시 (납품서만 청구시에 계산)' ?></span></label>
                <label class="flex items-center gap-2 cursor-pointer"><input type="radio" name="tax_display" value="inclusive" class="text-blue-600" <?= $_taxDisplay === 'inclusive' ? 'checked' : '' ?>><span class="text-sm text-zinc-700 dark:text-zinc-300"><?= __('settings.tax.inclusive') ?? '부가세 포함 표시' ?></span></label>
                <label class="flex items-center gap-2 cursor-pointer"><input type="radio" name="tax_display" value="exempt" class="text-blue-600" <?= $_taxDisplay === 'exempt' ? 'checked' : '' ?>><span class="text-sm text-zinc-700 dark:text-zinc-300"><?= __('settings.tax.exempt') ?? '부가세 포함 표시 (면세)' ?></span></label>
            </div>
        </div>

        <!-- 소비세율 설정 -->
        <div>
            <h4 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200 mb-3"><?= __('settings.tax.rate_title') ?? '소비세율 설정' ?></h4>
            <div class="space-y-2">
                <label class="flex items-center gap-2 cursor-pointer"><input type="radio" name="tax_rate" value="10" class="text-blue-600" <?= $_taxRate === '10' ? 'checked' : '' ?>><span class="text-sm text-zinc-700 dark:text-zinc-300">10%</span></label>
                <label class="flex items-center gap-2 cursor-pointer"><input type="radio" name="tax_rate" value="8r" class="text-blue-600" <?= $_taxRate === '8r' ? 'checked' : '' ?>><span class="text-sm text-zinc-700 dark:text-zinc-300"><?= __('settings.tax.rate_reduced') ?? '경감 8%' ?></span></label>
                <label class="flex items-center gap-2 cursor-pointer"><input type="radio" name="tax_rate" value="8" class="text-blue-600" <?= $_taxRate === '8' ? 'checked' : '' ?>><span class="text-sm text-zinc-700 dark:text-zinc-300">8%</span></label>
                <label class="flex items-center gap-2 cursor-pointer"><input type="radio" name="tax_rate" value="5" class="text-blue-600" <?= $_taxRate === '5' ? 'checked' : '' ?>><span class="text-sm text-zinc-700 dark:text-zinc-300">5%</span></label>
            </div>
        </div>

        <!-- 소비세 단수 처리 -->
        <div>
            <h4 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200 mb-2"><?= __('settings.tax.rounding_title') ?? '소비세 단수 처리' ?></h4>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-3"><?= __('settings.tax.rounding_desc') ?? '소계에 걸리는 소비세의 소수점 이하의 처리 방법을 선택할 수 있습니다.' ?></p>
            <div class="space-y-2">
                <label class="flex items-center gap-2 cursor-pointer"><input type="radio" name="tax_rounding" value="floor" class="text-blue-600" <?= $_taxRounding === 'floor' ? 'checked' : '' ?>><span class="text-sm text-zinc-700 dark:text-zinc-300"><?= __('settings.tax.floor') ?? '잘라내다 (切り捨て)' ?></span></label>
                <label class="flex items-center gap-2 cursor-pointer"><input type="radio" name="tax_rounding" value="round" class="text-blue-600" <?= $_taxRounding === 'round' ? 'checked' : '' ?>><span class="text-sm text-zinc-700 dark:text-zinc-300"><?= __('settings.tax.round') ?? '반올림 (四捨五入)' ?></span></label>
                <label class="flex items-center gap-2 cursor-pointer"><input type="radio" name="tax_rounding" value="ceil" class="text-blue-600" <?= $_taxRounding === 'ceil' ? 'checked' : '' ?>><span class="text-sm text-zinc-700 dark:text-zinc-300"><?= __('settings.tax.ceil') ?? '올림 (切り上げ)' ?></span></label>
            </div>
        </div>

        <!-- 원천징수세 설정 -->
        <div>
            <h4 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200 mb-2"><?= __('settings.tax.withholding_title') ?? '원천징수세 설정' ?></h4>
            <?php $_whCountry = strtolower($settings['site_country'] ?? 'jp'); ?>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-3"><a href="<?= rtrim($config['app_url'] ?? '', '/') ?>/withholding-tax-<?= $_whCountry ?>" target="_blank" class="text-blue-500 hover:underline"><?= __('settings.tax.withholding_link') ?? '원천징수세의 계산방법에 대해서' ?> ↗</a></p>
            <div class="space-y-2">
                <label class="flex items-center gap-2 cursor-pointer"><input type="radio" name="withholding" value="none" class="text-blue-600" <?= $_withholding === 'none' ? 'checked' : '' ?>><span class="text-sm text-zinc-700 dark:text-zinc-300"><?= __('settings.tax.withholding_none') ?? '없음' ?></span></label>
                <label class="flex items-center gap-2 cursor-pointer"><input type="radio" name="withholding" value="with_reconstruction" class="text-blue-600" <?= $_withholding === 'with_reconstruction' ? 'checked' : '' ?>><span class="text-sm text-zinc-700 dark:text-zinc-300"><?= __('settings.tax.withholding_with') ?? '있음 (부흥세 있음)' ?></span></label>
                <label class="flex items-center gap-2 cursor-pointer"><input type="radio" name="withholding" value="without_reconstruction" class="text-blue-600" <?= $_withholding === 'without_reconstruction' ? 'checked' : '' ?>><span class="text-sm text-zinc-700 dark:text-zinc-300"><?= __('settings.tax.withholding_without') ?? '있음 (부흥세 없음)' ?></span></label>
            </div>
        </div>

        <div class="flex items-center justify-end pt-4 border-t dark:border-zinc-700">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition">
                <?= __('common.buttons.save') ?? '저장' ?>
            </button>
        </div>
    </form>
</div>

<?php
// 결제 설정 로드 (PG사별 키 구조)
$_payConf = json_decode($settings['payment_config'] ?? '{}', true) ?: [];
$_payGateway = $_payConf['gateway'] ?? 'stripe';
$_payEnabled = ($_payConf['enabled'] ?? '0') === '1';
$_payGateways = $_payConf['gateways'] ?? [];
// v1 호환: 기존 단일 키 구조
if (empty($_payGateways) && !empty($_payConf['public_key'])) {
    $_payGateways[$_payGateway] = [
        'public_key' => $_payConf['public_key'] ?? '',
        'secret_key' => $_payConf['secret_key'] ?? '',
        'test_mode' => $_payConf['test_mode'] ?? '1',
    ];
}
$_payCurrentGw = $_payGateways[$_payGateway] ?? [];
$_payPubKey = $_payCurrentGw['public_key'] ?? '';
$_paySecKey = $_payCurrentGw['secret_key'] ?? '';
$_payTestMode = ($_payCurrentGw['test_mode'] ?? '1') === '1';
$_payMaskedSec = $_paySecKey ? str_repeat('•', 12) . substr($_paySecKey, -8) : '';
// JS용 PG사별 데이터
$_payGatewaysJson = json_encode($_payGateways, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_TAG);
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
            <input type="text" name="payment_public_key" id="payPubKeyInput" value="<?= htmlspecialchars($_payPubKey) ?>"
                   placeholder="pk_test_..." autocomplete="off"
                   class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 font-mono text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                <span id="secKeyLabel">Secret Key</span>
            </label>
            <div class="relative">
                <input type="text" name="payment_secret_key" id="paySecKeyInput"
                       value="<?= htmlspecialchars($_paySecKey) ?>" autocomplete="off"
                       placeholder="sk_test_..."
                       style="-webkit-text-security: disc; text-security: disc;"
                       class="w-full px-3 py-2 pr-10 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 font-mono text-sm">
                <button type="button" onclick="var i=document.getElementById('paySecKeyInput');var s=i.style;if(s.webkitTextSecurity==='disc'||s.textSecurity==='disc'){s.webkitTextSecurity='none';s.textSecurity='none'}else{s.webkitTextSecurity='disc';s.textSecurity='disc'}"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                </button>
            </div>
            <p id="paySecSavedHint" class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 <?= $_paySecKey ? '' : 'hidden' ?>"><?= $_paySecKey ? (__('settings.payment_config.key_saved') ?? '키가 저장되어 있습니다.') . ' (' . $_payMaskedSec . ')' : '' ?></p>
        </div>

        <!-- Webhook URL (PG사 대시보드에 등록할 주소) -->
        <?php
        $_appUrl = rtrim($_ENV['APP_URL'] ?? ('https://' . ($_SERVER['HTTP_HOST'] ?? '')), '/');
        $_webhookUrl = $_appUrl . '/api/webhook-payjp.php';
        ?>
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Webhook URL</label>
            <div class="flex gap-2">
                <input type="text" id="payWebhookUrl" value="<?= htmlspecialchars($_webhookUrl) ?>" readonly
                       class="flex-1 px-3 py-2 border border-zinc-300 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-200 rounded-lg font-mono text-sm cursor-text">
                <button type="button"
                        onclick="var el=document.getElementById('payWebhookUrl'); el.select(); navigator.clipboard.writeText(el.value).then(function(){var b=event.currentTarget;var t=b.textContent;b.textContent='복사됨!';setTimeout(function(){b.textContent=t;},1500);});"
                        class="px-3 py-2 text-sm font-medium border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-200 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 whitespace-nowrap">
                    복사
                </button>
            </div>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                PG사 대시보드 (예: PAY.JP → Webhook 설정)에 위 주소를 등록하세요. 결제 완료·실패·환불 등 이벤트가 이 주소로 전달됩니다.
            </p>
        </div>

        <!-- Webhook Token -->
        <?php
        $_payWebhookToken = $_payCurrentGw['webhook_token'] ?? '';
        $_payMaskedWebhook = $_payWebhookToken ? str_repeat('·', 12) . substr($_payWebhookToken, -6) : '';
        ?>
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Webhook Token</label>
            <div class="relative">
                <input type="text" name="payment_webhook_token" id="payWebhookInput"
                       value="<?= htmlspecialchars($_payWebhookToken) ?>" autocomplete="off"
                       placeholder="PG사 대시보드에서 Webhook 토큰을 복사하세요"
                       style="-webkit-text-security: disc; text-security: disc;"
                       class="w-full px-3 py-2 pr-10 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 font-mono text-sm">
                <button type="button" onclick="var i=document.getElementById('payWebhookInput');var s=i.style;if(s.webkitTextSecurity==='disc'||s.textSecurity==='disc'){s.webkitTextSecurity='none';s.textSecurity='none'}else{s.webkitTextSecurity='disc';s.textSecurity='disc'}"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                </button>
            </div>
            <?php if ($_payWebhookToken): ?>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1"><?php $_ks = __('settings.payment_config.key_saved'); echo $_ks === 'settings.payment_config.key_saved' ? '키가 저장되어 있습니다.' : $_ks; ?> (<?= $_payMaskedWebhook ?>)</p>
            <?php endif; ?>
            <p class="text-xs text-zinc-400 mt-1">Webhook 요청의 진위를 검증하는 토큰입니다. PG사 대시보드에서 확인할 수 있습니다.</p>
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
(function() {
    var savedGateways = <?= $_payGatewaysJson ?> || {};
    var labels = {
        stripe: ['Publishable Key', 'Secret Key', 'pk_test_...', 'sk_test_...'],
        toss: ['Client Key', 'Secret Key', 'test_ck_...', 'test_sk_...'],
        payjp: ['Public Key', 'Secret Key', 'pk_test_...', 'sk_test_...'],
        portone: ['Merchant ID', 'API Secret', 'imp_...', 'secret_...']
    };
    var pubInput = document.querySelector('[name=payment_public_key]');
    var secInput = document.getElementById('paySecKeyInput');
    var testToggle = document.querySelector('[name=payment_test_mode]');
    var savedHint = document.getElementById('paySecSavedHint');

    function switchGateway(gw) {
        var l = labels[gw] || labels.stripe;
        document.getElementById('pubKeyLabel').textContent = l[0];
        document.getElementById('secKeyLabel').textContent = l[1];
        pubInput.placeholder = l[2];
        secInput.placeholder = l[3];

        // 저장된 키값 로드
        var saved = savedGateways[gw] || {};
        pubInput.value = saved.public_key || '';
        secInput.value = saved.secret_key || '';
        if (testToggle) testToggle.checked = (saved.test_mode || '1') === '1';

        // 마스킹 힌트 업데이트
        if (savedHint) {
            if (saved.secret_key) {
                var masked = '••••••••••••' + saved.secret_key.slice(-8);
                savedHint.textContent = '<?= __('settings.payment_config.key_saved') ?? '키가 저장되어 있습니다.' ?> (' + masked + ')';
                savedHint.classList.remove('hidden');
            } else {
                savedHint.classList.add('hidden');
            }
        }
    }

    document.getElementById('payment_gateway').addEventListener('change', function() {
        switchGateway(this.value);
    });

    // 초기 로드: 현재 선택된 PG사의 키 표시
    switchGateway(document.getElementById('payment_gateway').value);
})();
</script>

<?php
$pageContent = ob_get_clean();

// Render layout with content
include __DIR__ . '/_layout.php';
