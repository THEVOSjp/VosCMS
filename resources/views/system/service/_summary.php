<section class="bg-gradient-to-b from-zinc-800 to-zinc-900 dark:from-zinc-900 dark:to-black rounded-2xl shadow-xl overflow-hidden text-white">
    <div class="px-6 py-4 border-b border-zinc-700">
        <h2 class="text-lg font-bold">주문 요약</h2>
    </div>
    <div class="p-6 space-y-3 text-sm" id="orderSummaryBody">
        <div id="summaryEmpty" class="flex justify-between"><span class="text-zinc-400">호스팅 플랜을 선택하세요.</span></div>
        <div id="summaryItems" class="hidden space-y-2"></div>
        <div id="summaryTotal" class="hidden border-t border-zinc-700 pt-3 mt-3 flex justify-between items-center">
            <span class="text-lg font-bold">최종 결제 금액</span>
            <span class="text-2xl font-bold text-blue-400" id="summaryTotalAmount"></span>
        </div>
    </div>
    <div class="px-6 pb-6">
        <button id="btnSubmitOrder" class="w-full py-4 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl transition text-lg shadow-lg disabled:opacity-50 disabled:cursor-not-allowed" disabled>결제하기</button>
    </div>
</section>
