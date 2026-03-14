<?php
/**
 * RezlyX Admin Settings - Update Management
 * 보안 강화된 서버 사이드 업데이트 시스템
 */

require_once __DIR__ . '/../_init.php';

$pageTitle = __('system.tabs.updates') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
$currentSettingsPage = 'system';
$currentSystemTab = 'updates';

// 버전 정보 로드
$versionFile = BASE_PATH . '/version.json';
$versionInfo = file_exists($versionFile) ? json_decode(file_get_contents($versionFile), true) : [
    'version' => '1.0.0',
    'channel' => 'stable',
];

$currentVersion = $versionInfo['version'] ?? '1.0.0';
$releaseDate = $versionInfo['release_date'] ?? '';
$channel = $versionInfo['channel'] ?? 'stable';

// 시스템 요구사항 확인
$requirements = [
    'curl' => extension_loaded('curl'),
    'json' => extension_loaded('json'),
    'zip' => extension_loaded('zip'),
    'openssl' => extension_loaded('openssl'),
    'allow_url_fopen' => (bool) ini_get('allow_url_fopen'),
    'writable_root' => is_writable(BASE_PATH),
    'writable_storage' => is_writable(BASE_PATH . '/storage'),
];
$allRequirementsMet = !in_array(false, $requirements, true);

// CSRF 토큰 생성
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

ob_start();
?>

<?php include __DIR__ . '/_tabs.php'; ?>

<!-- 현재 버전 정보 -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 mb-6 transition-colors">
    <?php
    $headerIcon = 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
    $headerTitle = __('system.updates.current_version');
    $headerDescription = ''; $headerActions = '';
    $headerIconColor = 'text-blue-600';
    include __DIR__ . '/../../components/settings-header.php';
    ?>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <!-- 설치 버전 -->
        <div class="flex items-center p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center mr-4 shrink-0">
                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-0.5"><?= __('system.updates.installed_version') ?></p>
                <p class="text-lg font-bold text-zinc-900 dark:text-white">RezlyX v<?= htmlspecialchars($currentVersion) ?></p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    <?= __('system.updates.channel') ?>:
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium <?= $channel === 'dev' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' ?>">
                        <?= htmlspecialchars(ucfirst($channel)) ?>
                    </span>
                    <?php if ($releaseDate): ?>
                    <span class="ml-2 text-zinc-400">|</span>
                    <span class="ml-2"><?= htmlspecialchars($releaseDate) ?></span>
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <!-- 최신 안정 버전 -->
        <div class="flex items-center p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
            <div class="w-12 h-12 <?= !empty($updateInfo['has_update']) ? 'bg-orange-100 dark:bg-orange-900/30' : 'bg-green-100 dark:bg-green-900/30' ?> rounded-lg flex items-center justify-center mr-4 shrink-0">
                <?php if (!empty($updateInfo['has_update'])): ?>
                <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                <?php else: ?>
                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <?php endif; ?>
            </div>
            <div>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-0.5"><?= __('system.updates.latest_version') ?></p>
                <?php if (!empty($updateInfo['latest'])): ?>
                <p class="text-lg font-bold <?= !empty($updateInfo['has_update']) ? 'text-orange-600 dark:text-orange-400' : 'text-green-600 dark:text-green-400' ?>">
                    RezlyX v<?= htmlspecialchars($updateInfo['latest']) ?>
                </p>
                <p class="text-sm <?= !empty($updateInfo['has_update']) ? 'text-orange-500 dark:text-orange-400' : 'text-green-500 dark:text-green-400' ?>">
                    <?= !empty($updateInfo['has_update']) ? __('system.updates.available_short') : __('system.updates.up_to_date') ?>
                </p>
                <?php else: ?>
                <p class="text-lg font-bold text-zinc-400 dark:text-zinc-500" id="latestVersionText">—</p>
                <p class="text-sm text-zinc-400 dark:text-zinc-500"><?= __('system.updates.check_update') ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="flex justify-end">
        <button type="button" id="checkUpdateBtn" onclick="checkForUpdates()"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition flex items-center disabled:opacity-50 disabled:cursor-not-allowed">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            <?= __('system.updates.check_update') ?>
        </button>
    </div>

    <!-- 업데이트 상태 영역 -->
    <div id="updateStatus" class="hidden mt-4"></div>
