<?php
/**
 * Services Widget - render.php
 */
$limit = (int)($config['count'] ?? 6);
try {
    $stmt = $pdo->prepare("SELECT * FROM rzx_services WHERE is_active = 1 ORDER BY sort_order ASC LIMIT ?");
    $stmt->execute([$limit]);
    $services = $stmt->fetchAll(\PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    $services = [];
}

$cards = '';
foreach ($services as $svc) {
    $name  = htmlspecialchars($svc['name'] ?? '');
    $desc  = htmlspecialchars($svc['description'] ?? '');
    $price = !empty($config['show_price']) ? '<span class="text-blue-600 dark:text-blue-400 font-bold">' . number_format($svc['price'] ?? 0) . '원</span>' : '';
    $dur   = !empty($config['show_duration']) ? '<span class="text-sm text-zinc-500">' . ($svc['duration'] ?? 0) . '분</span>' : '';
    $cards .= '<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 hover:shadow-lg transition">'
        . '<h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">' . $name . '</h3>'
        . '<p class="text-sm text-gray-600 dark:text-zinc-400 mb-4">' . $desc . '</p>'
        . '<div class="flex items-center justify-between">' . $price . $dur . '</div>'
        . '</div>';
}

$sTitle = htmlspecialchars(__('home.services.title'));
$sSub   = htmlspecialchars(__('home.services.subtitle'));

return '<section class="py-20 bg-gray-50 dark:bg-zinc-900">'
    . '<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">'
    . '<div class="text-center mb-16">'
    . '<h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">' . $sTitle . '</h2>'
    . '<p class="text-gray-600 dark:text-zinc-400">' . $sSub . '</p>'
    . '</div>'
    . '<div class="grid grid-cols-1 md:grid-cols-3 gap-6">' . $cards . '</div>'
    . '</div></section>';
