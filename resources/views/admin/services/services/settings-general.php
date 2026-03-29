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
            'bundle_display_name' => trim($_POST['bundle_display_name'] ?? ''),
            // 환불 정책
            'refund_enabled' => trim($_POST['refund_enabled'] ?? '0'),
            'refund_time_unit' => trim($_POST['refund_time_unit'] ?? 'hours'),
            'refund_full_period' => trim($_POST['refund_full_period'] ?? '24'),
            'refund_partial1_enabled' => trim($_POST['refund_partial1_enabled'] ?? '0'),
            'refund_partial1_period' => trim($_POST['refund_partial1_period'] ?? '18'),
            'refund_partial1_rate' => trim($_POST['refund_partial1_rate'] ?? '70'),
            'refund_partial2_enabled' => trim($_POST['refund_partial2_enabled'] ?? '0'),
            'refund_partial2_period' => trim($_POST['refund_partial2_period'] ?? '12'),
            'refund_partial2_rate' => trim($_POST['refund_partial2_rate'] ?? '50'),
            'refund_partial3_enabled' => trim($_POST['refund_partial3_enabled'] ?? '0'),
            'refund_partial3_period' => trim($_POST['refund_partial3_period'] ?? '6'),
            'refund_partial3_rate' => trim($_POST['refund_partial3_rate'] ?? '30'),
            'refund_noshow_charge' => trim($_POST['refund_noshow_charge'] ?? '0'),
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

                <!-- 환불 기한 → 환불 정책 섹션으로 이동 -->
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

<?php
// 환불 정책 설정 로드
$_rfEnabled = ($settings['refund_enabled'] ?? '0') === '1';
$_rfTimeUnit = $settings['refund_time_unit'] ?? 'hours';
$_rfFullPeriod = $settings['refund_full_period'] ?? '24';
$_rfPartials = [];
for ($i = 1; $i <= 3; $i++) {
    $_rfPartials[$i] = [
        'enabled' => ($settings["refund_partial{$i}_enabled"] ?? '0') === '1',
        'period' => $settings["refund_partial{$i}_period"] ?? ($i === 1 ? '18' : ($i === 2 ? '12' : '6')),
        'rate' => $settings["refund_partial{$i}_rate"] ?? ($i === 1 ? '70' : ($i === 2 ? '50' : '30')),
    ];
}
$_rfNoshowCharge = ($settings['refund_noshow_charge'] ?? '0') === '1';
?>

