<?php
/**
 * RezlyX Admin Settings - Update Management
 * GitHub-based auto-update system
 */

// Initialize database and settings
require_once __DIR__ . '/../_init.php';

$pageTitle = __('admin.settings.system.tabs.updates') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
$currentSettingsPage = 'system';
$currentSystemTab = 'updates';

// Load version information
$versionFile = BASE_PATH . '/version.json';
$versionInfo = file_exists($versionFile) ? json_decode(file_get_contents($versionFile), true) : [
    'version' => '1.0.0',
    'commit' => null,
    'channel' => 'stable',
    'github' => ['owner' => '', 'repo' => '', 'branch' => 'main']
];

$currentVersion = $versionInfo['version'] ?? '1.0.0';
$githubOwner = $versionInfo['github']['owner'] ?? '';
$githubRepo = $versionInfo['github']['repo'] ?? '';
$githubBranch = $versionInfo['github']['branch'] ?? 'main';

// Messages
$message = '';
$messageType = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    switch ($action) {
        case 'save_github_settings':
            $newOwner = trim($_POST['github_owner'] ?? '');
            $newRepo = trim($_POST['github_repo'] ?? '');
            $newBranch = trim($_POST['github_branch'] ?? 'main');
            $newToken = trim($_POST['github_token'] ?? '');

            // Update version.json
            $versionInfo['github']['owner'] = $newOwner;
            $versionInfo['github']['repo'] = $newRepo;
            $versionInfo['github']['branch'] = $newBranch;

            if (file_put_contents($versionFile, json_encode($versionInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
                // Save token to database if provided
                if (!empty($newToken)) {
                    try {
                        $stmt = $pdo->prepare("INSERT INTO rzx_settings (`key`, `value`) VALUES ('github_token', ?) ON DUPLICATE KEY UPDATE `value` = ?");
                        $stmt->execute([$newToken, $newToken]);
                    } catch (PDOException $e) {
                        // Ignore
                    }
                }

                $githubOwner = $newOwner;
                $githubRepo = $newRepo;
                $githubBranch = $newBranch;

                $message = __('admin.settings.system.updates.settings_saved');
                $messageType = 'success';
            } else {
                $message = __('admin.settings.system.updates.settings_error');
                $messageType = 'error';
            }
            break;

        case 'check_update':
            // Check for updates via GitHub API
            if (empty($githubOwner) || empty($githubRepo)) {
                $message = __('admin.settings.system.updates.github_not_configured');
                $messageType = 'error';
            } else {
                // This will be handled via JavaScript/AJAX for better UX
                $message = __('admin.settings.system.updates.checking');
                $messageType = 'info';
            }
            break;
    }
}

// Get GitHub token from database
$githubToken = '';
try {
    $stmt = $pdo->prepare("SELECT `value` FROM rzx_settings WHERE `key` = 'github_token'");
    $stmt->execute();
    $githubToken = $stmt->fetchColumn() ?: '';
} catch (PDOException $e) {
    // Ignore
}

// Check if GitHub is configured
$isGithubConfigured = !empty($githubOwner) && !empty($githubRepo);

// Check system requirements for updates
$requirements = [
    'curl' => extension_loaded('curl'),
    'json' => extension_loaded('json'),
    'zip' => extension_loaded('zip'),
    'allow_url_fopen' => ini_get('allow_url_fopen'),
    'writable_root' => is_writable(BASE_PATH),
];
$allRequirementsMet = !in_array(false, $requirements, true);

// Start content buffering
ob_start();
?>

<?php include __DIR__ . '/_tabs.php'; ?>

<?php if ($message): ?>
<div class="mb-6 p-4 rounded-lg <?= $messageType === 'success' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : ($messageType === 'error' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400') ?>">
    <?= htmlspecialchars($message) ?>
</div>
<?php endif; ?>

<!-- Current Version Info -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 mb-6 transition-colors">
    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4 flex items-center">
        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <?= __('admin.settings.system.updates.current_version') ?>
    </h2>

    <div class="flex items-center justify-between p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center mr-4">
                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                </svg>
            </div>
            <div>
                <p class="text-lg font-bold text-zinc-900 dark:text-white">RezlyX v<?= htmlspecialchars($currentVersion) ?></p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    <?= __('admin.settings.system.updates.channel') ?>:
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                        <?= htmlspecialchars(ucfirst($versionInfo['channel'] ?? 'stable')) ?>
                    </span>
                </p>
            </div>
        </div>

        <?php if ($isGithubConfigured): ?>
        <button type="button" id="checkUpdateBtn" onclick="checkForUpdates()"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition flex items-center">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            <?= __('admin.settings.system.updates.check_update') ?>
        </button>
        <?php endif; ?>
    </div>

    <!-- Update Status Area -->
    <div id="updateStatus" class="hidden mt-4"></div>
</div>

<!-- GitHub Settings -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 mb-6 transition-colors">
    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4 flex items-center">
        <svg class="w-5 h-5 mr-2 text-zinc-700 dark:text-zinc-300" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
        </svg>
        <?= __('admin.settings.system.updates.github_settings') ?>
    </h2>
    <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-6"><?= __('admin.settings.system.updates.github_description') ?></p>

    <form method="POST" class="space-y-4">
        <input type="hidden" name="action" value="save_github_settings">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                    <?= __('admin.settings.system.updates.github_owner') ?>
                </label>
                <input type="text" name="github_owner" value="<?= htmlspecialchars($githubOwner) ?>"
                       placeholder="username or organization"
                       class="w-full px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1"><?= __('admin.settings.system.updates.github_owner_hint') ?></p>
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                    <?= __('admin.settings.system.updates.github_repo') ?>
                </label>
                <input type="text" name="github_repo" value="<?= htmlspecialchars($githubRepo) ?>"
                       placeholder="repository-name"
                       class="w-full px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1"><?= __('admin.settings.system.updates.github_repo_hint') ?></p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                    <?= __('admin.settings.system.updates.github_branch') ?>
                </label>
                <input type="text" name="github_branch" value="<?= htmlspecialchars($githubBranch) ?>"
                       placeholder="main"
                       class="w-full px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                    <?= __('admin.settings.system.updates.github_token') ?>
                    <span class="text-xs text-zinc-400">(<?= __('admin.settings.system.updates.optional') ?>)</span>
                </label>
                <input type="password" name="github_token" value=""
                       placeholder="<?= $githubToken ? '********' : 'ghp_xxxxxxxxxxxx' ?>"
                       class="w-full px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1"><?= __('admin.settings.system.updates.github_token_hint') ?></p>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
                <?= __('admin.buttons.save') ?>
            </button>
        </div>
    </form>
</div>

<!-- System Requirements -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 mb-6 transition-colors">
    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4 flex items-center">
        <svg class="w-5 h-5 mr-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
        </svg>
        <?= __('admin.settings.system.updates.requirements') ?>
    </h2>

    <div class="space-y-3">
        <?php
        $reqLabels = [
            'curl' => 'cURL Extension',
            'json' => 'JSON Extension',
            'zip' => 'ZipArchive Extension',
            'allow_url_fopen' => 'allow_url_fopen',
            'writable_root' => __('admin.settings.system.updates.writable_root'),
        ];
        foreach ($requirements as $key => $met):
        ?>
        <div class="flex items-center justify-between p-3 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
            <span class="text-sm text-zinc-700 dark:text-zinc-300"><?= $reqLabels[$key] ?></span>
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
                <?= __('admin.settings.system.updates.not_available') ?>
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
            <?= __('admin.settings.system.updates.requirements_warning') ?>
        </p>
    </div>
    <?php endif; ?>
</div>

<!-- Update Notes -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 transition-colors">
    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4 flex items-center">
        <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <?= __('admin.settings.system.updates.notes_title') ?>
    </h2>

    <div class="prose prose-sm dark:prose-invert max-w-none">
        <ul class="space-y-2 text-sm text-zinc-600 dark:text-zinc-400">
            <li><?= __('admin.settings.system.updates.note_backup') ?></li>
            <li><?= __('admin.settings.system.updates.note_maintenance') ?></li>
            <li><?= __('admin.settings.system.updates.note_rollback') ?></li>
            <li><?= __('admin.settings.system.updates.note_private') ?></li>
        </ul>
    </div>
</div>

<script>
console.log('Update management page loaded');

const githubOwner = '<?= htmlspecialchars($githubOwner) ?>';
const githubRepo = '<?= htmlspecialchars($githubRepo) ?>';
const currentVersion = '<?= htmlspecialchars($currentVersion) ?>';

async function checkForUpdates() {
    const btn = document.getElementById('checkUpdateBtn');
    const statusDiv = document.getElementById('updateStatus');

    if (!githubOwner || !githubRepo) {
        statusDiv.innerHTML = '<div class="p-4 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400 rounded-lg"><?= __('admin.settings.system.updates.github_not_configured') ?></div>';
        statusDiv.classList.remove('hidden');
        return;
    }

    // Show loading
    btn.disabled = true;
    btn.innerHTML = '<svg class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><?= __('admin.settings.system.updates.checking') ?>';

    try {
        // Fetch latest release from GitHub API
        const response = await fetch(`https://api.github.com/repos/${githubOwner}/${githubRepo}/releases/latest`, {
            headers: {
                'Accept': 'application/vnd.github.v3+json',
                'User-Agent': 'RezlyX-Updater'
            }
        });

        if (!response.ok) {
            if (response.status === 404) {
                throw new Error('<?= __('admin.settings.system.updates.no_releases') ?>');
            }
            throw new Error('GitHub API error: ' + response.status);
        }

        const release = await response.json();
        const latestVersion = release.tag_name.replace(/^v/, '');

        // Compare versions
        if (compareVersions(latestVersion, currentVersion) > 0) {
            statusDiv.innerHTML = `
                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <p class="text-sm font-medium text-blue-800 dark:text-blue-400">
                                <?= __('admin.settings.system.updates.new_version_available') ?>
                            </p>
                            <p class="text-lg font-bold text-blue-900 dark:text-blue-300">v${latestVersion}</p>
                        </div>
                        <button onclick="showUpdateDetails('${release.html_url}')" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
                            <?= __('admin.settings.system.updates.view_details') ?>
                        </button>
                    </div>
                    <div class="text-sm text-blue-700 dark:text-blue-400">
                        <p class="font-medium mb-1"><?= __('admin.settings.system.updates.release_notes') ?>:</p>
                        <div class="bg-white/50 dark:bg-zinc-800/50 p-3 rounded max-h-32 overflow-y-auto">
                            ${release.body ? marked ? marked.parse(release.body) : release.body.replace(/\n/g, '<br>') : '<?= __('admin.settings.system.updates.no_notes') ?>'}
                        </div>
                    </div>
                </div>
            `;
        } else {
            statusDiv.innerHTML = `
                <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg flex items-center">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-sm font-medium text-green-800 dark:text-green-400"><?= __('admin.settings.system.updates.up_to_date') ?></p>
                </div>
            `;
        }
        statusDiv.classList.remove('hidden');

    } catch (error) {
        statusDiv.innerHTML = `<div class="p-4 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400 rounded-lg">${error.message}</div>`;
        statusDiv.classList.remove('hidden');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg><?= __('admin.settings.system.updates.check_update') ?>';
    }
}

function compareVersions(v1, v2) {
    const parts1 = v1.split('.').map(Number);
    const parts2 = v2.split('.').map(Number);

    for (let i = 0; i < Math.max(parts1.length, parts2.length); i++) {
        const p1 = parts1[i] || 0;
        const p2 = parts2[i] || 0;
        if (p1 > p2) return 1;
        if (p1 < p2) return -1;
    }
    return 0;
}

function showUpdateDetails(url) {
    window.open(url, '_blank');
}
</script>

<?php
$pageContent = ob_get_clean();
include __DIR__ . '/../_layout.php';
