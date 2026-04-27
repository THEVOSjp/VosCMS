<?php
/**
 * @deprecated 이 파일은 더 이상 사용되지 않습니다.
 * 서비스 설정은 관리자 > 페이지 설정 > 서비스 설정 탭으로 이동했습니다.
 * @see resources/views/system/service/_settings.php
 * @see config/system-pages.php (settings_view)
 */
return; // 실행 방지
?>
<div id="adminSettingsModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="document.getElementById('adminSettingsModal').classList.add('hidden')"></div>
    <div class="relative bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-zinc-700">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-gray-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">서비스 환경 설정</h2>
            </div>
            <button onclick="document.getElementById('adminSettingsModal').classList.add('hidden')" class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-white rounded-lg hover:bg-gray-100 dark:hover:bg-zinc-700"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <form method="POST" action="<?= $baseUrl ?>/service/order/settings" class="p-6 space-y-6">
            <input type="hidden" name="_csrf" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

            <!-- 페이지 너비 -->
            <div>
                <h3 class="text-sm font-bold text-gray-800 dark:text-zinc-200 mb-3">페이지 너비</h3>
                <select name="service_page_width" class="px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg text-sm">
                    <option value="2xl" <?= ($serviceSettings['service_page_width'] ?? '') === '2xl' ? 'selected' : '' ?>>좁게 (2xl)</option>
                    <option value="3xl" <?= ($serviceSettings['service_page_width'] ?? '') === '3xl' ? 'selected' : '' ?>>보통 (3xl)</option>
                    <option value="4xl" <?= ($serviceSettings['service_page_width'] ?? '4xl') === '4xl' ? 'selected' : '' ?>>기본 (4xl)</option>
                    <option value="5xl" <?= ($serviceSettings['service_page_width'] ?? '') === '5xl' ? 'selected' : '' ?>>넓게 (5xl)</option>
                    <option value="6xl" <?= ($serviceSettings['service_page_width'] ?? '') === '6xl' ? 'selected' : '' ?>>매우 넓게 (6xl)</option>
                    <option value="7xl" <?= ($serviceSettings['service_page_width'] ?? '') === '7xl' ? 'selected' : '' ?>>최대 (7xl)</option>
                </select>
            </div>

            <!-- 환율 설정 -->
            <div>
                <h3 class="text-sm font-bold text-gray-800 dark:text-zinc-200 mb-3">환율 설정</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-xs font-medium text-gray-600 dark:text-zinc-400 mb-1">USD → KRW</label><div class="flex items-center gap-2"><input type="number" name="service_exchange_rate" value="<?= $serviceSettings['service_exchange_rate'] ?? '1380' ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg text-sm"><span class="text-xs text-gray-400 whitespace-nowrap">원/$</span></div></div>
                    <div><label class="block text-xs font-medium text-gray-600 dark:text-zinc-400 mb-1">자동 업데이트</label><select name="service_exchange_auto" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg text-sm"><option>수동 설정</option><option>매일 자동</option><option>매주 자동</option></select></div>
                </div>
            </div>

            <!-- 도메인 마진 -->
            <div>
                <h3 class="text-sm font-bold text-gray-800 dark:text-zinc-200 mb-3">도메인 마진</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-xs font-medium text-gray-600 dark:text-zinc-400 mb-1">기본 마진율</label><div class="flex items-center gap-2"><input type="number" name="service_domain_margin" value="<?= $serviceSettings['service_domain_margin'] ?? '30' ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg text-sm"><span class="text-xs text-gray-400">%</span></div></div>
                    <div><label class="block text-xs font-medium text-gray-600 dark:text-zinc-400 mb-1">반올림 단위</label><select name="service_price_round" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg text-sm"><option>100원</option><option>500원</option><option selected>1,000원</option></select></div>
                </div>
            </div>

            <!-- NameSilo API -->
            <div>
                <h3 class="text-sm font-bold text-gray-800 dark:text-zinc-200 mb-3">NameSilo API</h3>
                <div><label class="block text-xs font-medium text-gray-600 dark:text-zinc-400 mb-1">API Key</label><input type="password" name="service_namesilo_key" value="<?= $serviceSettings['service_namesilo_key'] ?? ($_ENV['NAMESILO_API_KEY'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg text-sm font-mono"></div>
                <div class="mt-2 flex items-center gap-3">
                    <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" name="service_namesilo_sandbox" class="text-blue-600 rounded"><span class="text-xs text-gray-600 dark:text-zinc-400">샌드박스 모드</span></label>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-zinc-700">
                <button type="button" onclick="document.getElementById('adminSettingsModal').classList.add('hidden')" class="px-4 py-2 text-sm text-gray-600 dark:text-zinc-400 hover:bg-gray-100 dark:hover:bg-zinc-700 rounded-lg transition">취소</button>
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition">저장</button>
            </div>
        </form>
    </div>
</div>