<!-- 환불 정책 설정 -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden mt-6">
    <form method="POST">
        <input type="hidden" name="action" value="save_general">
        <!-- 기존 설정 유지를 위해 hidden 필드 -->
        <?php foreach ($settings as $k => $v): ?>
        <?php if (str_starts_with($k, 'service_') || $k === 'bundle_display_name' || $k === 'booking_phone_enabled' || $k === 'booking_same_day'): ?>
        <input type="hidden" name="<?= htmlspecialchars($k) ?>" value="<?= htmlspecialchars($v) ?>">
        <?php endif; ?>
        <?php endforeach; ?>

        <div class="px-6 py-5 border-b border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"/></svg>
                <div>
                    <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100"><?= __('services.settings.general.refund_title') ?? '환불 정책 설정' ?></h3>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400"><?= __('services.settings.general.refund_description') ?? '예약 취소 시 환불 조건을 설정합니다.' ?></p>
                </div>
            </div>
        </div>

        <div class="px-6 py-5 space-y-5">

            <!-- 환불 활성화 -->
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('services.settings.general.refund_enabled') ?? '환불 정책 사용' ?></p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400"><?= __('services.settings.general.refund_enabled_hint') ?? '비활성화 시 취소하면 환불 없이 예약만 취소됩니다.' ?></p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="refund_enabled" id="refundEnabled" value="1" <?= $_rfEnabled ? 'checked' : '' ?> onchange="toggleRefundFields()" class="sr-only peer">
                    <div class="w-11 h-6 bg-zinc-200 peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                </label>
            </div>

            <div id="refundFields" class="space-y-5 <?= $_rfEnabled ? '' : 'opacity-50 pointer-events-none' ?>">

                <!-- 기준 시간 단위 -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('services.settings.general.refund_time_unit') ?? '환불 기준 단위' ?></label>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="refund_time_unit" value="hours" <?= $_rfTimeUnit === 'hours' ? 'checked' : '' ?> class="text-blue-600 focus:ring-blue-500">
                            <span class="text-sm text-zinc-700 dark:text-zinc-300"><?= __('services.settings.general.refund_unit_hours') ?? '시간' ?></span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="refund_time_unit" value="days" <?= $_rfTimeUnit === 'days' ? 'checked' : '' ?> class="text-blue-600 focus:ring-blue-500">
                            <span class="text-sm text-zinc-700 dark:text-zinc-300"><?= __('services.settings.general.refund_unit_days') ?? '일' ?></span>
                        </label>
                    </div>
                </div>

                <!-- 전액 환불 기한 -->
                <div class="bg-green-50 dark:bg-green-900/10 rounded-lg p-4 border border-green-200 dark:border-green-800">
                    <h4 class="text-sm font-semibold text-green-800 dark:text-green-300 mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <?= __('services.settings.general.refund_full_title') ?? '전액 환불' ?>
                    </h4>
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-zinc-600 dark:text-zinc-400"><?= __('services.settings.general.refund_before') ?? '예약 시간' ?></span>
                        <input type="number" name="refund_full_period" min="1" max="720"
                               value="<?= htmlspecialchars($_rfFullPeriod) ?>"
                               class="w-20 px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-center focus:ring-2 focus:ring-blue-500">
                        <span class="text-sm text-zinc-600 dark:text-zinc-400 refund-unit-label"><?= $_rfTimeUnit === 'days' ? (__('services.settings.general.refund_unit_days') ?? '일') : (__('services.settings.general.refund_unit_hours') ?? '시간') ?></span>
                        <span class="text-sm text-zinc-600 dark:text-zinc-400"><?= __('services.settings.general.refund_before_suffix') ?? '전 취소 시 100% 환불' ?></span>
                    </div>
                </div>

                <!-- 부분 환불 (3단계) -->
                <?php
                $_unitLabel = $_rfTimeUnit === 'days' ? (__('services.settings.general.refund_unit_days') ?? '일') : (__('services.settings.general.refund_unit_hours') ?? '시간');
                for ($i = 1; $i <= 3; $i++):
                    $p = $_rfPartials[$i];
                ?>
                <div class="bg-amber-50 dark:bg-amber-900/10 rounded-lg p-4 border border-amber-200 dark:border-amber-800">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-sm font-semibold text-amber-800 dark:text-amber-300 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                            <?= __('services.settings.general.refund_partial_title') ?? '부분 환불' ?> <?= $i ?>
                        </h4>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="refund_partial<?= $i ?>_enabled" value="1" <?= $p['enabled'] ? 'checked' : '' ?> class="sr-only peer">
                            <div class="w-9 h-5 bg-zinc-200 rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-amber-500"></div>
                        </label>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <input type="number" name="refund_partial<?= $i ?>_period" min="0" max="720"
                               value="<?= htmlspecialchars($p['period']) ?>"
                               class="w-20 px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-center focus:ring-2 focus:ring-blue-500">
                        <span class="text-sm text-zinc-600 dark:text-zinc-400 refund-unit-label"><?= $_unitLabel ?></span>
                        <span class="text-sm text-zinc-600 dark:text-zinc-400"><?= __('services.settings.general.refund_partial_suffix') ?? '전 취소 시' ?></span>
                        <input type="number" name="refund_partial<?= $i ?>_rate" min="0" max="99"
                               value="<?= htmlspecialchars($p['rate']) ?>"
                               class="w-20 px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-center focus:ring-2 focus:ring-blue-500">
                        <span class="text-sm text-zinc-600 dark:text-zinc-400">% <?= __('services.settings.general.refund_word') ?? '환불' ?></span>
                    </div>
                </div>
                <?php endfor; ?>

                <!-- 노쇼/기한 초과 -->
                <div class="bg-red-50 dark:bg-red-900/10 rounded-lg p-4 border border-red-200 dark:border-red-800">
                    <h4 class="text-sm font-semibold text-red-800 dark:text-red-300 mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                        <?= __('services.settings.general.refund_norefund_title') ?? '환불 불가' ?>
                    </h4>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-3"><?= __('services.settings.general.refund_norefund_desc') ?? '위 기한 이후 취소 또는 노쇼(No-Show) 시 환불 불가' ?></p>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="refund_noshow_charge" value="1" <?= $_rfNoshowCharge ? 'checked' : '' ?> class="rounded text-red-600 focus:ring-red-500">
                        <span class="text-sm text-zinc-700 dark:text-zinc-300"><?= __('services.settings.general.refund_noshow_blacklist') ?? '노쇼 3회 이상 시 블랙리스트 자동 등록' ?></span>
                    </label>
                </div>

                <!-- 정책 요약 미리보기 -->
                <div class="bg-zinc-50 dark:bg-zinc-700/30 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-2"><?= __('services.settings.general.refund_preview') ?? '정책 요약' ?></h4>
                    <div class="text-sm space-y-1" id="refundPreview">
                        <p class="text-green-600 dark:text-green-400">✓ <?= htmlspecialchars($_rfFullPeriod) ?><?= $_unitLabel ?> <?= __('services.settings.general.refund_preview_full') ?? '전 → 전액 환불 (100%)' ?></p>
                        <?php for ($i = 1; $i <= 3; $i++): ?>
                        <?php if ($_rfPartials[$i]['enabled']): ?>
                        <p class="text-amber-600 dark:text-amber-400">△ <?= htmlspecialchars($_rfPartials[$i]['period']) ?><?= $_unitLabel ?> <?= __('services.settings.general.refund_partial_suffix') ?? '전 취소 시' ?> <?= htmlspecialchars($_rfPartials[$i]['rate']) ?>% <?= __('services.settings.general.refund_word') ?? '환불' ?></p>
                        <?php endif; ?>
                        <?php endfor; ?>
                        <p class="text-red-600 dark:text-red-400">✕ <?= __('services.settings.general.refund_preview_none') ?? '그 외 → 환불 불가' ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="px-6 py-4 bg-zinc-50 dark:bg-zinc-800/50 border-t border-zinc-200 dark:border-zinc-700 rounded-b-xl flex justify-end">
            <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                <?= __('admin.common.save') ?>
            </button>
        </div>
    </form>
</div>

<script>
console.log('[ServiceSettings] General tab loaded');

function toggleRefundFields() {
    var enabled = document.getElementById('refundEnabled').checked;
    var fields = document.getElementById('refundFields');
    if (enabled) { fields.classList.remove('opacity-50', 'pointer-events-none'); }
    else { fields.classList.add('opacity-50', 'pointer-events-none'); }
    console.log('[ServiceSettings] Refund enabled:', enabled);
}

// 시간 단위 변경 시 라벨 업데이트
document.querySelectorAll('[name=refund_time_unit]').forEach(function(r) {
    r.addEventListener('change', function() {
        var unit = this.value === 'days' ? '<?= __('services.settings.general.refund_unit_days') ?? '일' ?>' : '<?= __('services.settings.general.refund_unit_hours') ?? '시간' ?>';
        document.querySelectorAll('.refund-unit-label').forEach(function(el) { el.textContent = unit; });
        console.log('[ServiceSettings] Refund unit changed:', this.value);
    });
});

function toggleDepositFields() {
    const enabled = document.getElementById('depositEnabled').checked;
    console.log('[ServiceSettings] Deposit enabled:', enabled);
    const fields = ['depositTypeField', 'depositAmountField', 'depositPercentField'];
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
