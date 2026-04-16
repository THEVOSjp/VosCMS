<?php
/**
 * 서비스 관리 파셜 — 메일
 * 기본 메일/비즈니스 메일 모두 호스팅 탭에서 통합 관리.
 * 이 탭은 호스팅이 없는 경우 또는 독립 메일 구독만 있을 때 표시.
 */
$allAccounts = [];
foreach ($subs as $sub) {
    $meta = json_decode($sub['metadata'] ?? '{}', true) ?: [];
    $isBiz = stripos($sub['label'], '비즈니스') !== false || stripos($sub['label'], 'ビジネス') !== false;
    foreach ($meta['mail_accounts'] ?? [] as $ma) {
        $allAccounts[] = [
            'address' => $ma['address'] ?? '',
            'type' => $isBiz ? 'business' : 'basic',
            'type_label' => $isBiz ? '비즈니스 메일' : '기본 메일',
            'sub_id' => $sub['id'],
        ];
    }
}
$firstSub = $subs[0];
$fst = $statusLabels[$firstSub['status']] ?? ['알 수 없음', 'bg-gray-100 text-gray-500'];
$fsc = $firstSub['service_class'] ?? 'recurring';
?>

<div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100 dark:border-zinc-700 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <h3 class="text-sm font-bold text-zinc-900 dark:text-white">메일 계정 관리</h3>
            <span class="text-[10px] px-2 py-0.5 rounded-full font-medium <?= $fst[1] ?>"><?= $fst[0] ?></span>
            <span class="text-xs text-zinc-400"><?= count($allAccounts) ?>개</span>
        </div>
        <?php if ($fsc === 'recurring' && $firstSub['status'] === 'active'): ?>
        <div class="flex items-center gap-2">
            <span class="text-xs text-zinc-400">자동연장</span>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" class="sr-only peer" <?= $firstSub['auto_renew'] ? 'checked' : '' ?>
                       onchange="serviceAction('toggle_auto_renew',{subscription_id:<?= $firstSub['id'] ?>,auto_renew:this.checked})">
                <div class="w-9 h-5 bg-zinc-200 rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
            </label>
        </div>
        <?php endif; ?>
    </div>

    <div class="p-5">
        <?php if (!empty($allAccounts)): ?>
        <div class="space-y-2">
            <?php foreach ($allAccounts as $acc):
                $isBiz = $acc['type'] === 'business';
                $colorClass = $isBiz ? 'amber' : 'green';
            ?>
            <div class="flex items-center justify-between p-3 bg-<?= $colorClass ?>-50/50 dark:bg-<?= $colorClass ?>-900/10 rounded-lg">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-8 h-8 rounded-full bg-<?= $colorClass ?>-100 dark:bg-<?= $colorClass ?>-900/30 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 text-<?= $colorClass ?>-600 dark:text-<?= $colorClass ?>-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-mono font-medium text-zinc-900 dark:text-white truncate"><?= htmlspecialchars($acc['address']) ?></p>
                        <p class="text-[10px] text-<?= $colorClass ?>-600 dark:text-<?= $colorClass ?>-400"><?= $acc['type_label'] ?></p>
                    </div>
                </div>
                <div class="flex items-center gap-1.5 shrink-0">
                    <button onclick="togglePasswordChange(this)" class="text-[10px] px-2.5 py-1.5 text-zinc-500 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-600 rounded-lg hover:border-<?= $colorClass ?>-400 hover:text-<?= $colorClass ?>-600 transition">비밀번호 변경</button>
                    <button disabled class="text-[10px] px-2.5 py-1.5 text-zinc-400 border border-zinc-200 dark:border-zinc-600 rounded-lg cursor-not-allowed opacity-50" title="준비중">삭제</button>
                </div>
            </div>
            <div class="pw-change-form hidden ml-11 p-3 bg-<?= $colorClass ?>-50 dark:bg-<?= $colorClass ?>-900/10 border border-<?= $colorClass ?>-200 dark:border-<?= $colorClass ?>-800 rounded-lg">
                <div class="flex items-end gap-2">
                    <div class="flex-1">
                        <label class="text-[10px] text-zinc-500 block mb-1">새 비밀번호</label>
                        <input type="password" class="pw-new w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg" placeholder="8자 이상">
                    </div>
                    <div class="flex-1">
                        <label class="text-[10px] text-zinc-500 block mb-1">비밀번호 확인</label>
                        <input type="password" class="pw-confirm w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg" placeholder="다시 입력">
                    </div>
                    <button onclick="changeMailPassword(this,'<?= htmlspecialchars($acc['address']) ?>',<?= $acc['sub_id'] ?>)"
                            class="px-4 py-2 text-xs font-medium text-white bg-<?= $colorClass ?>-600 rounded-lg hover:bg-<?= $colorClass ?>-700 transition whitespace-nowrap">변경</button>
                    <button onclick="this.closest('.pw-change-form').classList.add('hidden')"
                            class="px-3 py-2 text-xs text-zinc-400 hover:text-zinc-600 transition">취소</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-xs text-zinc-400">메일 계정 정보가 없습니다.</p>
        <?php endif; ?>

        <div class="pt-3 mt-3 border-t border-gray-100 dark:border-zinc-700 flex flex-wrap gap-2">
            <button disabled class="px-3 py-1.5 text-xs text-zinc-400 border border-zinc-200 dark:border-zinc-600 rounded-lg cursor-not-allowed opacity-50" title="준비중">계정 추가</button>
        </div>
    </div>
</div>
