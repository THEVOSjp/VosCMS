<?php
/**
 * Text Widget - render.php
 */
$content   = $renderer->t($config, 'content', '');
$customCss = trim($config['custom_css'] ?? '');
$customJs  = trim($config['custom_js'] ?? '');
$scopeId   = 'rzx-text-' . mt_rand(1000, 9999);

// content 내 <style> 태그의 전역 셀렉터를 스코핑
if (preg_match('/<style/i', $content)) {
    $content = preg_replace_callback('/<style([^>]*)>(.*?)<\/style>/is', function($m) use ($renderer, $scopeId) {
        return '<style' . $m[1] . '>' . $renderer->scopeWidgetCss($m[2], '#' . $scopeId) . '</style>';
    }, $content);
}

// 커스텀 CSS 스코핑
$cssBlock = '';
if ($customCss) {
    $scopedCss = $renderer->scopeWidgetCss($customCss, '#' . $scopeId);
    $cssBlock = '<style>' . $scopedCss . '</style>';
}

// 커스텀 JS (IIFE 래핑)
$jsBlock = '';
if ($customJs) {
    $jsBlock = '<script>(function(){var el=document.getElementById("' . $scopeId . '");' . $customJs . '})();</script>';
}

return $cssBlock
    . '<section class="py-12">'
    . '<div class="max-w-4xl mx-auto px-4">'
    . '<div id="' . $scopeId . '" data-widget-field="content" class="page-content text-gray-700 dark:text-zinc-300">' . $content . '</div>'
    . '</div></section>'
    . $jsBlock;
