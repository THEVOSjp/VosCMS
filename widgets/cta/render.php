<?php
/**
 * CTA Widget - render.php
 */
$title    = htmlspecialchars($renderer->t($config, 'title', __('home.cta.title')));
$subtitle = nl2br(htmlspecialchars($renderer->t($config, 'subtitle', __('home.cta.subtitle'))));
$btnText  = htmlspecialchars($renderer->t($config, 'btn_text', __('home.cta.start_free')));
$btnUrl   = htmlspecialchars($config['btn_url'] ?? $baseUrl . '/booking');

return '<section class="py-20 bg-gradient-to-r from-blue-600 to-blue-800 dark:from-blue-800 dark:to-zinc-900 text-white">'
    . '<div class="max-w-4xl mx-auto px-4 text-center">'
    . '<h2 data-widget-field="title" class="text-3xl font-bold mb-4">' . $title . '</h2>'
    . '<p data-widget-field="subtitle" class="text-blue-100 mb-8">' . $subtitle . '</p>'
    . '<a data-widget-field="btn_text" href="' . $btnUrl . '" class="inline-flex items-center px-8 py-4 bg-white text-blue-600 font-semibold rounded-xl hover:bg-blue-50 transition shadow-lg">' . $btnText . '</a>'
    . '</div></section>';
