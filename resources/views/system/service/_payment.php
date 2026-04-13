<section class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-gray-200 dark:border-zinc-700 overflow-hidden">
    <div class="px-6 py-4 bg-gray-50 dark:bg-zinc-800/50 border-b border-gray-200 dark:border-zinc-700">
        <div class="flex items-center gap-2">
            <span class="w-7 h-7 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm font-bold">5</span>
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">결제 방법</h2>
        </div>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-2 gap-3">
            <label class="hosting-option selected cursor-pointer border-2 border-blue-500 rounded-xl p-4 text-center bg-blue-50 dark:bg-blue-900/30">
                <input type="radio" name="payment" value="card" class="hidden" checked>
                <svg class="w-6 h-6 mx-auto mb-1 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                <p class="text-sm font-semibold text-gray-900 dark:text-white">카드 결제</p>
            </label>
            <label class="hosting-option cursor-pointer border-2 border-gray-200 dark:border-zinc-600 rounded-xl p-4 text-center hover:border-blue-400 transition">
                <input type="radio" name="payment" value="bank" class="hidden">
                <svg class="w-6 h-6 mx-auto mb-1 text-gray-500 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                <p class="text-sm font-semibold text-gray-900 dark:text-white">계좌이체</p>
            </label>
        </div>
        <div class="mt-4 flex items-start gap-2">
            <input type="checkbox" name="agree_terms" class="mt-1 text-blue-600 rounded">
            <p class="text-xs text-gray-500 dark:text-zinc-400"><a href="<?= $baseUrl ?>/terms" class="text-blue-600 hover:underline">이용약관</a> 및 <a href="<?= $baseUrl ?>/privacy" class="text-blue-600 hover:underline">개인정보처리방침</a>에 동의합니다.</p>
        </div>
    </div>
</section>
