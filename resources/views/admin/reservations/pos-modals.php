<!-- 상세/상태변경 모달 -->
<div id="posDetailModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4" onclick="POS.closeDetail(event)">
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-2xl w-full max-w-md overflow-hidden" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between p-4 border-b border-zinc-200 dark:border-zinc-700">
            <h3 id="posDetailTitle" class="text-base font-bold text-zinc-900 dark:text-white"></h3>
            <button onclick="POS.closeDetail()" class="p-1 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg">
                <svg class="w-5 h-5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div id="posDetailBody" class="p-4"></div>
        <div id="posDetailActions" class="px-4 pb-4 flex gap-2"></div>
    </div>
</div>

<!-- 당일 접수 모달 (예약 접수 컴포넌트 재사용) -->
<div id="posCheckinModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4" onclick="POS.closeCheckinModal(event)">
    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl w-full max-w-5xl max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between p-5 border-b border-zinc-200 dark:border-zinc-700 sticky top-0 bg-white dark:bg-zinc-800 z-10 rounded-t-2xl">
            <h3 class="text-lg font-bold text-zinc-900 dark:text-white flex items-center">
                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <?= __('reservations.pos_tab_checkin') ?>
            </h3>
            <button onclick="POS.closeCheckinModal()" class="p-1.5 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition">
                <svg class="w-5 h-5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="p-5">
            <?php
            $resForm = [
                'services'         => $calServices,
                'adminUrl'         => $adminUrl,
                'csrfToken'        => $csrfToken,
                'currencySymbol'   => $currencySymbol,
                'currencyPosition' => $currencyPosition,
                'formId'           => 'posCheckinForm',
                'mode'             => 'modal',
                'defaultDate'      => $today,
                'source'           => 'walk_in',
                'old'              => [],
            ];
            include BASE_PATH . '/resources/views/admin/components/reservation-form.php';
            ?>
        </div>
    </div>
</div>

