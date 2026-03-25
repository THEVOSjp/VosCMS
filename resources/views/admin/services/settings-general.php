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
            'booking_phone_enabled' => trim($_POST['booking_phone_enabled'] ?? '0'),
            'booking_same_day' => trim($_POST['booking_same_day'] ?? '1'),
            'service_advance_booking_days' => trim($_POST['service_advance_booking_days'] ?? '30'),
            'service_min_notice_hours' => trim($_POST['service_min_notice_hours'] ?? '1'),
            'service_max_capacity' => trim($_POST['service_max_capacity'] ?? '1'),
            'service_price_display' => trim($_POST['service_price_display'] ?? 'show'),
            'service_discount_enabled' => trim($_POST['service_discount_enabled'] ?? '0'),
            'service_points_enabled' => trim($_POST['service_points_enabled'] ?? '0'),
            'service_points_name' => trim($_POST['service_points_name'] ?? ''),
            'service_deposit_enabled' => trim($_POST['service_deposit_enabled'] ?? '0'),
            'service_deposit_type' => trim($_POST['service_deposit_type'] ?? 'fixed'),
            'service_deposit_amount' => trim($_POST['service_deposit_amount'] ?? '0'),
            'service_deposit_percent' => trim($_POST['service_deposit_percent'] ?? '0'),
            'service_deposit_refund_hours' => trim($_POST['service_deposit_refund_hours'] ?? '24'),
            'bundle_display_name' => trim($_POST['bundle_display_name'] ?? ''),
        ];

        $stmt = $pdo->prepare("INSERT INTO {$prefix}settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
        foreach ($fields as $key => $value) {
            $stmt->execute([$key, $value]);
        }

        $message = __('services.settings.general.saved');
        $messageType = 'success';

        // 설정 갱신
        foreach ($fields as $key => $value) {
            $settings[$key] = $value;
        }
    } catch (PDOException $e) {
        $message = __('services.error.server_error') . ': ' . $e->getMessage();
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
                <?= __('services.settings.general.title') ?>
            </h3>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                <?= __('services.settings.general.description') ?>
            </p>

            <!-- 기본 소요시간 -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        <?= __('services.settings.general.default_duration') ?>
                    </label>
                    <div class="flex items-center gap-2">
                        <input type="number" name="service_default_duration" min="5" max="480" step="5"
                               value="<?= htmlspecialchars($settings['service_default_duration'] ?? '60') ?>"
                               class="w-32 px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <span class="text-sm text-zinc-500 dark:text-zinc-400"><?= __('services.minute') ?></span>
                    </div>
                </div>

                <!-- 기본 버퍼 시간 -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        <?= __('services.settings.general.default_buffer') ?>
                    </label>
                    <div class="flex items-center gap-2">
                        <input type="number" name="service_default_buffer" min="0" max="120" step="5"
                               value="<?= htmlspecialchars($settings['service_default_buffer'] ?? '0') ?>"
                               class="w-32 px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <span class="text-sm text-zinc-500 dark:text-zinc-400"><?= __('services.minute') ?></span>
                    </div>
                </div>

                <!-- 세트 서비스 명칭 -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        <?= __('services.settings.general.bundle_name') ?? '세트 서비스 명칭' ?>
                    </label>
                    <?php rzx_multilang_input('bundle_display_name', $settings['bundle_display_name'] ?? '', 'bundle_display_name', ['placeholder' => __('services.settings.general.bundle_name_placeholder') ?? '세트 서비스 (기본)']); ?>
                    <p class="mt-1 text-xs text-zinc-400"><?= __('services.settings.general.bundle_name_hint') ?? '세트 서비스, 패키지 서비스, 쿠폰 서비스 등 업종에 맞는 명칭을 설정합니다. 비워두면 기본값이 사용됩니다.' ?></p>
                </div>

                <!-- 전화 예약 -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        <?= __('services.settings.general.phone_booking') ?? '전화 예약' ?>
                    </label>
                    <?php $phoneBookingEnabled = ($settings['booking_phone_enabled'] ?? '0') === '1'; ?>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="booking_phone_enabled" value="0">
                        <input type="checkbox" name="booking_phone_enabled" value="1"
                               <?= $phoneBookingEnabled ? 'checked' : '' ?>
                               class="sr-only peer">
                        <div class="w-11 h-6 bg-zinc-200 peer-focus:ring-2 peer-focus:ring-blue-500 dark:peer-focus:ring-blue-600 rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:after:border-zinc-500 peer-checked:bg-blue-600"></div>
                        <span class="ml-3 text-sm text-zinc-500 dark:text-zinc-400"><?= __('services.settings.general.phone_booking_hint') ?? '활성화 시 관리자 예약 생성에서 전화예약 경로를 사용할 수 있습니다.' ?></span>
                    </label>
                </div>

                <!-- 당일 예약 허용 -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        <?= __('services.settings.general.same_day_booking') ?>
                    </label>
                    <?php $sameDayEnabled = ($settings['booking_same_day'] ?? '1') === '1'; ?>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="booking_same_day" value="0">
                        <input type="checkbox" name="booking_same_day" value="1"
                               <?= $sameDayEnabled ? 'checked' : '' ?>
                               class="sr-only peer">
                        <div class="w-11 h-6 bg-zinc-200 peer-focus:ring-2 peer-focus:ring-blue-500 dark:peer-focus:ring-blue-600 rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:after:border-zinc-500 peer-checked:bg-blue-600"></div>
                        <span class="ml-3 text-sm text-zinc-500 dark:text-zinc-400"><?= __('services.settings.general.same_day_booking_hint') ?></span>
                    </label>
                </div>

                <!-- 예약 가능 기간 -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        <?= __('services.settings.general.advance_booking_days') ?>
                    </label>
                    <div class="flex items-center gap-2">
                        <input type="number" name="service_advance_booking_days" min="1" max="365"
                               value="<?= htmlspecialchars($settings['service_advance_booking_days'] ?? '30') ?>"
                               class="w-32 px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <span class="text-sm text-zinc-500 dark:text-zinc-400"><?= __('services.settings.general.days') ?></span>
                    </div>
                </div>

                <!-- 최소 사전 예약 마감 -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        <?= __('services.settings.general.min_notice_hours') ?>
                    </label>
                    <div class="flex items-center gap-2">
                        <input type="number" name="service_min_notice_hours" min="0" max="168"
                               value="<?= htmlspecialchars($settings['service_min_notice_hours'] ?? '1') ?>"
                               class="w-32 px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <span class="text-sm text-zinc-500 dark:text-zinc-400"><?= __('services.settings.general.hours') ?></span>
                    </div>
                    <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-1"><?= __('services.settings.general.min_notice_hours_hint') ?></p>
                </div>

                <!-- 기본 최대 수용 인원 -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        <?= __('services.settings.general.max_capacity') ?>
                    </label>
                    <input type="number" name="service_max_capacity" min="1" max="100"
                           value="<?= htmlspecialchars($settings['service_max_capacity'] ?? '1') ?>"
                           class="w-32 px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- 가격 표시 -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        <?= __('services.settings.general.price_display') ?>
                    </label>
                    <?php $priceDisplay = $settings['service_price_display'] ?? 'show'; ?>
                    <select name="service_price_display"
                            class="w-48 px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="show" <?= $priceDisplay === 'show' ? 'selected' : '' ?>><?= __('services.settings.general.price_show') ?></option>
                        <option value="hide" <?= $priceDisplay === 'hide' ? 'selected' : '' ?>><?= __('services.settings.general.price_hide') ?></option>
                        <option value="contact" <?= $priceDisplay === 'contact' ? 'selected' : '' ?>><?= __('services.settings.general.price_contact') ?></option>
                    </select>
                </div>
            </div>

            <!-- 회원 할인/적립 설정 구분선 -->
            <hr class="border-zinc-200 dark:border-zinc-700">

            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white flex items-center">
                <svg class="w-5 h-5 mr-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                <?= __('services.settings.general.member_benefits_title') ?>
            </h3>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 -mt-4">
                <?= __('services.settings.general.member_benefits_description') ?>
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- 회원 등급별 할인율 적용 -->
                <div>
                    <?php $discountEnabled = ($settings['service_discount_enabled'] ?? '0') === '1'; ?>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="service_discount_enabled" value="0">
                        <input type="checkbox" name="service_discount_enabled" value="1"
                               <?= $discountEnabled ? 'checked' : '' ?>
                               class="sr-only peer">
                        <div class="w-11 h-6 bg-zinc-200 peer-focus:ring-2 peer-focus:ring-blue-500 dark:peer-focus:ring-blue-600 rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:after:border-zinc-500 peer-checked:bg-blue-600"></div>
                        <span class="ml-3 text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            <?= __('services.settings.general.discount_enabled') ?>
                        </span>
                    </label>
                    <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-1"><?= __('services.settings.general.discount_enabled_hint') ?></p>
                </div>

                <!-- 회원 등급별 적립금 적용 -->
                <div>
                    <?php $pointsEnabled = ($settings['service_points_enabled'] ?? '0') === '1'; ?>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="service_points_enabled" value="0">
                        <input type="checkbox" name="service_points_enabled" value="1"
                               <?= $pointsEnabled ? 'checked' : '' ?>
                               class="sr-only peer">
                        <div class="w-11 h-6 bg-zinc-200 peer-focus:ring-2 peer-focus:ring-blue-500 dark:peer-focus:ring-blue-600 rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:after:border-zinc-500 peer-checked:bg-blue-600"></div>
                        <span class="ml-3 text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            <?= __('services.settings.general.points_enabled') ?>
                        </span>
                    </label>
                    <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-1"><?= __('services.settings.general.points_enabled_hint') ?></p>
                </div>

                <!-- 적립금 명칭 -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('services.settings.general.points_name') ?></label>
                    <?php rzx_multilang_input('service_points_name', $settings['service_points_name'] ?? '', 'service_points_name', ['placeholder' => __('services.settings.general.points_name_placeholder')]); ?>
                    <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-1"><?= __('services.settings.general.points_name_hint') ?></p>
                </div>
            </div>

            <!-- 예약금 설정 구분선 -->
            <hr class="border-zinc-200 dark:border-zinc-700">

            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white flex items-center">
                <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <?= __('services.settings.general.deposit_title') ?>
            </h3>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 -mt-4">
                <?= __('services.settings.general.deposit_description') ?>
            </p>

            <?php
                $depositEnabled = ($settings['service_deposit_enabled'] ?? '0') === '1';
                $depositType = $settings['service_deposit_type'] ?? 'fixed';
                $depositAmount = $settings['service_deposit_amount'] ?? '0';
                $depositPercent = $settings['service_deposit_percent'] ?? '0';
                $depositRefundHours = $settings['service_deposit_refund_hours'] ?? '24';

                // 통화 기호
                $currencySymbols = [
                    'KRW' => '₩', 'USD' => '$', 'JPY' => '¥', 'EUR' => '€',
                    'CNY' => '¥', 'GBP' => '£', 'THB' => '฿', 'VND' => '₫',
                    'MNT' => '₮', 'RUB' => '₽', 'TRY' => '₺', 'IDR' => 'Rp',
                ];
                $sCurrency = $settings['service_currency'] ?? 'KRW';
                $sCurrencySymbol = $currencySymbols[$sCurrency] ?? $sCurrency;
            ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- 예약금 사용 여부 -->
                <div class="md:col-span-2">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="service_deposit_enabled" value="0">
                        <input type="checkbox" name="service_deposit_enabled" value="1"
                               id="depositEnabled"
                               <?= $depositEnabled ? 'checked' : '' ?>
                               class="sr-only peer"
                               onchange="toggleDepositFields()">
                        <div class="w-11 h-6 bg-zinc-200 peer-focus:ring-2 peer-focus:ring-blue-500 dark:peer-focus:ring-blue-600 rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:after:border-zinc-500 peer-checked:bg-blue-600"></div>
                        <span class="ml-3 text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            <?= __('services.settings.general.deposit_enabled') ?>
                        </span>
                    </label>
                </div>

                <!-- 예약금 타입 -->
                <div id="depositTypeField" class="<?= $depositEnabled ? '' : 'opacity-50 pointer-events-none' ?>">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        <?= __('services.settings.general.deposit_type') ?>
                    </label>
                    <select name="service_deposit_type" id="depositType"
                            onchange="toggleDepositAmountFields()"
                            class="w-48 px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="fixed" <?= $depositType === 'fixed' ? 'selected' : '' ?>><?= __('services.settings.general.deposit_type_fixed') ?></option>
                        <option value="percent" <?= $depositType === 'percent' ? 'selected' : '' ?>><?= __('services.settings.general.deposit_type_percent') ?></option>
                    </select>
                </div>

                <!-- 예약금 금액 (고정) -->
                <div id="depositAmountField" class="<?= $depositEnabled ? '' : 'opacity-50 pointer-events-none' ?> <?= $depositType === 'percent' ? 'hidden' : '' ?>">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        <?= __('services.settings.general.deposit_amount') ?>
                    </label>
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-zinc-500 dark:text-zinc-400"><?= $sCurrencySymbol ?></span>
                        <input type="number" name="service_deposit_amount" min="0" step="1"
                               value="<?= htmlspecialchars($depositAmount) ?>"
                               class="w-40 px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <!-- 예약금 비율 (%) -->
                <div id="depositPercentField" class="<?= $depositEnabled ? '' : 'opacity-50 pointer-events-none' ?> <?= $depositType === 'fixed' ? 'hidden' : '' ?>">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        <?= __('services.settings.general.deposit_percent') ?>
                    </label>
                    <div class="flex items-center gap-2">
                        <input type="number" name="service_deposit_percent" min="0" max="100" step="1"
                               value="<?= htmlspecialchars($depositPercent) ?>"
                               class="w-32 px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <span class="text-sm text-zinc-500 dark:text-zinc-400">%</span>
                    </div>
                    <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-1"><?= __('services.settings.general.deposit_percent_hint') ?></p>
                </div>

                <!-- 환불 기한 -->
                <div id="depositRefundField" class="<?= $depositEnabled ? '' : 'opacity-50 pointer-events-none' ?>">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        <?= __('services.settings.general.deposit_refund_hours') ?>
                    </label>
                    <div class="flex items-center gap-2">
                        <input type="number" name="service_deposit_refund_hours" min="0" max="168"
                               value="<?= htmlspecialchars($depositRefundHours) ?>"
                               class="w-32 px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <span class="text-sm text-zinc-500 dark:text-zinc-400"><?= __('services.settings.general.hours') ?></span>
                    </div>
                    <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-1"><?= __('services.settings.general.deposit_refund_hint') ?></p>
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

<script>
console.log('[ServiceSettings] General tab loaded');

function toggleDepositFields() {
    const enabled = document.getElementById('depositEnabled').checked;
    console.log('[ServiceSettings] Deposit enabled:', enabled);
    const fields = ['depositTypeField', 'depositAmountField', 'depositPercentField', 'depositRefundField'];
    fields.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            if (enabled) {
                el.classList.remove('opacity-50', 'pointer-events-none');
            } else {
                el.classList.add('opacity-50', 'pointer-events-none');
            }
        }
    });
    if (enabled) toggleDepositAmountFields();
}

function toggleDepositAmountFields() {
    const type = document.getElementById('depositType').value;
    console.log('[ServiceSettings] Deposit type:', type);
    const amountField = document.getElementById('depositAmountField');
    const percentField = document.getElementById('depositPercentField');
    if (type === 'fixed') {
        amountField.classList.remove('hidden');
        percentField.classList.add('hidden');
    } else {
        amountField.classList.add('hidden');
        percentField.classList.remove('hidden');
    }
}
</script>
