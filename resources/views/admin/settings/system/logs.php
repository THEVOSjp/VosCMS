<?php
/**
 * RezlyX Admin Settings - Log Management
 * View and manage application logs
 */

// Initialize database and settings
require_once __DIR__ . '/../_init.php';

$pageTitle = __('admin.settings.system.tabs.logs') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
$currentSettingsPage = 'system';
$currentSystemTab = 'logs';

$logPath = BASE_PATH . '/storage/logs';

// Handle log actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    switch ($action) {
        case 'delete':
            $filename = basename($_POST['filename'] ?? '');
            $filePath = $logPath . '/' . $filename;
            if ($filename && file_exists($filePath) && pathinfo($filename, PATHINFO_EXTENSION) === 'log') {
                unlink($filePath);
                $message = __('admin.settings.system.logs.deleted');
                $messageType = 'success';
            }
            break;

        case 'delete_selected':
            $selectedFiles = $_POST['selected'] ?? [];
            $deletedCount = 0;
            foreach ($selectedFiles as $filename) {
                $filename = basename($filename);
                $filePath = $logPath . '/' . $filename;
                if ($filename && file_exists($filePath) && pathinfo($filename, PATHINFO_EXTENSION) === 'log') {
                    unlink($filePath);
                    $deletedCount++;
                }
            }
            if ($deletedCount > 0) {
                $message = __('admin.settings.system.logs.selected_deleted', ['count' => $deletedCount]);
                $messageType = 'success';
            }
            break;

        case 'clear_all':
            if (is_dir($logPath)) {
                $files = glob($logPath . '/*.log');
                foreach ($files as $file) {
                    unlink($file);
                }
                $message = __('admin.settings.system.logs.all_cleared');
                $messageType = 'success';
            }
            break;
    }
}

// Handle download
if (isset($_GET['download'])) {
    $downloadFile = basename($_GET['download']);
    $downloadPath = $logPath . '/' . $downloadFile;
    if (file_exists($downloadPath) && pathinfo($downloadFile, PATHINFO_EXTENSION) === 'log') {
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="' . $downloadFile . '"');
        header('Content-Length: ' . filesize($downloadPath));
        readfile($downloadPath);
        exit;
    }
}

// Get log files
$logFiles = [];
$totalSize = 0;
if (is_dir($logPath)) {
    $files = glob($logPath . '/*.log');
    foreach ($files as $file) {
        $size = filesize($file);
        $totalSize += $size;
        $logFiles[] = [
            'name' => basename($file),
            'size' => $size,
            'modified' => filemtime($file),
        ];
    }
    usort($logFiles, function($a, $b) {
        return $b['modified'] - $a['modified'];
    });
}

// Format file size
function formatLogSize($bytes) {
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    }
    return $bytes . ' B';
}

// View log content
$viewLogContent = null;
$viewLogName = null;
$viewLogSize = 0;
if (isset($_GET['view'])) {
    $viewLogName = basename($_GET['view']);
    $viewLogPath = $logPath . '/' . $viewLogName;
    if (file_exists($viewLogPath) && pathinfo($viewLogName, PATHINFO_EXTENSION) === 'log') {
        $viewLogSize = filesize($viewLogPath);
        // Get last 500 lines
        $lines = file($viewLogPath);
        $viewLogContent = implode('', array_slice($lines, -500));
    }
}

// Start content buffering
ob_start();
?>

<?php include __DIR__ . '/_tabs.php'; ?>

<!-- Message Alert -->
<?php if (!empty($message)): ?>
<div class="mb-6 p-4 rounded-lg <?= $messageType === 'success' ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-300' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300' ?>">
    <div class="flex items-center">
        <?php if ($messageType === 'success'): ?>
        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        <?php else: ?>
        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
        </svg>
        <?php endif; ?>
        <?= htmlspecialchars($message) ?>
    </div>
</div>
<?php endif; ?>

