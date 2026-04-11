<?php
/**
 * 라이선스 상태 경고 배너
 * $licenseInfo (배열) — LicenseStatus::toArray() 결과
 */
if (empty($licenseInfo) || empty($licenseInfo['warning'])) return;

$_lBannerColors = [
    'info'    => 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-400',
    'warning' => 'bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-800 text-amber-700 dark:text-amber-400',
    'danger'  => 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800 text-red-700 dark:text-red-400',
];
$_lIconColors = [
    'info'    => 'text-blue-500',
    'warning' => 'text-amber-500',
    'danger'  => 'text-red-500',
];
$_lLevel = $licenseInfo['warning_level'] ?? 'info';
$_lColor = $_lBannerColors[$_lLevel] ?? $_lBannerColors['info'];
$_lIcon = $_lIconColors[$_lLevel] ?? $_lIconColors['info'];
?>
<div class="mx-6 mt-4 px-4 py-3 rounded-lg border text-sm flex items-center gap-3 <?= $_lColor ?>">
    <?php if ($_lLevel === 'danger'): ?>
    <svg class="w-5 h-5 flex-shrink-0 <?= $_lIcon ?>" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
    </svg>
    <?php elseif ($_lLevel === 'warning'): ?>
    <svg class="w-5 h-5 flex-shrink-0 <?= $_lIcon ?>" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
    </svg>
    <?php else: ?>
    <svg class="w-5 h-5 flex-shrink-0 <?= $_lIcon ?>" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
    </svg>
    <?php endif; ?>
    <div class="flex-1">
        <span class="font-medium"><?= htmlspecialchars($licenseInfo['warning']) ?></span>
        <?php if (!empty($licenseInfo['unauthorized_plugins'])): ?>
        <span class="text-xs opacity-75 ml-2">(<?= htmlspecialchars(implode(', ', $licenseInfo['unauthorized_plugins'])) ?>)</span>
        <?php endif; ?>
    </div>
    <?php if ($licenseInfo['state'] === 'unregistered'): ?>
    <a href="<?= ($config['app_url'] ?? '') . '/' . ($config['admin_path'] ?? 'admin') ?>/settings"
       class="text-xs font-medium underline hover:no-underline">설정</a>
    <?php endif; ?>
</div>
