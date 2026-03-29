<?php
/**
 * 다국어 입력 필드 컴포넌트
 * input/textarea 내부 오른쪽에 지구본 아이콘 배치
 *
 * 사용법:
 *   <?php rzx_multilang_input('title', $value, 'board.1.title'); ?>
 *   <?php rzx_multilang_input('description', $value, 'board.1.description', ['type' => 'textarea', 'rows' => 3]); ?>
 *   <?php rzx_multilang_input('header', $value, 'board.1.header', ['type' => 'textarea', 'rows' => 3, 'modal_type' => 'editor']); ?>
 *   <?php rzx_multilang_input('title', $value, 'board.1.title', ['required' => true, 'placeholder' => '제목']); ?>
 */

if (!function_exists('rzx_multilang_input')) {
    /**
     * 다국어 입력 필드 (input 또는 textarea) + 지구본 버튼
     *
     * @param string $name       input name 및 id
     * @param string $value      현재 값
     * @param string $langKey    다국어 키 (예: 'board.1.title')
     * @param array  $opts       옵션:
     *   'type'        => 'text'|'textarea' (기본: 'text')
     *   'rows'        => textarea 행 수 (기본: 3)
     *   'required'    => bool (기본: false)
     *   'placeholder' => string
     *   'class'       => 추가 CSS 클래스
     *   'modal_type'  => 'text'|'editor' (기본: 'text') - multilang 모달 타입
     *   'attrs'       => 추가 HTML 속성 문자열
     */
    function rzx_multilang_input(string $name, string $value, string $langKey, array $opts = []): void
    {
        $type       = $opts['type'] ?? 'text';
        $rows       = (int)($opts['rows'] ?? 3);
        $required   = !empty($opts['required']) ? 'required' : '';
        $placeholder = isset($opts['placeholder']) ? htmlspecialchars($opts['placeholder'], ENT_QUOTES) : '';
        $extraClass = $opts['class'] ?? '';
        $modalType  = $opts['modal_type'] ?? '';
        $attrs      = $opts['attrs'] ?? '';
        $safeValue  = htmlspecialchars($value, ENT_QUOTES);

        // 모달 onclick
        $modalArg = $modalType ? ", '{$modalType}'" : '';
        $onclick  = "openMultilangModal('{$langKey}', '{$name}'{$modalArg})";

        // 공통 input 클래스
        $baseClass = 'w-full text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200 placeholder-zinc-400';

        // 지구본 SVG (multilang-button.php 정의 재사용)
        $svg = defined('RZX_MULTILANG_SVG') ? RZX_MULTILANG_SVG : '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>';

        $btnTitle = function_exists('__') ? htmlspecialchars(__('admin.common.multilang'), ENT_QUOTES) : '다국어';
        $btnHtml  = '<button type="button" onclick="' . $onclick . '" class="absolute text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 transition" title="' . $btnTitle . '">' . $svg . '</button>';

        if ($type === 'textarea') {
            echo '<div class="relative">';
            echo "<textarea name=\"{$name}\" id=\"{$name}\" rows=\"{$rows}\" {$required}"
               . ($placeholder ? " placeholder=\"{$placeholder}\"" : '')
               . " class=\"{$baseClass} px-3 py-2 pr-8 {$extraClass}\" {$attrs}>{$safeValue}</textarea>";
            // 버튼: 우측 상단
            echo str_replace('class="absolute', 'class="absolute right-2 top-2', $btnHtml);
            echo '</div>';
        } else {
            echo '<div class="relative">';
            echo "<input type=\"text\" name=\"{$name}\" id=\"{$name}\" value=\"{$safeValue}\" {$required}"
               . ($placeholder ? " placeholder=\"{$placeholder}\"" : '')
               . " class=\"{$baseClass} px-3 py-2 pr-8 {$extraClass}\" {$attrs}>";
            // 버튼: 우측 중앙
            echo str_replace('class="absolute', 'class="absolute right-2 top-1/2 -translate-y-1/2', $btnHtml);
            echo '</div>';
        }
    }
}
