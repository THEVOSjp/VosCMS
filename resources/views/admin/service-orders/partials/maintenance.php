<?php
/**
 * 관리자 서비스 상세 — 유지보수 탭
 * $subs: maintenance 타입 구독 배열
 */
$sub = $subs[0];
$sst = $statusLabels[$sub['status']] ?? ['알 수 없음', 'bg-gray-100 text-gray-500'];
$sc = $sub['service_class'] ?? 'recurring';
$meta = json_decode($sub['metadata'] ?? '{}', true) ?: [];
?>

<!-- 유지보수 플랜 정보 -->
<div class="px-5 py-4 border-b border-gray-100 dark:border-zinc-700">
    <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-2">
            <p class="text-sm font-semibold text-zinc-900 dark:text-white"><?= htmlspecialchars($sub['label']) ?></p>
            <span class="text-[10px] px-1.5 py-0.5 rounded-full font-medium <?= $sst[1] ?>"><?= $sst[0] ?></span>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-xs text-zinc-400"><?= (int)$sub['billing_amount'] > 0 ? $fmtPrice($sub['billing_amount'], $sub['currency']) : '무료' ?></span>
            <?php if ($sub['auto_renew']): ?>
            <span class="text-[10px] px-1.5 py-0.5 bg-blue-50 text-blue-600 rounded-full">자동연장</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
        <div>
            <p class="text-[10px] text-zinc-400 mb-0.5">기간</p>
            <p class="font-medium text-zinc-800 dark:text-zinc-200"><?= date('Y-m-d', strtotime($sub['started_at'])) ?> ~ <?= date('Y-m-d', strtotime($sub['expires_at'])) ?></p>
        </div>
        <div>
            <p class="text-[10px] text-zinc-400 mb-0.5">월 요금</p>
            <p class="font-medium text-zinc-800 dark:text-zinc-200"><?= (int)$sub['unit_price'] > 0 ? $fmtPrice($sub['unit_price'], $sub['currency']) . '/월' : '무료' ?></p>
        </div>
        <div>
            <p class="text-[10px] text-zinc-400 mb-0.5">다음 점검 예정일</p>
            <p class="font-medium text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($meta['next_check'] ?? '-') ?></p>
        </div>
        <div>
            <p class="text-[10px] text-zinc-400 mb-0.5">최근 점검일</p>
            <p class="font-medium text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($meta['last_check'] ?? '-') ?></p>
        </div>
    </div>
</div>

<!-- 작업 이력 -->
<div class="px-5 py-4 border-b border-gray-100 dark:border-zinc-700">
    <p class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider mb-3">작업 이력</p>
    <?php
    $maintLogs = $meta['work_logs'] ?? [];
    if (empty($maintLogs)):
    ?>
    <p class="text-xs text-zinc-400 py-4 text-center">작업 이력이 없습니다.</p>
    <?php else: ?>
    <div class="space-y-1.5">
        <?php foreach ($maintLogs as $log): ?>
        <div class="flex items-start gap-3 px-3 py-2 bg-gray-50 dark:bg-zinc-700/30 rounded text-xs">
            <span class="text-zinc-400 w-20 shrink-0"><?= htmlspecialchars($log['date'] ?? '') ?></span>
            <span class="text-zinc-400 w-16 shrink-0"><?= htmlspecialchars($log['type'] ?? '') ?></span>
            <span class="text-zinc-800 dark:text-zinc-200 flex-1"><?= htmlspecialchars($log['content'] ?? '') ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- 관리 버튼 -->
<div class="px-5 py-4 flex flex-wrap gap-2">
    <button disabled class="px-3 py-1.5 text-xs text-zinc-400 border border-zinc-200 rounded-lg cursor-not-allowed opacity-50" title="준비중">플랜 변경</button>
    <button disabled class="px-3 py-1.5 text-xs text-zinc-400 border border-zinc-200 rounded-lg cursor-not-allowed opacity-50" title="준비중">작업 기록 추가</button>
    <button disabled class="px-3 py-1.5 text-xs text-zinc-400 border border-zinc-200 rounded-lg cursor-not-allowed opacity-50" title="준비중">유지보수 리포트 발송</button>
</div>
