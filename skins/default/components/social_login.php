<?php
/**
 * Social Login Component
 * Displays social login buttons (Google, Kakao, LINE, etc.)
 */
if (empty($socialProviders) || !is_array($socialProviders)) return;
?>
<div class="mt-6">
    <div class="relative">
        <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-zinc-300 dark:border-zinc-600"></div></div>
        <div class="relative flex justify-center text-sm">
            <span class="px-2 bg-white dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400"><?= $translations['or_social'] ?? 'SNS 계정으로 계속하기' ?></span>
        </div>
    </div>
    <div class="mt-4 flex flex-col gap-2">
        <?php if (!empty($socialProviders['google'])): ?>
        <a href="<?= htmlspecialchars($baseUrl ?? '') ?>/auth/google" class="flex items-center justify-center gap-2 px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition text-sm font-medium text-zinc-700 dark:text-zinc-300">
            <svg class="w-5 h-5" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
            Google
        </a>
        <?php endif; ?>
        <?php if (!empty($socialProviders['kakao'])): ?>
        <a href="<?= htmlspecialchars($baseUrl ?? '') ?>/auth/kakao" class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg hover:opacity-90 transition text-sm font-medium" style="background:#FEE500;color:#191919;">
            <svg class="w-5 h-5" viewBox="0 0 24 24"><path d="M12 3C6.48 3 2 6.48 2 10.5c0 2.58 1.72 4.85 4.3 6.14-.14.51-.9 3.28-.93 3.5 0 0-.02.16.08.22.1.06.22.01.22.01.29-.04 3.37-2.2 3.9-2.57.78.12 1.59.18 2.43.18 5.52 0 10-3.48 10-7.48S17.52 3 12 3z" fill="currentColor"/></svg>
            Kakao
        </a>
        <?php endif; ?>
        <?php if (!empty($socialProviders['line'])): ?>
        <a href="<?= htmlspecialchars($baseUrl ?? '') ?>/auth/line" class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg hover:opacity-90 transition text-sm font-medium text-white" style="background:#06C755;">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 5.82 2 10.5c0 4.21 3.74 7.74 8.78 8.4.34.07.81.23.93.52.1.27.07.68.03.95l-.15.9c-.05.27-.2 1.07.94.58 1.14-.49 6.14-3.62 8.38-6.2C22.74 13.5 22 11.5 22 10.5 22 5.82 17.52 2 12 2z"/></svg>
            LINE
        </a>
        <?php endif; ?>
    </div>
</div>