<!-- 결제 모달 -->
<div id="posPaymentModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4" onclick="POS.closePayment(event)">
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-2xl w-full max-w-sm overflow-hidden" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between p-4 border-b border-zinc-200 dark:border-zinc-700">
            <h3 class="text-base font-bold text-zinc-900 dark:text-white flex items-center">
                <svg class="w-5 h-5 mr-2 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                <?= __('reservations.pos_btn_payment') ?>
            </h3>
            <button onclick="POS.closePayment()" class="p-1 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg">
                <svg class="w-5 h-5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="p-4 space-y-4">
            <input type="hidden" id="payReservationId">
            <!-- 서비스 상세 내역 -->
            <div id="payServiceDetails" class="space-y-1.5 max-h-40 overflow-y-auto"></div>
            <!-- 금액 요약 -->
            <div class="space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-zinc-500"><?= __('reservations.pos_pay_total') ?></span><span id="payTotalAmount" class="font-bold text-zinc-900 dark:text-white"></span></div>
                <div class="flex justify-between"><span class="text-zinc-500"><?= __('reservations.pos_pay_paid') ?></span><span id="payPaidAmount" class="font-medium text-emerald-600"></span></div>
                <div class="flex justify-between border-t border-zinc-200 dark:border-zinc-700 pt-2"><span class="font-bold text-zinc-900 dark:text-white"><?= __('reservations.pos_pay_remaining') ?></span><span id="payRemaining" class="font-bold text-lg text-violet-600"></span></div>
            </div>
            <!-- 결제 금액 -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('reservations.pos_pay_amount') ?></label>
                <input type="number" id="payAmount" min="0" step="1"
                       class="w-full h-12 px-4 text-lg font-bold border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-violet-500 focus:border-violet-500">
            </div>
            <!-- 결제 방법 -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('reservations.pos_pay_method') ?></label>
                <div class="grid grid-cols-3 gap-2">
                    <label class="cursor-pointer">
                        <input type="radio" name="pay_method_radio" value="card" checked class="sr-only peer" onchange="document.getElementById('payMethod').value='card'">
                        <div class="h-11 flex items-center justify-center rounded-lg border-2 border-zinc-200 dark:border-zinc-600 peer-checked:border-violet-500 peer-checked:bg-violet-50 dark:peer-checked:bg-violet-900/20 text-sm font-medium text-zinc-600 dark:text-zinc-300 peer-checked:text-violet-700 dark:peer-checked:text-violet-400 transition">
                            <?= __('reservations.pos_pay_card') ?>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="pay_method_radio" value="cash" class="sr-only peer" onchange="document.getElementById('payMethod').value='cash'">
                        <div class="h-11 flex items-center justify-center rounded-lg border-2 border-zinc-200 dark:border-zinc-600 peer-checked:border-violet-500 peer-checked:bg-violet-50 dark:peer-checked:bg-violet-900/20 text-sm font-medium text-zinc-600 dark:text-zinc-300 peer-checked:text-violet-700 dark:peer-checked:text-violet-400 transition">
                            <?= __('reservations.pos_pay_cash') ?>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="pay_method_radio" value="transfer" class="sr-only peer" onchange="document.getElementById('payMethod').value='transfer'">
                        <div class="h-11 flex items-center justify-center rounded-lg border-2 border-zinc-200 dark:border-zinc-600 peer-checked:border-violet-500 peer-checked:bg-violet-50 dark:peer-checked:bg-violet-900/20 text-sm font-medium text-zinc-600 dark:text-zinc-300 peer-checked:text-violet-700 dark:peer-checked:text-violet-400 transition">
                            <?= __('reservations.pos_pay_transfer') ?>
                        </div>
                    </label>
                </div>
                <input type="hidden" id="payMethod" value="card">
            </div>
        </div>
        <!-- 결제 버튼 -->
        <div class="px-4 pb-4">
            <button onclick="POS.submitPayment()"
                    class="w-full h-12 bg-violet-600 hover:bg-violet-700 active:bg-violet-800 text-white rounded-lg text-base font-bold transition">
                <?= __('reservations.pos_pay_submit') ?>
            </button>
        </div>
    </div>
</div>

<!-- 서비스 상세 모달 (POS 현장 기준) -->
<div id="posServiceModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4" onclick="POS.closeServiceModal(event)">
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-2xl w-full max-w-lg overflow-hidden" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between p-4 border-b border-zinc-200 dark:border-zinc-700">
            <h3 id="posServiceTitle" class="text-base font-bold text-zinc-900 dark:text-white flex items-center">
                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <?= __('reservations.pos_service_detail') ?>
            </h3>
            <button onclick="POS.closeServiceModal()" class="p-1 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg">
                <svg class="w-5 h-5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <!-- 고객 정보 -->
        <div id="posServiceCustomer" class="px-4 pt-3 pb-2 border-b border-zinc-100 dark:border-zinc-700"></div>
        <!-- 서비스 목록 -->
        <div id="posServiceList" class="p-4 max-h-60 overflow-y-auto"></div>
        <!-- 합계 -->
        <div id="posServiceTotal" class="px-4 pb-3 border-b border-zinc-200 dark:border-zinc-700"></div>
        <!-- 서비스 추가 영역 -->
        <div class="p-4">
            <button onclick="POS.toggleAddService()" id="posAddServiceToggle"
                    class="w-full py-2 text-sm font-medium text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg border border-dashed border-blue-300 dark:border-blue-700 transition flex items-center justify-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <?= __('reservations.pos_add_service') ?>
            </button>
            <div id="posAddServiceArea" class="hidden mt-3">
                <div id="posAddServiceList" class="space-y-2 max-h-48 overflow-y-auto mb-3"></div>
                <button onclick="POS.submitAddService()" id="posAddServiceBtn"
                        class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-bold transition disabled:opacity-50" disabled>
                    <?= __('reservations.pos_add_service_submit') ?>
                </button>
            </div>
        </div>
    </div>
</div>
