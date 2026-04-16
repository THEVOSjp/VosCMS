<?php
/**
 * 관리자 서비스 상세 — 부가서비스 탭
 * $subs: addon 타입 구독 배열
 */
?>
<div class="divide-y divide-gray-100 dark:divide-zinc-700/50">
    <?php foreach ($subs as $sub):
        $sst = $statusLabels[$sub['status']] ?? ['알 수 없음', 'bg-gray-100 text-gray-500'];
        $sc = $sub['service_class'] ?? 'recurring';
        $meta = json_decode($sub['metadata'] ?? '{}', true) ?: [];
        $isOneTime = $sc === 'one_time';
        $currentOneTimeStatus = !empty($sub['completed_at']) ? 'completed' : $sub['status'];
        $adminMemo = $meta['admin_memo'] ?? '';
    ?>
    <div class="px-5 py-4" id="sub_<?= $sub['id'] ?>">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-2 flex-1 min-w-0">
                <p class="text-sm font-semibold text-zinc-900 dark:text-white truncate"><?= htmlspecialchars($sub['label']) ?></p>
                <span class="text-[10px] px-1.5 py-0.5 rounded-full font-medium <?= $sst[1] ?>"><?= $sst[0] ?></span>
                <?php if ($isOneTime): ?>
                <span class="text-[10px] px-1.5 py-0.5 bg-amber-50 text-amber-600 rounded-full">1회성</span>
                <?php elseif ($sc === 'free'): ?>
                <span class="text-[10px] px-1.5 py-0.5 bg-green-50 text-green-600 rounded-full">무료</span>
                <?php endif; ?>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <?php if ($isOneTime): ?>
                <?php
                    $otColors = ['pending'=>'blue','active'=>'amber','suspended'=>'zinc','cancelled'=>'red','completed'=>'green'];
                    $otLabels = ['pending'=>'접수','active'=>'진행','suspended'=>'보류','cancelled'=>'취소','completed'=>'완료'];
                    $otColor = $otColors[$currentOneTimeStatus] ?? 'zinc';
                    $otLabel = $otLabels[$currentOneTimeStatus] ?? '알 수 없음';
                ?>
                <button onclick="openStatusModal(<?= $sub['id'] ?>, '<?= $currentOneTimeStatus ?>', '<?= htmlspecialchars($sub['label']) ?>')"
                        class="text-xs px-2.5 py-1 bg-<?= $otColor ?>-50 text-<?= $otColor ?>-600 dark:bg-<?= $otColor ?>-900/20 dark:text-<?= $otColor ?>-400 rounded-lg hover:ring-2 hover:ring-<?= $otColor ?>-300 transition cursor-pointer"><?= $otLabel ?></button>
                <?php else: ?>
                <span class="text-xs text-zinc-400"><?= (int)$sub['billing_amount'] > 0 ? $fmtPrice($sub['billing_amount'], $sub['currency']) : '무료' ?></span>
                <?php if ($sub['auto_renew']): ?>
                <span class="text-[10px] px-1.5 py-0.5 bg-blue-50 text-blue-600 rounded-full">자동연장</span>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php if (!$isOneTime): ?>
        <p class="text-[10px] text-zinc-400 mt-1"><?= date('Y-m-d', strtotime($sub['started_at'])) ?> ~ <?= date('Y-m-d', strtotime($sub['expires_at'])) ?></p>
        <?php endif; ?>
        <?php if (!empty($sub['completed_at'])): ?>
        <p class="text-[10px] text-green-600 mt-1">완료: <?= date('Y-m-d H:i', strtotime($sub['completed_at'])) ?></p>
        <?php endif; ?>
        <?php if (!empty($meta['quote_required'])): ?>
        <p class="text-[10px] text-amber-500 mt-1">별도 견적</p>
        <?php endif; ?>

        <!-- 관리자 메모 -->
        <div class="mt-2">
            <div class="flex items-center gap-2">
                <textarea id="memo_<?= $sub['id'] ?>" rows="2" placeholder="관리자 메모 (진행 상황, 견적 내용 등)"
                    class="flex-1 px-3 py-1.5 text-xs border border-zinc-200 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg resize-none"><?= htmlspecialchars($adminMemo) ?></textarea>
                <button onclick="saveMemo(<?= $sub['id'] ?>)" class="px-3 py-1.5 text-[10px] font-medium text-blue-600 border border-blue-200 rounded-lg hover:bg-blue-50 shrink-0">저장</button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<script>
function saveMemo(subId) {
    var memo = document.getElementById('memo_' + subId).value;
    ajaxPost({ action: 'update_addon_memo', subscription_id: subId, memo: memo })
        .then(function(d) { if (d.success) alert('메모가 저장되었습니다.'); else alert(d.message || '저장 실패'); });
}
</script>
