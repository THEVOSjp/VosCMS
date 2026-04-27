<?php
/**
 * RezlyX Default Theme - Alert Components
 *
 * 사용법:
 * $alert = ['type' => 'success', 'message' => '저장되었습니다.'];
 * include 'alerts.php';
 */
?>

<?php if (isset($alert) && is_array($alert)): ?>
<?php
$type = $alert['type'] ?? 'info';
$message = $alert['message'] ?? '';
$dismissible = $alert['dismissible'] ?? true;

$styles = [
    'success' => [
        'bg' => 'bg-green-50 dark:bg-green-900/30',
        'border' => 'border-green-200 dark:border-green-800',
        'icon' => 'text-green-600 dark:text-green-400',
        'text' => 'text-green-700 dark:text-green-300',
    ],
    'error' => [
        'bg' => 'bg-red-50 dark:bg-red-900/30',
        'border' => 'border-red-200 dark:border-red-800',
        'icon' => 'text-red-600 dark:text-red-400',
        'text' => 'text-red-700 dark:text-red-300',
    ],
    'warning' => [
        'bg' => 'bg-amber-50 dark:bg-amber-900/20',
        'border' => 'border-amber-200 dark:border-amber-800',
        'icon' => 'text-amber-600 dark:text-amber-400',
        'text' => 'text-amber-700 dark:text-amber-300',
    ],
    'info' => [
        'bg' => 'bg-blue-50 dark:bg-blue-900/30',
        'border' => 'border-blue-200 dark:border-blue-800',
        'icon' => 'text-blue-600 dark:text-blue-400',
        'text' => 'text-blue-700 dark:text-blue-300',
    ],
];

$style = $styles[$type] ?? $styles['info'];

$icons = [
    'success' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
    'error' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
    'warning' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>',
    'info' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
];

$icon = $icons[$type] ?? $icons['info'];
?>

<div class="p-4 <?php echo $style['bg']; ?> border <?php echo $style['border']; ?> rounded-lg"
     x-data="{ show: true }"
     x-show="show"
     x-transition>
    <div class="flex items-start">
        <svg class="w-5 h-5 <?php echo $style['icon']; ?> mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <?php echo $icon; ?>
        </svg>
        <div class="flex-1 <?php echo $style['text']; ?> text-sm">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php if ($dismissible): ?>
            <button @click="show = false" class="ml-3 <?php echo $style['icon']; ?> hover:opacity-70 transition-opacity">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
