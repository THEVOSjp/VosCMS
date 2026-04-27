<?php
/**
 * 이슈/Q&A 목록 컴포넌트 (마켓플레이스 어드민)
 * 필요 변수: $_threadList, $_threadEmpty, $replyMap
 */
?>
<?php if (empty($_threadList)): ?>
<div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-8 text-center text-sm text-zinc-400">
    <?= htmlspecialchars($_threadEmpty) ?>
</div>
<?php else: ?>
<?php foreach ($_threadList as $iss):
    $_statusBadge = [
        'open'     => ['열림',   'bg-rose-100 text-rose-700 dark:bg-rose-900/20 dark:text-rose-400'],
        'closed'   => ['닫힘',   'bg-zinc-100 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400'],
        'resolved' => ['해결됨', 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-400'],
    ][$iss['status']] ?? ['?', 'bg-zinc-100 text-zinc-500'];
    $_replies = $replyMap[(int)$iss['id']] ?? [];
?>
<details class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl group">
    <summary class="cursor-pointer p-4 list-none select-none flex items-start gap-3 hover:bg-zinc-50 dark:hover:bg-zinc-800/70 transition-colors">
        <svg class="w-4 h-4 mt-1 text-zinc-400 transition-transform group-open:rotate-90 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-xs font-medium px-1.5 py-0.5 rounded <?= $_statusBadge[1] ?>"><?= $_statusBadge[0] ?></span>
                <h4 class="text-sm font-medium text-zinc-800 dark:text-zinc-200 truncate"><?= htmlspecialchars($iss['title']) ?></h4>
            </div>
            <div class="flex items-center gap-2 mt-1 text-xs text-zinc-500 dark:text-zinc-400 flex-wrap">
                <?php if (!empty($iss['author_name'])): ?>
                <span class="font-medium text-zinc-600 dark:text-zinc-300"><?= htmlspecialchars($iss['author_name']) ?></span>
                <?php endif; ?>
                <?php if (!empty($iss['author_domain'])): ?>
                <span class="font-mono"><?= htmlspecialchars($iss['author_domain']) ?></span>
                <?php endif; ?>
                <?php if (!empty($iss['is_verified'])): ?>
                <span class="inline-flex items-center gap-0.5 text-emerald-600 dark:text-emerald-400 font-medium">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    구매 인증
                </span>
                <?php endif; ?>
                <span class="ml-auto"><?= htmlspecialchars(substr($iss['created_at'] ?? '', 0, 16)) ?></span>
                <?php if ((int)$iss['reply_count'] > 0): ?>
                <span class="inline-flex items-center gap-1 text-zinc-500">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    <?= (int)$iss['reply_count'] ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
    </summary>
    <div class="border-t border-zinc-100 dark:border-zinc-700 p-4 space-y-4">
        <?php if (!empty($iss['body'])): ?>
        <p class="text-sm text-zinc-700 dark:text-zinc-300 whitespace-pre-line"><?= htmlspecialchars($iss['body']) ?></p>
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
                    <span class="text-indigo-600 dark:text-indigo-400 font-medium">운영자</span>
                    <?php elseif (!empty($rep['is_verified'])): ?>
                    <span class="text-emerald-600 dark:text-emerald-400 font-medium">구매 인증</span>
                    <?php endif; ?>
                    <span class="ml-auto"><?= htmlspecialchars(substr($rep['created_at'] ?? '', 0, 16)) ?></span>
                    <button type="button" onclick="issueReplyDelete(<?= (int)$rep['id'] ?>)" class="text-red-500 hover:text-red-600 text-[11px]">삭제</button>
                </div>
                <p class="text-sm text-zinc-700 dark:text-zinc-300 whitespace-pre-line"><?= htmlspecialchars($rep['body']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- 운영자 답변 + 액션 -->
        <div class="pt-3 border-t border-zinc-100 dark:border-zinc-700 space-y-3">
            <div>
                <textarea id="rep_<?= (int)$iss['id'] ?>" rows="3" placeholder="운영자 답변 작성..."
                          class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                <button type="button" onclick="issueReplyAdd(<?= (int)$iss['id'] ?>)"
                        class="mt-2 text-xs px-3 py-1.5 rounded bg-indigo-600 hover:bg-indigo-700 text-white">답변 등록</button>
            </div>
            <div class="flex gap-2 flex-wrap">
                <?php if ($iss['status'] !== 'open'): ?>
                <button type="button" onclick="issueStatus(<?= (int)$iss['id'] ?>, 'open')" class="text-xs px-2.5 py-1 rounded border border-rose-300 dark:border-rose-700 text-rose-700 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-900/20">열기</button>
                <?php endif; ?>
                <?php if ($iss['status'] !== 'resolved'): ?>
                <button type="button" onclick="issueStatus(<?= (int)$iss['id'] ?>, 'resolved')" class="text-xs px-2.5 py-1 rounded border border-emerald-300 dark:border-emerald-700 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/20">해결됨</button>
                <?php endif; ?>
                <?php if ($iss['status'] !== 'closed'): ?>
                <button type="button" onclick="issueStatus(<?= (int)$iss['id'] ?>, 'closed')" class="text-xs px-2.5 py-1 rounded border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700">닫기</button>
                <?php endif; ?>
                <button type="button" onclick="issueDelete(<?= (int)$iss['id'] ?>)" class="text-xs px-2.5 py-1 rounded border border-red-300 dark:border-red-700 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20">삭제</button>
            </div>
        </div>
    </div>
</details>
<?php endforeach; ?>
<?php endif; ?>
