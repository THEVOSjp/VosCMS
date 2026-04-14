<section class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-gray-200 dark:border-zinc-700 overflow-hidden">
    <div class="px-6 py-4 bg-gray-50 dark:bg-zinc-800/50 border-b border-gray-200 dark:border-zinc-700">
        <div class="flex items-center gap-2">
            <span class="w-7 h-7 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm font-bold">4</span>
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">신청자 정보</h2>
        </div>
    </div>
    <div class="p-6 space-y-4">
        <?php if (!$isLoggedIn): ?>
        <!-- 비회원 -->
        <div id="guestForm">
            <div class="p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl mb-4">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-amber-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"/></svg>
                    <div>
                        <p class="text-sm font-semibold text-amber-800 dark:text-amber-200">회원이신가요?</p>
                        <p class="text-xs text-amber-700 dark:text-amber-300 mt-1">이미 회원이시면 로그인하세요. 정보가 자동으로 입력됩니다.</p>
                        <a href="<?= $baseUrl ?>/login?redirect=<?= urlencode($_SERVER['REQUEST_URI'] ?? '/service/order') ?>" class="mt-2 inline-block px-4 py-2 bg-amber-600 text-white text-xs font-semibold rounded-lg hover:bg-amber-700 transition">로그인</a>
                    </div>
                </div>
            </div>
            <p class="text-xs text-gray-400 dark:text-zinc-500 mb-3">* 비회원은 입력된 정보로 자동 회원가입됩니다.</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">이름 / 회사명 <span class="text-red-500">*</span></label><input type="text" name="name" class="w-full px-4 py-3 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 text-sm" placeholder="홍길동 / 주식회사 OOO"></div>
                <div><label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">이메일 <span class="text-red-500">*</span></label><input type="email" name="email" class="w-full px-4 py-3 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 text-sm" placeholder="email@example.com"></div>
                <div><label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">비밀번호 <span class="text-red-500">*</span></label><input type="password" name="password" class="w-full px-4 py-3 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 text-sm" placeholder="8자 이상"></div>
                <div><label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">연락처 <span class="text-red-500">*</span></label><input type="tel" name="phone" class="w-full px-4 py-3 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 text-sm" placeholder="010-0000-0000"></div>
            </div>
        </div>
        <?php else: ?>
        <!-- 회원 -->
        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold"><?= mb_substr($currentUser['name'] ?? 'U', 0, 1) ?></div>
                    <div>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($currentUser['name'] ?? '') ?></p>
                        <p class="text-xs text-gray-500 dark:text-zinc-400"><?= htmlspecialchars($currentUser['email'] ?? '') ?></p>
                    </div>
                </div>
                <span class="text-xs text-green-600 font-medium flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg>
                    로그인됨
                </span>
            </div>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php if ($isLoggedIn): ?>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">연락처</label><input type="tel" name="phone" class="w-full px-4 py-3 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 text-sm" value="<?= htmlspecialchars($currentUser['phone'] ?? '') ?>"></div>
            <?php endif; ?>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1"><?= __('settings.site.category_label') ?? '사이트 분류 (업종)' ?></label>
                <?php
                $categoryKeys = ['beauty_salon', 'nail_salon', 'skincare', 'massage', 'hospital', 'dental', 'studio', 'restaurant', 'accommodation', 'sports', 'education', 'consulting', 'pet', 'car', 'corporate', 'shopping', 'law_firm', 'accounting', 'real_estate', 'it_tech', 'media', 'nonprofit', 'government', 'community', 'portfolio', 'other'];
                ?>
                <select name="site_category" class="w-full px-4 py-3 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 text-sm">
                    <option value=""><?= __('settings.site.category_placeholder') ?? '-- 업종을 선택하세요 --' ?></option>
                    <?php foreach ($categoryKeys as $ck): ?>
                    <option value="<?= $ck ?>"><?= __('settings.site.categories.' . $ck) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">요청 사항</label><textarea name="notes" class="w-full px-4 py-3 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 text-sm resize-y" rows="3" placeholder="추가 요청 사항이 있으면 입력하세요."></textarea></div>
    </div>
</section>
