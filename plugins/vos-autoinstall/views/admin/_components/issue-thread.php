<?php
/**
 * 이슈/Q&A 스레드 한 건 (제목 + 본문 + 답변 펼침)
 * 필요 변수: $is (이슈 행, replies 포함)
 */
$_isVerified = !empty($is['is_verified']);
$_status = $is['status'] ?? 'open';
$_statusBadge = [
    'open'     => ['열림',   'bg-rose-50 text-rose-700 dark:bg-rose-900/20 dark:text-rose-400'],
    'closed'   => ['닫힘',   'bg-zinc-100 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400'],
    'resolved' => ['해결됨', 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-400'],
][$_status] ?? ['?', 'bg-zinc-100 text-zinc-500'];
$_replies = $is['replies'] ?? [];
?>
<details class="border border-zinc-200 dark:border-zinc-700 rounded-lg group">
    <summary class="cursor-pointer p-3 list-none select-none flex items-start gap-3 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
        <svg class="w-4 h-4 mt-0.5 text-zinc-400 transition-transform group-open:rotate-90 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-xs font-medium px-1.5 py-0.5 rounded <?= $_statusBadge[1] ?>"><?= $_statusBadge[0] ?></span>
                <h4 class="text-sm font-medium text-zinc-800 dark:text-zinc-200 truncate"><?= htmlspecialchars($is['title']) ?></h4>
            </div>
            <div class="flex items-center gap-2 mt-1 text-xs text-zinc-500 dark:text-zinc-400 flex-wrap">
                <?php if (!empty($is['author_name'])): ?>
                <span class="font-medium text-zinc-600 dark:text-zinc-300"><?= htmlspecialchars($is['author_name']) ?></span>
                <?php endif; ?>
                <?php if (!empty($is['author_domain'])): ?>
                <span class="font-mono"><?= htmlspecialchars($is['author_domain']) ?></span>
                <?php endif; ?>
                <?php if ($_isVerified): ?>
                <span class="text-emerald-600 dark:text-emerald-400 font-medium"><?= __('autoinstall.verified_purchase') ?></span>
                <?php endif; ?>
                <span class="ml-auto"><?= !empty($is['created_at']) ? date('Y-m-d H:i', strtotime($is['created_at'])) : '' ?></span>
                <?php if (!empty($_replies)): ?>
                <span class="inline-flex items-center gap-1 text-zinc-500">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    <?= count($_replies) ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
    </summary>
    <div class="border-t border-zinc-100 dark:border-zinc-700 p-4 space-y-4">
        <?php if (!empty($is['body'])): ?>
        <p class="text-sm text-zinc-700 dark:text-zinc-300 whitespace-pre-line"><?= htmlspecialchars($is['body']) ?></p>
        <?php endif; ?>

        <?php if (!empty($_replies)): ?>
        <div class="space-y-3 pl-4 border-l-2 border-zinc-100 dark:border-zinc-700">
            <?php foreach ($_replies as $rep): ?>
            <div>
                <div class="flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400 flex-wrap mb-1">
                    <?php if (!empty($rep['author_name'])): ?>
                    <span class="font-medium text-zinc-600 dark:text-zinc-300"><?= htmlspecialchars($rep['author_name']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($rep['author_domain'])): ?>
                    <span class="font-mono"><?= htmlspecialchars($rep['author_domain']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($rep['is_partner_reply'])): ?>
                    <span class="text-indigo-600 dark:text-indigo-400 font-medium"><?= __('autoinstall.partner_reply') ?></span>
                    <?php elseif (!empty($rep['is_verified'])): ?>
                    <span class="text-emerald-600 dark:text-emerald-400 font-medium"><?= __('autoinstall.verified_purchase') ?></span>
                    <?php endif; ?>
                    <span class="ml-auto"><?= !empty($rep['created_at']) ? date('Y-m-d H:i', strtotime($rep['created_at'])) : '' ?></span>
                </div>
                <p class="text-sm text-zinc-700 dark:text-zinc-300 whitespace-pre-line"><?= htmlspecialchars($rep['body']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <button type="button" onclick="mpOpenIssueReply(<?= (int)$is['id'] ?>, <?= htmlspecialchars(json_encode($is['title']), ENT_QUOTES) ?>)"
                class="text-xs px-3 py-1.5 rounded border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-700 inline-flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
            <?= __('autoinstall.reply') ?>
        </button>
    </div>
</details>
