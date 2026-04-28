<?php
/**
 * 셋업 진행 중 모니터링 박스 (탭 영역 대체).
 *
 * 호스팅 결제 후 메일 도메인 자동 셋업이 완료되기 전까지 고객에게 노출.
 * mode === 'active' 가 되면 service-detail.php 에서 본 partial 대신 탭 노출.
 *
 * 외부 변수: $_provisionMode, $_provisionInfo
 */
$_stepLabel = __('services.detail.setup_step_payment');
if ($_provisionMode === 'new_pending') {
    $_stepLabel = __('services.detail.setup_step_domain_acquiring');
} elseif ($_provisionMode === 'existing_pending') {
    $_stepLabel = __('services.detail.setup_step_ns_propagation');
} elseif ($_provisionMode === 'pending' || $_provisionMode === null) {
    $_stepLabel = __('services.detail.setup_step_initializing');
}
?>
<div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800/50 rounded-xl px-6 py-6">
    <div class="flex items-start gap-4 mb-5">
        <div class="w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center flex-shrink-0">
            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div class="flex-1 min-w-0">
            <h3 class="text-base font-bold text-blue-900 dark:text-blue-300 mb-1.5"><?= htmlspecialchars(__('services.detail.setup_in_progress_title')) ?></h3>
            <p class="text-sm text-blue-800 dark:text-blue-400 leading-relaxed"><?= htmlspecialchars(__('services.detail.setup_in_progress_desc')) ?></p>
        </div>
    </div>

    <!-- 현재 단계 -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg px-4 py-3 mb-3">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs text-zinc-400 uppercase tracking-wider"><?= htmlspecialchars(__('services.detail.setup_current_step')) ?></span>
        </div>
        <p class="text-sm font-medium text-zinc-900 dark:text-white"><?= htmlspecialchars($_stepLabel) ?></p>
    </div>

    <!-- 단계 진행 바 (시각적 표시) -->
    <div class="flex items-center gap-1 mb-3">
        <?php
        $_steps = ['payment', 'domain', 'dns', 'mail', 'done'];
        $_currentStepIdx = 0;
        if ($_provisionMode === 'new_pending') $_currentStepIdx = 1;
        elseif ($_provisionMode === 'existing_pending') $_currentStepIdx = 2;
        elseif ($_provisionMode === 'active') $_currentStepIdx = 4;
        else $_currentStepIdx = 0;
        ?>
        <?php for ($i = 0; $i < count($_steps); $i++): ?>
        <div class="flex-1 h-2 rounded-full <?= $i <= $_currentStepIdx ? 'bg-blue-500 dark:bg-blue-400' : 'bg-zinc-200 dark:bg-zinc-700' ?>"></div>
        <?php endfor; ?>
    </div>

    <p class="text-xs text-blue-700 dark:text-blue-400 italic">💡 <?= htmlspecialchars(__('services.detail.setup_completion_notice')) ?></p>
</div>
