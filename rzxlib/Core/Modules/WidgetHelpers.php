<?php
/**
 * VosCMS Widget Helpers
 *
 * 위젯 render.php에서 반복되는 공통 패턴을 함수로 제공합니다.
 *
 * 사용법 (render.php 상단에서):
 *   require_once BASE_PATH . '/rzxlib/Core/Modules/WidgetHelpers.php';
 *   // 또는 WidgetLoader가 자동으로 로드
 *
 *   $ctx = rzx_widget_init($config, 'my-widget');
 *   // $ctx['locale'], $ctx['uid'], $ctx['section_style'] 사용
 */

if (!function_exists('rzx_widget_init')) {

    /**
     * 위젯 공통 초기화
     *
     * @param array  $config   위젯 설정
     * @param string $slug     위젯 slug (uid 접두사)
     * @param array  $options  추가 옵션
     * @return array ['locale', 'uid', 'bg_color', 'section_style', 'section_open', 'section_close', 'prefix']
     */
    function rzx_widget_init(array $config, string $slug, array $options = []): array
    {
        $locale = $GLOBALS['locale']
            ?? (function_exists('current_locale') ? current_locale() : 'ko');

        $uid = $slug . '-' . mt_rand(1000, 9999);

        $bgColor = $config['bg_color'] ?? 'transparent';
        $sectionStyle = ($bgColor && $bgColor !== 'transparent')
            ? 'background-color:' . htmlspecialchars($bgColor) . ';' : '';

        $sectionClass = $options['section_class'] ?? 'py-12';
        $sectionOpen = '<section class="' . $sectionClass . '"'
            . ($sectionStyle ? ' style="' . $sectionStyle . '"' : '') . '>';
        $sectionClose = '</section>';

        $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

        return [
            'locale'        => $locale,
            'uid'           => $uid,
            'bg_color'      => $bgColor,
            'section_style' => $sectionStyle,
            'section_open'  => $sectionOpen,
            'section_close' => $sectionClose,
            'prefix'        => $prefix,
        ];
    }

    /**
     * 위젯 섹션 헤더 (제목 + 더보기 링크)
     *
     * @param string $title     섹션 제목 (이미 htmlspecialchars 적용)
     * @param string $moreUrl   더보기 링크 URL (빈 문자열이면 표시 안 함)
     * @param string $moreText  더보기 텍스트
     * @return string HTML
     */
    function rzx_widget_header(string $title, string $moreUrl = '', string $moreText = ''): string
    {
        if (!$title && !$moreUrl) return '';
        if (!$moreText) $moreText = __('common.nav.more') ?? __('common.more') ?? '더보기';

        $html = '<div class="flex items-center justify-between mb-6">';
        if ($title) {
            $html .= '<h2 class="text-xl font-bold text-zinc-900 dark:text-white border-l-4 border-blue-500 pl-3">' . $title . '</h2>';
        }
        if ($moreUrl) {
            $html .= '<a href="' . htmlspecialchars($moreUrl) . '" class="text-sm text-zinc-500 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 transition flex items-center">'
                . htmlspecialchars($moreText)
                . '<svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></a>';
        }
        $html .= '</div>';
        return $html;
    }

    /**
     * 가로 슬라이드 CSS + JS (scroll-snap + 네비게이션)
     *
     * @param string $uid       위젯 고유 ID
     * @param string $prefix    CSS 클래스 접두사 (예: 'bs', 'mp')
     * @param int    $cardWidth 카드 너비 (px)
     * @return array ['css' => string, 'js' => string, 'nav_prev' => string, 'nav_next' => string]
     */
    function rzx_widget_scroll_slide(string $uid, string $prefix = 'sl', int $cardWidth = 260): array
    {
        $mobileWidth = $cardWidth - 20;
        $scrollAmount = $cardWidth + 20;

        $css = <<<CSS
<style>
#{$uid} .{$prefix}-scroll-container {
    display: flex; gap: 1rem; overflow-x: auto; scroll-snap-type: x mandatory;
    -webkit-overflow-scrolling: touch; scrollbar-width: none; padding-bottom: 4px;
}
#{$uid} .{$prefix}-scroll-container::-webkit-scrollbar { display: none; }
#{$uid} .{$prefix}-card { scroll-snap-align: start; flex: 0 0 {$mobileWidth}px; min-width: {$mobileWidth}px; max-width: {$mobileWidth}px; }
@media (min-width: 640px) { #{$uid} .{$prefix}-card { flex: 0 0 {$cardWidth}px; min-width: {$cardWidth}px; max-width: {$cardWidth}px; } }
#{$uid} .{$prefix}-nav-btn {
    position: absolute; top: 50%; transform: translateY(-50%); z-index: 10;
    width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
    background: rgba(255,255,255,0.9); border: 1px solid #e4e4e7; box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    cursor: pointer; transition: opacity 0.2s, background 0.2s;
}
#{$uid} .{$prefix}-nav-btn:hover { background: #fff; }
.dark #{$uid} .{$prefix}-nav-btn { background: rgba(39,39,42,0.9); border-color: #52525b; color: #a1a1aa; }
.dark #{$uid} .{$prefix}-nav-btn:hover { background: #3f3f46; color: #fff; }
#{$uid} .{$prefix}-nav-prev { left: -12px; }
#{$uid} .{$prefix}-nav-next { right: -12px; }
#{$uid} .{$prefix}-nav-btn.hidden { opacity: 0; pointer-events: none; }
</style>
CSS;

        $navPrev = '<button class="' . $prefix . '-nav-btn ' . $prefix . '-nav-prev hidden" aria-label="Previous"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></button>';
        $navNext = '<button class="' . $prefix . '-nav-btn ' . $prefix . '-nav-next" aria-label="Next"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></button>';

        $js = <<<JS
<script>
(function() {
    var root = document.getElementById('{$uid}');
    if (!root) return;
    var container = root.querySelector('.{$prefix}-scroll-container');
    var prev = root.querySelector('.{$prefix}-nav-prev');
    var next = root.querySelector('.{$prefix}-nav-next');
    if (!container || !prev || !next) return;
    function updateNav() {
        var sl = container.scrollLeft, sw = container.scrollWidth, cw = container.clientWidth;
        prev.classList.toggle('hidden', sl < 10);
        next.classList.toggle('hidden', sl + cw >= sw - 10);
    }
    prev.addEventListener('click', function() { container.scrollBy({left: -{$scrollAmount}, behavior: 'smooth'}); });
    next.addEventListener('click', function() { container.scrollBy({left: {$scrollAmount}, behavior: 'smooth'}); });
    container.addEventListener('scroll', updateNav);
    updateNav(); setTimeout(updateNav, 300);
})();
</script>
JS;

        return [
            'css' => $css,
            'js' => $js,
            'nav_prev' => $navPrev,
            'nav_next' => $navNext,
        ];
    }

    /**
     * 위젯 빈 상태 메시지 (다국어)
     *
     * @param string $messageKey 메시지 키 (i18n 배열의 키)
     * @param string $locale     현재 로케일
     * @param array  $messages   커스텀 메시지 배열 ['ko'=>'...', 'en'=>'...']
     * @return string HTML
     */
    function rzx_widget_empty(string $messageKey = 'no_data', string $locale = 'ko', array $messages = []): string
    {
        $defaults = [
            'no_data' => ['ko'=>'데이터가 없습니다.','en'=>'No data available.','ja'=>'データがありません。','zh_CN'=>'暂无数据。','zh_TW'=>'暫無資料。','de'=>'Keine Daten verfügbar.','es'=>'No hay datos.','fr'=>'Aucune donnée.','id'=>'Tidak ada data.','mn'=>'Мэдээлэл байхгүй.','ru'=>'Нет данных.','tr'=>'Veri yok.','vi'=>'Không có dữ liệu.'],
            'no_posts' => ['ko'=>'게시글이 없습니다.','en'=>'No posts yet.','ja'=>'投稿がありません。','zh_CN'=>'暂无帖子。','zh_TW'=>'暫無帖子。','de'=>'Noch keine Beiträge.','es'=>'No hay publicaciones.','fr'=>'Aucun article.','id'=>'Belum ada postingan.','mn'=>'Бичлэг байхгүй.','ru'=>'Записей нет.','tr'=>'Gönderi yok.','vi'=>'Chưa có bài viết.'],
            'no_board' => ['ko'=>'게시판을 선택해 주세요.','en'=>'Please select a board.','ja'=>'掲示板を選択してください。','zh_CN'=>'请选择版块。','zh_TW'=>'請選擇版塊。','de'=>'Bitte Board auswählen.','es'=>'Seleccione un tablero.','fr'=>'Sélectionnez un forum.','id'=>'Pilih papan.','mn'=>'Самбар сонгоно уу.','ru'=>'Выберите доску.','tr'=>'Pano seçin.','vi'=>'Chọn bảng.'],
        ];

        $msgs = !empty($messages) ? $messages : ($defaults[$messageKey] ?? $defaults['no_data']);
        $text = $msgs[$locale] ?? $msgs['en'] ?? reset($msgs);

        return '<p class="text-sm text-zinc-400 dark:text-zinc-500 py-8 text-center">' . htmlspecialchars($text) . '</p>';
    }
}
