<?php
/**
 * 키오스크 접수 확인 폼 (confirm.php에서 include)
 * 변수: $lang, $type, $staffId, $staffName, $designationFee,
 *       $services, $translations, $totalAmount, $totalDuration, $finalAmount,
 *       $currencySymbol, $currencyPosition, $serviceIdsRaw, $siteSettings
 */

// site_country(ISO) → 국제전화코드 매핑
$countryPhoneCodes = [
    'KR' => '+82', 'JP' => '+81', 'US' => '+1', 'CN' => '+86', 'TW' => '+886',
    'HK' => '+852', 'SG' => '+65', 'TH' => '+66', 'VN' => '+84', 'MY' => '+60',
    'ID' => '+62', 'PH' => '+63', 'IN' => '+91', 'MN' => '+976', 'RU' => '+7',
    'DE' => '+49', 'FR' => '+33', 'ES' => '+34', 'GB' => '+44', 'IT' => '+39',
    'TR' => '+90', 'AU' => '+61', 'NZ' => '+64', 'CA' => '+1', 'MX' => '+52',
    'BR' => '+55', 'AE' => '+971', 'SA' => '+966',
];
$siteCountry = strtoupper($siteSettings['site_country'] ?? 'KR');
$defaultPhoneCode = $countryPhoneCodes[$siteCountry] ?? '+82';
?>
        <!-- 상단 헤더 -->
        <div class="flex items-center justify-between px-8 pt-6 pb-2">
            <button type="button" onclick="goBack()"
                    class="flex items-center gap-2 px-4 py-2 rounded-xl <?= $subTextColor ?> hover:<?= $textColor ?> transition text-sm backdrop-blur-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <?= __('reservations.kiosk_back') ?>
            </button>
            <div class="text-center">
                <?php if ($kioskLogoSrc): ?>
                    <img src="<?= htmlspecialchars($kioskLogoSrc) ?>" alt="<?= htmlspecialchars($siteName) ?>" class="h-10 mx-auto object-contain">
                <?php else: ?>
                    <span class="text-lg font-bold <?= $textColor ?>"><?= htmlspecialchars($siteName) ?></span>
                <?php endif; ?>
            </div>
            <div class="w-20"></div>
        </div>

        <!-- 접수 확인 내용 -->
        <div class="flex-1 overflow-y-auto px-8 py-4">
            <div class="max-w-xl mx-auto space-y-6">

                <!-- 타이틀 -->
                <div class="text-center">
                    <h2 class="text-2xl font-bold <?= $textColor ?>"><?= __('reservations.kiosk_confirm_title') ?></h2>
                    <p class="<?= $subTextColor ?> text-sm mt-1"><?= __('reservations.kiosk_confirm_desc') ?></p>
                </div>

                <!-- 스태프 정보 (지명일 때만 표시) -->
                <?php if ($staffName && $type === 'designation'): ?>
                <div class="p-4 rounded-2xl backdrop-blur-sm border <?= $btnBg ?>">
                    <div class="flex items-center justify-between">
                        <span class="<?= $subTextColor ?> text-sm"><?= __('reservations.kiosk_staff_selected') ?></span>
                        <span class="<?= $textColor ?> font-bold"><?= htmlspecialchars($staffName) ?></span>
                    </div>
                    <?php if ($designationFee > 0): ?>
                    <div class="flex items-center justify-between mt-2">
                        <span class="<?= $subTextColor ?> text-sm"><?= __('reservations.kiosk_designation_fee') ?></span>
                        <span class="<?= $isLight ? 'text-blue-600' : 'text-blue-400' ?> font-medium"><?= kioskFmtPrice($designationFee, $currencySymbol, $currencyPosition) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- 서비스 목록 -->
                <div class="p-4 rounded-2xl backdrop-blur-sm border <?= $btnBg ?>">
                    <h3 class="<?= $textColor ?> font-bold text-sm mb-3"><?= __('reservations.pos_service_detail') ?></h3>
                    <div class="space-y-3">
                        <?php foreach ($services as $s):
                            $svcName = $translations['service.' . $s['id'] . '.name'] ?? $s['name'];
                        ?>
                        <div class="flex items-center justify-between">
                            <span class="<?= $textColor ?> text-sm"><?= htmlspecialchars($svcName) ?></span>
                            <div class="flex items-center gap-3">
                                <span class="<?= $subTextColor ?> text-xs"><?= $s['duration'] ?><?= __('reservations.pos_min') ?></span>
                                <span class="<?= $isLight ? 'text-blue-600' : 'text-blue-400' ?> text-sm font-bold"><?= kioskFmtPrice((float)$s['price'], $currencySymbol, $currencyPosition) ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <!-- 합계 -->
                    <div class="border-t <?= $isLight ? 'border-black/10' : 'border-white/10' ?> mt-3 pt-3">
                        <div class="flex items-center justify-between">
                            <span class="<?= $textColor ?> font-bold"><?= __('reservations.pos_pay_total') ?></span>
                            <div class="text-right">
                                <span class="<?= $isLight ? 'text-blue-600' : 'text-blue-400' ?> text-xl font-bold"><?= kioskFmtPrice($finalAmount, $currencySymbol, $currencyPosition) ?></span>
                                <span class="<?= $subTextColor ?> text-xs block"><?= $totalDuration ?><?= __('reservations.pos_min') ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 고객 정보 입력 -->
                <form id="checkinForm" method="POST" action="">
                    <input type="hidden" name="action" value="checkin">
                    <div class="p-4 rounded-2xl backdrop-blur-sm border <?= $btnBg ?> space-y-4">
                        <h3 class="<?= $textColor ?> font-bold text-sm"><?= __('reservations.kiosk_customer_info') ?></h3>

                        <!-- 고객명 -->
                        <div>
                            <label class="block <?= $subTextColor ?> text-xs mb-1"><?= __('reservations.pos_checkin_name') ?></label>
                            <input type="text" name="customer_name" id="customerName"
                                   class="w-full px-4 py-3 rounded-xl text-lg bg-zinc-700 border-zinc-600 text-white border outline-none focus:ring-2 focus:ring-blue-500 placeholder-zinc-400"
                                   placeholder="<?= __('reservations.pos_checkin_placeholder_name') ?>"
                                   autocomplete="off">
                        </div>

                        <!-- 연락처 (국제전화번호 컴포넌트) -->
                        <div>
                            <label class="block <?= $subTextColor ?> text-xs mb-1"><?= __('reservations.pos_checkin_phone') ?></label>
                            <?php
                            $phoneInputConfig = [
                                'name' => 'customer_phone',
                                'id' => 'customerPhone',
                                'label' => '',
                                'value' => '',
                                'country_code' => $defaultPhoneCode,
                                'phone_number' => '',
                                'required' => false,
                                'hint' => '',
                                'placeholder' => '',
                                'show_label' => false,
                            ];
                            include BASE_PATH . '/resources/views/components/phone-input.php';
                            ?>
                        </div>

                        <p class="<?= $subTextColor ?> text-xs"><?= __('reservations.kiosk_customer_optional') ?></p>
                    </div>

                    <!-- 접수 버튼 -->
                    <button type="submit" id="submitBtn"
                            class="w-full mt-6 py-4 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-xl font-bold rounded-2xl transition">
                        <?= __('reservations.kiosk_submit_checkin') ?>
                    </button>
                </form>

            </div>
        </div>

        <!-- 하단 안내 -->
        <div class="py-4 text-center">
            <p class="<?= $footerColor ?> text-sm"><?= $footerText ?></p>
        </div>

