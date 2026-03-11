<?php
/**
 * 서비스 설정 - 기본설정 탭
 * 예약 관련 기본 옵션 설정
 */

// POST 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_general') {
    try {
        $fields = [
            'service_default_duration' => trim($_POST['service_default_duration'] ?? '60'),
            'service_default_buffer' => trim($_POST['service_default_buffer'] ?? '0'),
            'service_advance_booking_days' => trim($_POST['service_advance_booking_days'] ?? '30'),
            'service_min_notice_hours' => trim($_POST['service_min_notice_hours'] ?? '1'),
            'service_max_capacity' => trim($_POST['service_max_capacity'] ?? '1'),
            'service_currency' => trim($_POST['service_currency'] ?? 'KRW'),
            'service_price_display' => trim($_POST['service_price_display'] ?? 'show'),
        ];

        $stmt = $pdo->prepare("INSERT INTO {$prefix}settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
        foreach ($fields as $key => $value) {
            $stmt->execute([$key, $value]);
        }

        $message = __('admin.services.settings.general.saved');
        $messageType = 'success';

        // 설정 갱신
        foreach ($fields as $key => $value) {
            $settings[$key] = $value;
        }
    } catch (PDOException $e) {
        $message = __('admin.services.error.server_error') . ': ' . $e->getMessage();
        $messageType = 'error';
    }
}
?>
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm">
    <form method="POST" action="<?= $adminUrl ?>/services/settings/general">
        <input type="hidden" name="action" value="save_general">
        <input type="hidden" name="_token" value="<?= $_SESSION['_token'] ?? '' ?>">

        <div class="p-6 space-y-6">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">
                <?= __('admin.services.settings.general.title') ?>
            </h3>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                <?= __('admin.services.settings.general.description') ?>
            </p>

            <!-- 기본 소요시간 -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        <?= __('admin.services.settings.general.default_duration') ?>
                    </label>
                    <div class="flex items-center gap-2">
                        <input type="number" name="service_default_duration" min="5" max="480" step="5"
                               value="<?= htmlspecialchars($settings['service_default_duration'] ?? '60') ?>"
                               class="w-32 px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <span class="text-sm text-zinc-500 dark:text-zinc-400"><?= __('admin.services.minute') ?></span>
                    </div>
                </div>

                <!-- 기본 버퍼 시간 -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        <?= __('admin.services.settings.general.default_buffer') ?>
                    </label>
                    <div class="flex items-center gap-2">
                        <input type="number" name="service_default_buffer" min="0" max="120" step="5"
                               value="<?= htmlspecialchars($settings['service_default_buffer'] ?? '0') ?>"
                               class="w-32 px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <span class="text-sm text-zinc-500 dark:text-zinc-400"><?= __('admin.services.minute') ?></span>
                    </div>
                </div>

                <!-- 예약 가능 기간 -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        <?= __('admin.services.settings.general.advance_booking_days') ?>
                    </label>
                    <div class="flex items-center gap-2">
                        <input type="number" name="service_advance_booking_days" min="1" max="365"
                               value="<?= htmlspecialchars($settings['service_advance_booking_days'] ?? '30') ?>"
                               class="w-32 px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <span class="text-sm text-zinc-500 dark:text-zinc-400"><?= __('admin.services.settings.general.days') ?></span>
                    </div>
                </div>

                <!-- 최소 사전 알림 -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        <?= __('admin.services.settings.general.min_notice_hours') ?>
                    </label>
                    <div class="flex items-center gap-2">
                        <input type="number" name="service_min_notice_hours" min="0" max="168"
                               value="<?= htmlspecialchars($settings['service_min_notice_hours'] ?? '1') ?>"
                               class="w-32 px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <span class="text-sm text-zinc-500 dark:text-zinc-400"><?= __('admin.services.settings.general.hours') ?></span>
                    </div>
                </div>

                <!-- 기본 최대 수용 인원 -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        <?= __('admin.services.settings.general.max_capacity') ?>
                    </label>
                    <input type="number" name="service_max_capacity" min="1" max="100"
                           value="<?= htmlspecialchars($settings['service_max_capacity'] ?? '1') ?>"
                           class="w-32 px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- 통화 단위 -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        <?= __('admin.services.settings.general.currency') ?>
                    </label>
                    <?php $currencyValue = $settings['service_currency'] ?? 'KRW'; ?>
                    <select name="service_currency"
                            class="w-48 px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="KRW" <?= $currencyValue === 'KRW' ? 'selected' : '' ?>>KRW (&#8361;)</option>
                        <option value="USD" <?= $currencyValue === 'USD' ? 'selected' : '' ?>>USD ($)</option>
                        <option value="JPY" <?= $currencyValue === 'JPY' ? 'selected' : '' ?>>JPY (&#165;)</option>
                        <option value="EUR" <?= $currencyValue === 'EUR' ? 'selected' : '' ?>>EUR (&euro;)</option>
                        <option value="CNY" <?= $currencyValue === 'CNY' ? 'selected' : '' ?>>CNY (&#165;)</option>
                    </select>
                </div>

                <!-- 가격 표시 -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        <?= __('admin.services.settings.general.price_display') ?>
                    </label>
                    <?php $priceDisplay = $settings['service_price_display'] ?? 'show'; ?>
                    <select name="service_price_display"
                            class="w-48 px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="show" <?= $priceDisplay === 'show' ? 'selected' : '' ?>><?= __('admin.services.settings.general.price_show') ?></option>
                        <option value="hide" <?= $priceDisplay === 'hide' ? 'selected' : '' ?>><?= __('admin.services.settings.general.price_hide') ?></option>
                        <option value="contact" <?= $priceDisplay === 'contact' ? 'selected' : '' ?>><?= __('admin.services.settings.general.price_contact') ?></option>
                    </select>
                </div>
            </div>
        </div>

        <!-- 저장 버튼 -->
        <div class="px-6 py-4 bg-zinc-50 dark:bg-zinc-800/50 border-t border-zinc-200 dark:border-zinc-700 rounded-b-xl flex justify-end">
            <button type="submit"
                    class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                <?= __('admin.common.save') ?>
            </button>
        </div>
    </form>
</div>
