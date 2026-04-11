<?php
/**
 * Stats Widget - render.php
 */
$stats = $config['items'] ?? [
    ['number' => '10,000+', 'label' => __('home.stats.total_bookings')],
    ['number' => '98%',     'label' => __('home.stats.satisfaction')],
    ['number' => '500+',    'label' => __('home.stats.partners')],
    ['number' => '24/7',    'label' => __('home.stats.support')],
];

$items = '';
foreach ($stats as $s) {
    $num = htmlspecialchars($s['number'] ?? '');
    $lbl = htmlspecialchars($s['label'] ?? '');
    $items .= '<div><p class="text-3xl font-bold text-blue-600 dark:text-blue-400">' . $num . '</p><p class="text-sm text-gray-600 dark:text-zinc-400 mt-1">' . $lbl . '</p></div>';
}

return '<section class="py-16 bg-white dark:bg-zinc-800">'
    . '<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">'
    . '<div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">' . $items . '</div>'
    . '</div></section>';
