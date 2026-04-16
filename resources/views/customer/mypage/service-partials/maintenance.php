<?php
/**
 * 마이페이지 서비스 관리 — 유지보수 탭
 */
$sub = $subs[0];
$st = $statusLabels[$sub['status']] ?? ['알 수 없음', 'bg-gray-100 text-gray-500'];
$sc = $sub['service_class'] ?? 'recurring';
$meta = json_decode($sub['metadata'] ?? '{}', true) ?: [];
?>

<div class="space-y-4">
    <!-- 유지보수 정보 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 dark:border-zinc-700 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <h3 class="text-sm font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars($sub['label']) ?></h3>
                <span class="text-[10px] px-2 py-0.5 rounded-full font-medium <?= $st[1] ?>"><?= $st[0] ?></span>
            </div>
            <?php if ($sc === 'recurring' && $sub['status'] === 'active'): ?>
            <div class="flex items-center gap-2">
                <span class="text-xs text-zinc-400">자동연장</span>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" class="sr-only peer" <?= $sub['auto_renew'] ? 'checked' : '' ?>
                           onchange="serviceAction('toggle_auto_renew',{subscription_id:<?= $sub['id'] ?>,auto_renew:this.checked})">
                    <div class="w-9 h-5 bg-zinc-200 rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                </label>
            </div>
            <?php elseif ($sc === 'free' && $sub['status'] === 'active'): ?>
            <button onclick="serviceAction('request_renewal',{subscription_id:<?= $sub['id'] ?>}).then(function(d){alert(d.message||'신청 완료')})"
                    class="text-xs px-3 py-1.5 bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400 rounded-lg hover:bg-blue-100 transition">연장 신청</button>
            <?php endif; ?>
        </div>
        <div class="p-5">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                <div>
                    <p class="text-[10px] text-zinc-400 uppercase tracking-wider mb-0.5">기간</p>
                    <p class="font-medium text-zinc-700 dark:text-zinc-300"><?= date('Y-m-d', strtotime($sub['started_at'])) ?> ~ <?= date('Y-m-d', strtotime($sub['expires_at'])) ?></p>
                </div>
                <div>
                    <p class="text-[10px] text-zinc-400 uppercase tracking-wider mb-0.5">월 요금</p>
                    <p class="font-medium text-zinc-700 dark:text-zinc-300"><?= (int)$sub['unit_price'] > 0 ? $fmtPrice($sub['unit_price'], $sub['currency']) . '/월' : '무료' ?></p>
                </div>
                <div>
                    <p class="text-[10px] text-zinc-400 uppercase tracking-wider mb-0.5">다음 점검 예정일</p>
                    <p class="font-medium text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars($meta['next_check'] ?? '-') ?></p>
                </div>
                <div>
                    <p class="text-[10px] text-zinc-400 uppercase tracking-wider mb-0.5">최근 점검일</p>
                    <p class="font-medium text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars($meta['last_check'] ?? '-') ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- 작업 이력 (관리자 기록이 있는 경우에만 표시) -->
    <?php
    $maintLogs = $meta['work_logs'] ?? [];
    if (!empty($maintLogs)):
    ?>
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 p-5">
        <p class="text-[10px] font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-3">작업 이력</p>
        <div class="space-y-1.5">
            <?php foreach ($maintLogs as $log): ?>
            <div class="flex items-start gap-3 px-3 py-2 bg-gray-50 dark:bg-zinc-700/30 rounded text-xs">
                <span class="text-zinc-400 w-20 shrink-0"><?= htmlspecialchars($log['date'] ?? '') ?></span>
                <span class="text-zinc-400 w-16 shrink-0"><?= htmlspecialchars($log['type'] ?? '') ?></span>
                <span class="text-zinc-800 dark:text-zinc-200 flex-1"><?= htmlspecialchars($log['content'] ?? '') ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
