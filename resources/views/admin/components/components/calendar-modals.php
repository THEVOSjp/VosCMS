<?php
/**
 * 캘린더 공용 모달 컴포넌트
 * - 예약 상세 모달 (상태 변경 포함)
 * - 예약 추가 모달 (reservation-form 컴포넌트 재사용, 풀사이즈)
 *
 * 필수 변수: $cal 배열 (adminUrl, csrfToken, services, currencySymbol, currencyPosition)
 */
?>

<!-- 예약 상세 모달 (상태 변경 포함) -->
<div id="rzxCalDetailModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4" onclick="rzxCalCloseDetail(event)">
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-2xl w-full max-w-md overflow-hidden" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between p-4 border-b border-zinc-200 dark:border-zinc-700">
            <h3 id="rzxCalDetailTitle" class="text-base font-bold text-zinc-900 dark:text-white"></h3>
            <button onclick="rzxCalCloseDetail()" class="p-1 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg">
                <svg class="w-5 h-5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div id="rzxCalDetailBody" class="p-4"></div>
        <div id="rzxCalDetailActions" class="px-4 pb-4 flex gap-2"></div>
    </div>
</div>

<!-- 예약 추가 모달 (풀사이즈 + 서비스 카드 다중선택) -->
<div id="rzxCalAddModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4" onclick="rzxCalCloseAdd(event)">
    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl w-full max-w-5xl max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
        <!-- 모달 헤더 -->
        <div class="flex items-center justify-between p-5 border-b border-zinc-200 dark:border-zinc-700 sticky top-0 bg-white dark:bg-zinc-800 z-10 rounded-t-2xl">
            <h3 class="text-lg font-bold text-zinc-900 dark:text-white flex items-center">
                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <?= __('reservations.cal_add') ?>
            </h3>
            <button onclick="rzxCalCloseAdd()" class="p-1.5 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition">
                <svg class="w-5 h-5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <!-- 공용 예약 폼 컴포넌트 -->
        <div class="p-5">
            <?php
            $resForm = [
                'services'         => $cal['services'] ?? [],
                'bundles'          => $cal['bundles'] ?? [],
                'adminUrl'         => $cal['adminUrl'],
                'csrfToken'        => $cal['csrfToken'],
                'currencySymbol'   => $cal['currencySymbol'],
                'currencyPosition' => $cal['currencyPosition'],
                'formId'           => 'rzxCalAddForm',
                'mode'             => 'modal',
                'defaultDate'      => date('Y-m-d'),
                'old'              => [],
            ];
            include __DIR__ . '/reservation-form.php';
            ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/reservation-form-js.php'; ?>
