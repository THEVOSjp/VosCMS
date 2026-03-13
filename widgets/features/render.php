<?php
/**
 * Features Widget - render.php
 */
$icons = [
    'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z',
    'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
];
$colors = ['blue', 'green', 'purple'];
$keys = ['mobile', 'realtime', 'easy_payment'];

$cards = '';
for ($i = 0; $i < 3; $i++) {
    $fc    = $colors[$i];
    $title = htmlspecialchars(__('home.features.' . $keys[$i] . '.title'));
    $desc  = htmlspecialchars(__('home.features.' . $keys[$i] . '.desc'));
    $cards .= '<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-8 text-center hover:shadow-lg transition">'
        . '<div class="w-16 h-16 bg-' . $fc . '-100 dark:bg-' . $fc . '-900/50 rounded-xl flex items-center justify-center mx-auto mb-6">'
        . '<svg class="w-8 h-8 text-' . $fc . '-600 dark:text-' . $fc . '-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="' . $icons[$i] . '"/></svg>'
        . '</div>'
        . '<h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">' . $title . '</h3>'
        . '<p class="text-gray-600 dark:text-zinc-400">' . $desc . '</p>'
        . '</div>';
}

$sTitle = htmlspecialchars(__('home.features.title'));
$sSub   = htmlspecialchars(__('home.features.subtitle'));

return '<section class="py-20">'
    . '<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">'
    . '<div class="text-center mb-16">'
    . '<h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">' . $sTitle . '</h2>'
    . '<p class="text-gray-600 dark:text-zinc-400">' . $sSub . '</p>'
    . '</div>'
    . '<div class="grid grid-cols-1 md:grid-cols-3 gap-8">' . $cards . '</div>'
    . '</div></section>';