</div>

<!-- 백업 목록 -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 mb-6 transition-colors">
    <?php
    $headerIcon = 'M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4';
    $headerTitle = __('system.updates.backups');
    $headerDescription = ''; $headerActions = '';
    $headerIconColor = 'text-green-600';
    include __DIR__ . '/../../components/settings-header.php';
    ?>
    </h2>

    <div id="backupList" class="space-y-2">
        <div class="text-center py-4 text-zinc-500 dark:text-zinc-400">
            <svg class="w-8 h-8 mx-auto mb-2 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <?= __('admin.messages.loading') ?>
        </div>
    </div>
</div>

<!-- 시스템 요구사항 -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 mb-6 transition-colors">
    <?php
    $headerIcon = 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z';
    $headerTitle = __('system.updates.requirements');
    $headerDescription = ''; $headerActions = '';
    $headerIconColor = 'text-orange-600';
    include __DIR__ . '/../../components/settings-header.php';
    ?>

    <div class="space-y-3">
        <?php
        $reqLabels = [
            'curl' => 'cURL Extension',
            'json' => 'JSON Extension',
            'zip' => 'ZipArchive Extension',
            'openssl' => 'OpenSSL Extension',
            'allow_url_fopen' => 'allow_url_fopen',
            'writable_root' => __('system.updates.writable_root'),
            'writable_storage' => __('system.updates.writable_storage'),
        ];
        foreach ($requirements as $key => $met):
        ?>
        <div class="flex items-center justify-between p-3 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
            <span class="text-sm text-zinc-700 dark:text-zinc-300"><?= $reqLabels[$key] ?? $key ?></span>
            <?php if ($met): ?>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
                OK
            </span>
            <?php else: ?>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
                <?= __('system.updates.not_available') ?>
            </span>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if (!$allRequirementsMet): ?>
    <div class="mt-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
        <p class="text-sm text-yellow-800 dark:text-yellow-400">
            <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <?= __('system.updates.requirements_warning') ?>
        </p>
    </div>
    <?php endif; ?>
</div>

<!-- 업데이트 안내 -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 transition-colors">
    <?php
    $headerIcon = 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
    $headerTitle = __('system.updates.notes_title');
    $headerDescription = ''; $headerActions = '';
    $headerIconColor = 'text-purple-600';
    include __DIR__ . '/../../components/settings-header.php';
    ?>

    <div class="prose prose-sm dark:prose-invert max-w-none">
        <ul class="space-y-2 text-sm text-zinc-600 dark:text-zinc-400">
            <li class="flex items-start">
                <svg class="w-4 h-4 mr-2 mt-0.5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <?= __('system.updates.note_backup') ?>
            </li>
            <li class="flex items-start">
                <svg class="w-4 h-4 mr-2 mt-0.5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <?= __('system.updates.note_maintenance') ?>
            </li>
            <li class="flex items-start">
                <svg class="w-4 h-4 mr-2 mt-0.5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <?= __('system.updates.note_rollback') ?>
            </li>
        </ul>
    </div>
</div>

<!-- 업데이트 모달 -->
<div id="updateModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-xl max-w-2xl w-full mx-4 overflow-hidden">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4" id="modalTitle">
                <?= __('system.updates.new_version_available') ?>
            </h3>
            <div id="modalContent"></div>
        </div>
        <div id="modalFooter" class="px-6 py-4 bg-zinc-50 dark:bg-zinc-900 flex justify-end space-x-3">
            <button type="button" onclick="closeModal()"
                    class="px-4 py-2 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700 rounded-lg transition">
                <?= __('admin.buttons.cancel') ?>
            </button>
        </div>
    </div>
</div>

<!-- 변경 파일 비교 영역 (숨김) -->
<div id="compareSection" class="hidden mt-6 bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 transition-colors">
    <?php
    $headerIcon = 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2';
    $headerTitle = __('system.updates.changed_files');
    $headerDescription = ''; $headerActions = '';
    $headerIconColor = 'text-indigo-600';
    include __DIR__ . '/../../components/settings-header.php';
    ?>
    <div id="compareContent"></div>
</div>

<?php include __DIR__ . '/updates-js.php'; ?>

<?php
$pageContent = ob_get_clean();
include __DIR__ . '/../_layout.php';
