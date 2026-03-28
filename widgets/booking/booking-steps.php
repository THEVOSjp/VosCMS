<?php
/**
 * Booking Widget - Steps 3~4 + 완료 화면
 * booking-html.php에서 include됨
 */
?>
    <!-- Step 3: 고객 정보 -->
    <div id="bwStepInfo" class="bw-step-panel bg-white dark:bg-zinc-800 rounded-2xl shadow-lg p-6 md:p-8 hidden">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6"><?= __('booking.enter_info') ?></h2>
        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1"><?= __('auth.register.name') ?> <span class="text-red-500">*</span></label>
                    <input type="text" id="bwCustName" required value="<?= $isLoggedIn ? htmlspecialchars($currentUser['name'] ?? '') : '' ?>"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500"
                           placeholder="<?= __('auth.register.name_placeholder') ?>">
                </div>
                <div>
                    <?php
                    $phoneInputConfig = [
                        'name' => 'bwCustPhone',
                        'id' => 'bwCustPhone',
                        'label' => __('auth.register.phone'),
                        'value' => $isLoggedIn ? htmlspecialchars($currentUser['phone'] ?? '') : '',
                        'phone_number' => $isLoggedIn ? htmlspecialchars($currentUser['phone'] ?? '') : '',
                        'required' => true,
                        'placeholder' => __('auth.register.phone_placeholder'),
                        'show_label' => true,
                    ];
                    include BASE_PATH . '/resources/views/components/phone-input.php';
                    ?>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1"><?= __('auth.register.email') ?></label>
                <input type="email" id="bwCustEmail" value="<?= $isLoggedIn ? htmlspecialchars($currentUser['email'] ?? '') : '' ?>"
                       class="w-full px-4 py-3 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500"
                       placeholder="<?= __('auth.register.email_placeholder') ?>">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1"><?= __('booking.notes') ?></label>
                <textarea id="bwCustMemo" rows="3" class="w-full px-4 py-3 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500"
                          placeholder="<?= __('booking.notes_placeholder') ?>"></textarea>
            </div>
        </div>
        <div class="flex justify-between mt-6">
            <button type="button" class="bw-prev-btn px-6 py-3 border border-gray-300 dark:border-zinc-600 text-gray-700 dark:text-zinc-300 font-semibold rounded-lg hover:bg-gray-50 dark:hover:bg-zinc-700 transition">
                &larr; <?= __('common.buttons.previous') ?>
            </button>
            <button type="button" class="bw-next-btn px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition">
                <?= __('booking.confirm_booking') ?> &rarr;
            </button>
        </div>
    </div>

    <!-- Step 4: 확인 -->
    <div id="bwStepConfirm" class="bw-step-panel bg-white dark:bg-zinc-800 rounded-2xl shadow-lg p-6 md:p-8 hidden">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6"><?= __('booking.confirm_info') ?></h2>
        <div class="bg-gray-50 dark:bg-zinc-700/50 rounded-xl p-6 space-y-4">
            <div class="pb-4 border-b dark:border-zinc-600">
                <span class="text-gray-600 dark:text-zinc-400"><?= __('booking.service.title') ?></span>
                <div id="bwConfirmService" class="mt-2 space-y-1"></div>
            </div>
            <?php
            $confirmRows = [
                ['booking.date_label', 'bwConfirmDate'],
                ['booking.time_label', 'bwConfirmTime'],
                ['booking.customer', 'bwConfirmName'],
                ['booking.phone', 'bwConfirmPhone'],
            ];
            foreach ($confirmRows as $cr): ?>
            <div class="flex justify-between items-center pb-4 border-b dark:border-zinc-600">
                <span class="text-gray-600 dark:text-zinc-400"><?= __($cr[0]) ?></span>
                <span id="<?= $cr[1] ?>" class="font-semibold text-gray-900 dark:text-white">-</span>
            </div>
            <?php endforeach; ?>
            <div class="flex justify-between items-center pt-2">
                <span class="text-lg font-semibold text-gray-900 dark:text-white"><?= __('booking.total_price') ?></span>
                <span id="bwConfirmPrice" class="text-2xl font-bold text-blue-600 dark:text-blue-400">-</span>
            </div>
        </div>
        <div class="mt-6 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
            <p class="text-sm text-amber-700 dark:text-amber-300"><?= __('booking.cancel_policy') ?></p>
        </div>
        <div class="flex justify-between mt-6">
            <button type="button" class="bw-prev-btn px-6 py-3 border border-gray-300 dark:border-zinc-600 text-gray-700 dark:text-zinc-300 font-semibold rounded-lg hover:bg-gray-50 dark:hover:bg-zinc-700 transition">
                &larr; <?= __('common.buttons.previous') ?>
            </button>
            <button type="button" id="bwSubmitBtn" class="px-8 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition shadow-lg shadow-blue-500/30">
                <?= __('booking.complete_booking') ?>
            </button>
        </div>
    </div>

    <!-- 완료 -->
    <div id="bwStepDone" class="bw-step-panel bg-white dark:bg-zinc-800 rounded-2xl shadow-lg p-8 hidden">
        <div class="text-center mb-8">
            <div class="w-20 h-20 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2"><?= __('booking.success') ?></h2>
            <p class="text-gray-600 dark:text-zinc-400 mb-2"><?= __('booking.success_desc') ?></p>
            <p class="text-lg font-mono font-bold text-blue-600 dark:text-blue-400 mb-6" id="bwDoneCode"></p>
        </div>

        <!-- 예약 정보 -->
        <div class="bg-gray-50 dark:bg-zinc-700/50 rounded-xl p-6 mb-8 text-left space-y-4">
            <!-- 서비스 -->
            <div class="pb-4 border-b dark:border-zinc-600">
                <p class="text-sm font-medium text-gray-600 dark:text-zinc-400 mb-2"><?= __('booking.service.title') ?></p>
                <div id="bwDoneService" class="space-y-1"></div>
            </div>

            <!-- 날짜 & 시간 -->
            <div class="grid grid-cols-2 gap-4 pb-4 border-b dark:border-zinc-600">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-zinc-400 mb-1"><?= __('booking.date_label') ?></p>
                    <p id="bwDoneDate" class="font-semibold text-gray-900 dark:text-white">-</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-zinc-400 mb-1"><?= __('booking.time_label') ?></p>
                    <p id="bwDoneTime" class="font-semibold text-gray-900 dark:text-white">-</p>
                </div>
            </div>

            <!-- 고객 정보 -->
            <div class="pb-4 border-b dark:border-zinc-600">
                <p class="text-sm font-medium text-gray-600 dark:text-zinc-400 mb-1"><?= __('booking.customer') ?></p>
                <p id="bwDoneName" class="font-semibold text-gray-900 dark:text-white">-</p>
            </div>

            <!-- 연락처 -->
            <div class="pb-4 border-b dark:border-zinc-600">
                <p class="text-sm font-medium text-gray-600 dark:text-zinc-400 mb-1"><?= __('booking.phone') ?></p>
                <p id="bwDonePhone" class="font-semibold text-gray-900 dark:text-white">-</p>
            </div>

            <!-- 가격 -->
            <?php if ($priceDisplay === 'show'): ?>
            <div class="pt-2">
                <div class="flex justify-between items-center">
                    <p class="text-lg font-semibold text-gray-900 dark:text-white"><?= __('booking.total_price') ?></p>
                    <p id="bwDonePrice" class="text-2xl font-bold text-blue-600 dark:text-blue-400">-</p>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="text-center">
            <a href="<?= $baseUrl ?>/" class="inline-block px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition"><?= __('common.nav.home') ?></a>
        </div>
    </div>
