<?php
/**
 * RezlyX - 관리자 아이콘 공통 함수
 * 페이지/게시판 제목 옆에 설정/편집 아이콘 출력
 */

/**
 * 관리자 설정/편집 아이콘 HTML 반환
 * @param string $settingsUrl 설정 페이지 URL
 * @param string $editUrl 편집 페이지 URL (빈 문자열이면 미표시)
 * @return string HTML
 */
function rzx_admin_icons($settingsUrl = '', $editUrl = '') {
    $isAdmin = !empty($_SESSION['admin_id']);
    if (!$isAdmin) return '';

    $html = '';
    if ($settingsUrl) {
        $title = __('common.page_settings') ?? '페이지 설정';
        $html .= '<a href="' . htmlspecialchars($settingsUrl) . '" class="text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 transition" title="' . $title . '"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg></a>';
    }
    if ($editUrl) {
        $title = __('common.edit') ?? '편집';
        if ($editUrl === 'coming_soon') {
            $comingSoon = __('common.msg.coming_soon') ?? '기능 준비중입니다.';
            $html .= '<a href="javascript:void(0)" onclick="alert(\'' . htmlspecialchars($comingSoon, ENT_QUOTES) . '\')" class="text-zinc-400 hover:text-amber-500 dark:hover:text-amber-400 transition" title="' . $title . '"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></a>';
        } else {
            $html .= '<a href="' . htmlspecialchars($editUrl) . '" class="text-zinc-400 hover:text-green-600 dark:hover:text-green-400 transition" title="' . $title . '"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></a>';
        }
    }
    return $html;
}
