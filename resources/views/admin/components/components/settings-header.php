<?php
/**
 * Settings Section Header Component
 * 설정 페이지 섹션 헤더 공통 컴포넌트
 *
 * Required variables:
 * - $headerTitle: string - 섹션 제목
 *
 * Optional variables:
 * - $headerDescription: string - 섹션 설명
 * - $headerIcon: string - SVG path d 속성값
 * - $headerIconColor: string - 아이콘 색상 Tailwind 클래스 (기본: text-zinc-600 dark:text-zinc-400)
 * - $headerActions: string - 우측 액션 버튼 HTML
 */

$_iconColor = !empty($headerIconColor) ? $headerIconColor : 'text-zinc-600 dark:text-zinc-400';
?>
<div class="flex items-center justify-between mb-4">
    <div>
        <div class="flex items-center">
            <?php if (!empty($headerIcon)): ?>
            <svg class="w-5 h-5 <?= $_iconColor ?> mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $headerIcon ?>"/>
            </svg>
            <?php endif; ?>
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white"><?= $headerTitle ?></h2>
        </div>
        <?php if (!empty($headerDescription)): ?>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1 <?= !empty($headerIcon) ? 'ml-7' : '' ?>"><?= $headerDescription ?></p>
        <?php endif; ?>
    </div>
    <?php if (!empty($headerActions)): ?>
    <div class="flex items-center space-x-2">
        <?= $headerActions ?>
    </div>
    <?php endif; ?>
</div>
