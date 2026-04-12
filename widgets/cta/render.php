<?php
/**
 * CTA Widget - render.php
 * 행동 유도 배너 (버튼 최대 3개 지원)
 */

$title    = htmlspecialchars($renderer->t($config, 'title', __('home.cta.title') ?? 'Ready to Get Started?'));
$subtitle = nl2br(htmlspecialchars($renderer->t($config, 'subtitle', __('home.cta.subtitle') ?? '')));
$bgStyle  = $config['bg_style'] ?? 'blue'; // blue, dark, custom

// 배경 클래스
$bgClass = match($bgStyle) {
    'dark'   => 'bg-zinc-900 dark:bg-zinc-950',
    'custom' => '',
    default  => 'bg-gradient-to-r from-blue-600 to-blue-800 dark:from-blue-800 dark:to-zinc-900',
};
$customBg = ($bgStyle === 'custom' && !empty($config['bg_color']))
    ? ' style="background-color:' . htmlspecialchars($config['bg_color']) . '"' : '';

// 버튼 (최대 3개)
$buttons = [];
for ($i = 1; $i <= 3; $i++) {
    $suffix = $i === 1 ? '' : '_' . $i;
    $btnText = $renderer->t($config, 'btn_text' . $suffix, '');
    $btnUrl  = trim($config['btn_url' . $suffix] ?? '');
    if ($btnText && $btnUrl) {
        $btnStyle = $config['btn_style' . $suffix] ?? ($i === 1 ? 'primary' : 'outline');
        $buttons[] = ['text' => htmlspecialchars($btnText), 'url' => htmlspecialchars($btnUrl), 'style' => $btnStyle];
    }
}

// 레거시 호환 (btn_text/btn_url 단일 필드)
if (empty($buttons)) {
    $btnText = $renderer->t($config, 'btn_text', __('home.cta.start_free') ?? 'Get Started');
    $btnUrl  = $config['btn_url'] ?? $baseUrl . '/register';
    if ($btnText) {
        $buttons[] = ['text' => htmlspecialchars($btnText), 'url' => htmlspecialchars($btnUrl), 'style' => 'primary'];
    }
}

// 버튼 스타일 클래스
$btnClasses = [
    'primary' => 'bg-white text-blue-600 hover:bg-blue-50 shadow-lg',
    'outline' => 'border-2 border-white text-white hover:bg-white/10',
    'ghost'   => 'text-white/80 hover:text-white underline underline-offset-4',
];

// 버튼 HTML
$buttonsHtml = '';
if (!empty($buttons)) {
    $buttonsHtml = '<div class="flex flex-wrap items-center justify-center gap-4">';
    foreach ($buttons as $btn) {
        $cls = $btnClasses[$btn['style']] ?? $btnClasses['primary'];
        $buttonsHtml .= '<a href="' . $btn['url'] . '" class="inline-flex items-center px-8 py-4 font-semibold rounded-xl transition ' . $cls . '">' . $btn['text'] . '</a>';
    }
    $buttonsHtml .= '</div>';
}

return '<section class="py-20 ' . $bgClass . ' text-white"' . $customBg . '>'
    . '<div class="max-w-4xl mx-auto px-4 text-center">'
    . '<h2 data-widget-field="title" class="text-3xl font-bold mb-4">' . $title . '</h2>'
    . ($subtitle ? '<p data-widget-field="subtitle" class="text-blue-100 dark:text-zinc-300 mb-8">' . $subtitle . '</p>' : '')
    . $buttonsHtml
    . '</div></section>';
