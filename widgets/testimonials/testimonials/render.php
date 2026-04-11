<?php
/**
 * Testimonials Widget - render.php
 */
$reviews = $config['items'] ?? [];
$cards = '';
foreach ($reviews as $rv) {
    $stars   = str_repeat('&#9733;', (int)($rv['rating'] ?? 5));
    $content = htmlspecialchars($rv['content'] ?? '');
    $name    = htmlspecialchars($rv['name'] ?? '');
    $cards .= '<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6">'
        . '<div class="flex text-yellow-400 mb-3">' . $stars . '</div>'
        . '<p class="text-gray-600 dark:text-zinc-400 mb-4">' . $content . '</p>'
        . '<p class="font-semibold text-gray-900 dark:text-white">' . $name . '</p>'
        . '</div>';
}

$sTitle = htmlspecialchars(__('home.testimonials.title'));
$sSub   = htmlspecialchars(__('home.testimonials.subtitle'));

return '<section class="py-20">'
    . '<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">'
    . '<div class="text-center mb-16">'
    . '<h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">' . $sTitle . '</h2>'
    . '<p class="text-gray-600 dark:text-zinc-400">' . $sSub . '</p>'
    . '</div>'
    . '<div class="grid grid-cols-1 md:grid-cols-3 gap-8">' . $cards . '</div>'
    . '</div></section>';
