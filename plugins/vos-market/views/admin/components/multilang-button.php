<?php
/**
 * 통합 다국어 버튼 컴포넌트 — voscms rzxlib 공용 버전을 market 용으로 이관.
 *
 * 글로브 아이콘 하나로 통일. 아이콘 변경 시 이 파일의 SVG 만 수정하면 전체 적용.
 *
 * PHP 사용법:
 *   <?= rzx_multilang_btn("openMultilangModal('name_json','name_display')") ?>
 *
 * JS 함수 출력 (페이지 하단에 한 번):
 *   <?= rzx_multilang_btn_js() ?>
 *
 * JS 사용법 (동적 생성):
 *   var btn = RZX_MULTILANG_BTN('openMultilangModal(...)');
 */

if (!function_exists('rzx_multilang_btn')) {
    // 글로브 SVG — 아이콘 변경 시 이 한 줄만 수정
    define('RZX_MULTILANG_SVG', '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>');

    /**
     * 통합 다국어 버튼 HTML 반환
     */
    function rzx_multilang_btn(string $onclick, string $title = ''): string
    {
        if ($title === '') {
            $title = __('admin.common.multilang') ?: '다국어';
        }
        $titleAttr = htmlspecialchars($title, ENT_QUOTES);
        return '<button type="button" onclick="' . $onclick . '" class="rzx-multilang-btn p-1.5 text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/30 rounded-lg transition" title="' . $titleAttr . '">'
             . RZX_MULTILANG_SVG
             . '</button>';
    }

    /**
     * JS 용 RZX_MULTILANG_BTN 함수 출력 (script 태그 포함).
     * 페이지 HTML 영역에서 한 번만 호출.
     */
    function rzx_multilang_btn_js(): string
    {
        $title = __('admin.common.multilang') ?: '다국어';
        return '<script>
function RZX_MULTILANG_BTN(onclick, title) {
    title = title || ' . json_encode($title) . ';
    return \'<button type="button" onclick="\' + onclick.replace(/"/g, \'&quot;\') + \'" class="rzx-multilang-btn p-1.5 text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/30 rounded-lg transition" title="\' + title + \'">\'
         + ' . json_encode(RZX_MULTILANG_SVG) . '
         + \'</button>\';
}
</script>';
    }
}
