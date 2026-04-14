<section class="bg-gradient-to-b from-zinc-800 to-zinc-900 dark:from-zinc-900 dark:to-black rounded-2xl shadow-xl overflow-hidden text-white">
    <div class="px-6 py-4 border-b border-zinc-700 flex items-center justify-between">
        <h2 class="text-lg font-bold">주문 요약</h2>
        <div class="flex items-center gap-1">
            <button onclick="setCurrency('KRW')" id="cur_KRW" class="cur-btn px-2 py-1 rounded text-xs font-medium bg-white/20 text-white transition hover:bg-white/30">🇰🇷 KRW</button>
            <button onclick="setCurrency('USD')" id="cur_USD" class="cur-btn px-2 py-1 rounded text-xs font-medium text-zinc-400 transition hover:bg-white/10">🇺🇸 USD</button>
            <button onclick="setCurrency('JPY')" id="cur_JPY" class="cur-btn px-2 py-1 rounded text-xs font-medium text-zinc-400 transition hover:bg-white/10">🇯🇵 JPY</button>
            <button onclick="setCurrency('CNY')" id="cur_CNY" class="cur-btn px-2 py-1 rounded text-xs font-medium text-zinc-400 transition hover:bg-white/10">🇨🇳 CNY</button>
            <button onclick="setCurrency('EUR')" id="cur_EUR" class="cur-btn px-2 py-1 rounded text-xs font-medium text-zinc-400 transition hover:bg-white/10">🇪🇺 EUR</button>
        </div>
    </div>
    <div class="p-6 space-y-3 text-sm" id="orderSummaryBody">
        <div id="summaryEmpty" class="flex justify-between"><span class="text-zinc-400">호스팅 플랜을 선택하세요.</span></div>
        <div id="summaryItems" class="hidden space-y-2"></div>
        <div id="summaryTotal" class="hidden border-t border-zinc-700 pt-3 mt-3 flex justify-between items-center">
            <span class="text-lg font-bold">결제 금액</span>
            <span class="text-2xl font-bold text-blue-400" id="summaryTotalAmount">0원</span>
        </div>
    </div>
    <div class="px-6 pb-6">
        <button id="btnSubmitOrder" class="w-full py-4 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl transition text-lg shadow-lg disabled:opacity-50 disabled:cursor-not-allowed" disabled>결제하기</button>
        <p class="text-center text-xs text-zinc-500 mt-2">부가세(VAT) 포함 금액입니다.</p>
    </div>
</section>