<!-- Log Management -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 mb-6 transition-colors">
    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4 flex items-center">
        <svg class="w-5 h-5 mr-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <?= __('admin.settings.system.logs.title') ?>
    </h2>
    <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-6"><?= __('admin.settings.system.logs.description') ?></p>

    <?php if ($viewLogContent !== null): ?>
    <!-- Log Viewer -->
    <div class="mb-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-md font-semibold text-zinc-900 dark:text-white flex items-center">
                    <svg class="w-4 h-4 mr-2 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <?= htmlspecialchars($viewLogName) ?>
                </h3>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                    <?= formatLogSize($viewLogSize) ?> | <?= __('admin.settings.system.logs.last_lines', ['count' => 500]) ?>
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="?download=<?= urlencode($viewLogName) ?>" class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/40 transition">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    <?= __('admin.settings.system.logs.download') ?>
                </a>
                <a href="<?= $baseUrl ?>/<?= $adminPath ?>/settings/system/logs" class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-zinc-600 dark:text-zinc-400 bg-zinc-100 dark:bg-zinc-700 rounded-lg hover:bg-zinc-200 dark:hover:bg-zinc-600 transition">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <?= __('admin.settings.system.logs.back_to_list') ?>
                </a>
            </div>
        </div>

        <!-- Log Content Viewer -->
        <div class="bg-zinc-900 rounded-lg overflow-hidden">
            <div class="flex items-center justify-between px-4 py-2 bg-zinc-800 border-b border-zinc-700">
                <span class="text-xs text-zinc-400 font-mono">tail -n 500 <?= htmlspecialchars($viewLogName) ?></span>
                <button onclick="copyLogContent()" class="text-xs text-zinc-400 hover:text-white transition flex items-center">
                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    <?= __('admin.settings.system.logs.copy') ?>
                </button>
            </div>
            <div class="p-4 overflow-x-auto max-h-[500px] overflow-y-auto">
                <pre id="logContent" class="text-xs text-green-400 font-mono whitespace-pre-wrap"><?= htmlspecialchars($viewLogContent) ?></pre>
            </div>
        </div>
    </div>
    <?php else: ?>

    <?php if (empty($logFiles)): ?>
    <div class="text-center py-12 text-zinc-500 dark:text-zinc-400">
        <svg class="w-16 h-16 mx-auto mb-4 text-zinc-300 dark:text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <p class="text-lg font-medium"><?= __('admin.settings.system.logs.no_logs') ?></p>
        <p class="text-sm mt-1"><?= __('admin.settings.system.logs.no_logs_desc') ?></p>
    </div>
    <?php else: ?>

    <form method="POST" id="logForm">
        <!-- Toolbar -->
        <div class="flex items-center justify-between mb-4 pb-4 border-b border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center gap-2">
                <span class="text-sm text-zinc-500 dark:text-zinc-400">
                    <?= __('admin.settings.system.logs.total_files', ['count' => count($logFiles)]) ?> | <?= formatLogSize($totalSize) ?>
                </span>
            </div>
            <div class="flex items-center gap-2">
                <!-- Selected Actions -->
                <div id="selectedActions" class="hidden items-center gap-2">
                    <span class="text-sm text-zinc-600 dark:text-zinc-400" id="selectedCount">0 <?= __('admin.settings.system.logs.selected') ?></span>
                    <button type="submit" name="action" value="delete_selected"
                            onclick="return confirm('<?= __('admin.settings.system.logs.confirm_delete_selected') ?>')"
                            class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/40 transition">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        <?= __('admin.settings.system.logs.delete_selected') ?>
                    </button>
                </div>
                <!-- Clear All -->
                <button type="submit" name="action" value="clear_all"
                        onclick="return confirm('<?= __('admin.settings.system.logs.confirm_clear_all') ?>')"
                        class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    <?= __('admin.settings.system.logs.clear_all') ?>
                </button>
            </div>
        </div>

        <!-- Log Files Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                        <th class="text-left py-3 px-4 w-10">
                            <input type="checkbox" id="selectAll" class="w-4 h-4 rounded border-zinc-300 dark:border-zinc-600 text-blue-600 focus:ring-blue-500 dark:bg-zinc-700">
                        </th>
                        <th class="text-left py-3 px-4 font-medium text-zinc-500 dark:text-zinc-400"><?= __('admin.settings.system.logs.filename') ?></th>
                        <th class="text-left py-3 px-4 font-medium text-zinc-500 dark:text-zinc-400"><?= __('admin.settings.system.logs.size') ?></th>
                        <th class="text-left py-3 px-4 font-medium text-zinc-500 dark:text-zinc-400"><?= __('admin.settings.system.logs.modified') ?></th>
                        <th class="text-right py-3 px-4 font-medium text-zinc-500 dark:text-zinc-400"><?= __('admin.settings.system.logs.actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($logFiles, 0, 50) as $index => $log): ?>
                    <tr class="border-b border-zinc-100 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-900/50 transition-colors">
                        <td class="py-3 px-4">
                            <input type="checkbox" name="selected[]" value="<?= htmlspecialchars($log['name']); ?>"
                                   class="log-checkbox w-4 h-4 rounded border-zinc-300 dark:border-zinc-600 text-blue-600 focus:ring-blue-500 dark:bg-zinc-700">
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-2 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <span class="text-zinc-900 dark:text-white font-medium"><?= htmlspecialchars($log['name']); ?></span>
                            </div>
                        </td>
                        <td class="py-3 px-4 text-zinc-500 dark:text-zinc-400"><?= formatLogSize($log['size']); ?></td>
                        <td class="py-3 px-4 text-zinc-500 dark:text-zinc-400"><?= date('Y-m-d H:i:s', $log['modified']); ?></td>
                        <td class="py-3 px-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <!-- View Button -->
                                <a href="?view=<?= urlencode($log['name']); ?>"
                                   class="inline-flex items-center px-2 py-1 text-xs font-medium text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 rounded hover:bg-blue-100 dark:hover:bg-blue-900/40 transition"
                                   title="<?= __('admin.settings.system.logs.view') ?>">
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    <?= __('admin.settings.system.logs.view') ?>
                                </a>
                                <!-- Download Button -->
                                <a href="?download=<?= urlencode($log['name']); ?>"
                                   class="inline-flex items-center px-2 py-1 text-xs font-medium text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20 rounded hover:bg-green-100 dark:hover:bg-green-900/40 transition"
                                   title="<?= __('admin.settings.system.logs.download') ?>">
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                    <?= __('admin.settings.system.logs.download') ?>
                                </a>
                                <!-- Delete Button -->
                                <button type="button" onclick="deleteLog('<?= htmlspecialchars($log['name']); ?>')"
                                        class="inline-flex items-center px-2 py-1 text-xs font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded hover:bg-red-100 dark:hover:bg-red-900/40 transition"
                                        title="<?= __('admin.settings.system.logs.delete') ?>">
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    <?= __('admin.settings.system.logs.delete') ?>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (count($logFiles) > 50): ?>
        <div class="mt-4 text-center text-sm text-zinc-500 dark:text-zinc-400">
            <?= __('admin.settings.system.logs.showing_first', ['count' => 50, 'total' => count($logFiles)]) ?>
        </div>
        <?php endif; ?>
    </form>

    <!-- Hidden form for single delete -->
    <form method="POST" id="deleteForm" class="hidden">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="filename" id="deleteFilename">
    </form>

    <?php endif; ?>
    <?php endif; ?>
</div>

<script>
    console.log('Log management page loaded');

    // Select all checkbox
    const selectAll = document.getElementById('selectAll');
    const logCheckboxes = document.querySelectorAll('.log-checkbox');
    const selectedActions = document.getElementById('selectedActions');
    const selectedCount = document.getElementById('selectedCount');

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            logCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateSelectedCount();
        });
    }

    logCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedCount);
    });

    function updateSelectedCount() {
        const checked = document.querySelectorAll('.log-checkbox:checked').length;
        if (selectedCount) {
            selectedCount.textContent = checked + ' <?= __('admin.settings.system.logs.selected') ?>';
        }
        if (selectedActions) {
            if (checked > 0) {
                selectedActions.classList.remove('hidden');
                selectedActions.classList.add('flex');
            } else {
                selectedActions.classList.add('hidden');
                selectedActions.classList.remove('flex');
            }
        }

        // Update selectAll state
        if (selectAll) {
            selectAll.checked = checked === logCheckboxes.length && logCheckboxes.length > 0;
            selectAll.indeterminate = checked > 0 && checked < logCheckboxes.length;
        }
    }

    // Single delete
    function deleteLog(filename) {
        if (confirm('<?= __('admin.settings.system.logs.confirm_delete') ?>')) {
            document.getElementById('deleteFilename').value = filename;
            document.getElementById('deleteForm').submit();
        }
    }

    // Copy log content
    function copyLogContent() {
        const logContent = document.getElementById('logContent');
        if (logContent) {
            navigator.clipboard.writeText(logContent.textContent).then(() => {
                alert('<?= __('admin.settings.system.logs.copied') ?>');
            });
        }
    }
</script>

<?php
$pageContent = ob_get_clean();
include __DIR__ . '/../_layout.php';
