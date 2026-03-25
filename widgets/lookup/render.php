<?php
/**
 * Lookup Widget - render.php
 * 예약 조회 폼을 직접 렌더링하는 시스템 위젯
 */
$wTitle = $config['title'] ?? '';
$wSubtitle = $config['subtitle'] ?? '';
$baseUrl = $baseUrl ?? '';
$currentLocale = $locale ?? ($currentLocale ?? 'ko');

if (!defined('BASE_PATH')) define('BASE_PATH', realpath(__DIR__ . '/../../'));
$_GET['widget_mode'] = '1';

ob_start();
?>
<section class="py-8">
    <div class="max-w-3xl mx-auto px-4">
        <?php if ($wTitle || $wSubtitle): ?>
        <div class="text-center mb-8">
            <?php if ($wTitle): ?><h2 class="text-3xl font-bold text-gray-900 dark:text-white"><?= htmlspecialchars($renderer->t($config, 'title', $wTitle)) ?></h2><?php endif; ?>
            <?php if ($wSubtitle): ?><p class="text-gray-600 dark:text-zinc-400 mt-2"><?= htmlspecialchars($renderer->t($config, 'subtitle', $wSubtitle)) ?></p><?php endif; ?>
        </div>
        <?php endif; ?>
        <?php include BASE_PATH . '/resources/views/customer/booking/lookup.php'; ?>
    </div>
</section>
<?php
unset($_GET['widget_mode']);
return ob_get_clean();