<!-- phone-input 컴포넌트용 다크모드 스타일 오버라이드 -->
<style>
    .phone-input-component .phone-country-dropdown-btn {
        background: rgb(63 63 70); /* bg-zinc-700 */
        border-color: rgb(82 82 91); /* border-zinc-600 */
        border-radius: 0.75rem;
        padding: 0.75rem;
        min-width: 80px;
    }
    .phone-input-component .phone-selected-country {
        color: rgb(161 161 170); /* text-zinc-400 */
        font-size: 0.625rem;
    }
    .phone-input-component .phone-selected-code {
        color: #fff;
        font-size: 1rem;
    }
    .phone-input-component .phone-country-dropdown {
        background: rgb(39 39 42); /* bg-zinc-800 */
        border-color: rgb(63 63 70);
        border-radius: 0.75rem;
    }
    .phone-input-component .phone-country-dropdown .phone-country-search {
        background: rgb(63 63 70);
        border-color: rgb(82 82 91);
        color: #fff;
        border-radius: 0.75rem;
    }
    .phone-input-component .phone-country-option:hover {
        background: rgb(63 63 70);
    }
    .phone-input-component .phone-country-option span {
        color: #fff;
    }
    .phone-input-component .phone-country-option .text-xs {
        color: rgb(161 161 170);
    }
    .phone-input-component input[type="tel"] {
        background: rgb(63 63 70) !important;
        border-color: rgb(82 82 91) !important;
        color: #fff !important;
        border-radius: 0.75rem !important;
        padding: 0.75rem 1rem !important;
        font-size: 1.125rem !important;
    }
    .phone-input-component input[type="tel"]::placeholder {
        color: rgb(161 161 170);
    }
    .phone-input-component input[type="tel"]:focus {
        ring: 2px solid rgb(59 130 246);
        border-color: rgb(59 130 246) !important;
    }
    .phone-input-component .phone-country-dropdown .sticky {
        background: rgb(39 39 42);
        border-color: rgb(63 63 70);
    }
</style>

<script src="<?= $baseUrl ?>/assets/js/phone-input.js"></script>
<script>
console.log('[Kiosk] Confirm page loaded');
console.log('[Kiosk] Type:', '<?= $type ?>', 'Staff:', '<?= $staffId ?>', 'Services:', '<?= $serviceIdsRaw ?>');

function goBack() {
    const adminUrl = '<?= $adminUrl ?>';
    const lang = '<?= $lang ?>';
    const type = '<?= $type ?>';
    const staff = '<?= $staffId ?>';
    window.location.href = adminUrl + '/kiosk/run/service?lang=' + lang + '&type=' + type + '&staff=' + staff;
}

// 중복 제출 방지
document.getElementById('checkinForm').addEventListener('submit', function(e) {
    const btn = document.getElementById('submitBtn');
    if (btn.disabled) { e.preventDefault(); return; }
    btn.disabled = true;
    btn.textContent = '...';
    console.log('[Kiosk] Submitting checkin');
});
</script>
