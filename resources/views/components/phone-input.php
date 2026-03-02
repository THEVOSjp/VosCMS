<?php
/**
 * 전화번호 입력 컴포넌트
 *
 * 사용법:
 * <?php
 * $phoneInputConfig = [
 *     'name' => 'phone',           // 필드명 (required)
 *     'id' => 'phone',             // ID 접두사 (optional, default: name)
 *     'label' => __('auth.register.phone'), // 라벨 (optional)
 *     'value' => '',               // 초기값 - 전체 전화번호 (optional)
 *     'country_code' => '+82',     // 초기 국가코드 (optional)
 *     'phone_number' => '',        // 초기 전화번호 (optional)
 *     'required' => false,         // 필수 여부 (optional)
 *     'hint' => '',                // 힌트 텍스트 (optional)
 *     'placeholder' => '010-1234-5678', // placeholder (optional)
 *     'show_label' => true,        // 라벨 표시 여부 (optional)
 * ];
 * include BASE_PATH . '/resources/views/components/phone-input.php';
 * ?>
 */

// 기본 설정
$config = $phoneInputConfig ?? [];
$name = $config['name'] ?? 'phone';
$idPrefix = $config['id'] ?? $name;
$label = $config['label'] ?? __('auth.register.phone');
$value = $config['value'] ?? '';
$countryCode = $config['country_code'] ?? '+82';
$phoneNumber = $config['phone_number'] ?? '';
$required = $config['required'] ?? false;
$hint = $config['hint'] ?? '';
$placeholder = $config['placeholder'] ?? '010-1234-5678';
$showLabel = $config['show_label'] ?? true;

// 언어별 우선 국가
$localePriority = [
    'ko' => 'kr',
    'ja' => 'jp',
    'en' => 'us',
];
$currentLocale = current_locale();
$priorityCountry = $localePriority[$currentLocale] ?? 'kr';

// 국가 목록
$countries = [
    // 아시아
    ['+82', 'kr'],
    ['+81', 'jp'],
    ['+86', 'cn'],
    ['+886', 'tw'],
    ['+852', 'hk'],
    ['+65', 'sg'],
    ['+66', 'th'],
    ['+84', 'vn'],
    ['+60', 'my'],
    ['+62', 'id'],
    ['+63', 'ph'],
    ['+91', 'in'],
    // 북미
    ['+1', 'us'],
    ['+1', 'ca'],
    // 유럽
    ['+44', 'gb'],
    ['+49', 'de'],
    ['+33', 'fr'],
    ['+39', 'it'],
    ['+34', 'es'],
    ['+31', 'nl'],
    ['+41', 'ch'],
    ['+7', 'ru'],
    // 오세아니아
    ['+61', 'au'],
    ['+64', 'nz'],
    // 중동
    ['+971', 'ae'],
    ['+966', 'sa'],
    // 남미
    ['+55', 'br'],
    ['+52', 'mx'],
];

// 국가 플래그 이모지 매핑
$flags = [
    'kr' => '🇰🇷', 'jp' => '🇯🇵', 'cn' => '🇨🇳', 'tw' => '🇹🇼', 'hk' => '🇭🇰',
    'sg' => '🇸🇬', 'th' => '🇹🇭', 'vn' => '🇻🇳', 'my' => '🇲🇾', 'id' => '🇮🇩',
    'ph' => '🇵🇭', 'in' => '🇮🇳', 'us' => '🇺🇸', 'ca' => '🇨🇦', 'gb' => '🇬🇧',
    'de' => '🇩🇪', 'fr' => '🇫🇷', 'it' => '🇮🇹', 'es' => '🇪🇸', 'nl' => '🇳🇱',
    'ch' => '🇨🇭', 'ru' => '🇷🇺', 'au' => '🇦🇺', 'nz' => '🇳🇿', 'ae' => '🇦🇪',
    'sa' => '🇸🇦', 'br' => '🇧🇷', 'mx' => '🇲🇽',
];

// 우선 국가를 맨 앞으로 정렬
usort($countries, function($a, $b) use ($priorityCountry) {
    if ($a[1] === $priorityCountry) return -1;
    if ($b[1] === $priorityCountry) return 1;
    return 0;
});

// 기본 선택 국가 정보
$defaultCountry = $countries[0];
$defaultCode = $countryCode ?: $defaultCountry[0];
$defaultKey = $defaultCountry[1];

// 현재 선택된 국가 찾기
foreach ($countries as $country) {
    if ($country[0] === $defaultCode) {
        $defaultKey = $country[1];
        break;
    }
}
?>
<div class="phone-input-component" data-id-prefix="<?= htmlspecialchars($idPrefix) ?>">
    <?php if ($showLabel): ?>
    <label for="<?= htmlspecialchars($idPrefix) ?>_number" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">
        <?= htmlspecialchars($label) ?>
        <?php if ($required): ?><span class="text-red-500">*</span><?php endif; ?>
    </label>
    <?php endif; ?>
    <div class="flex gap-2">
        <!-- 국가코드 선택 (커스텀 드롭다운) -->
        <div class="relative phone-country-dropdown-wrapper">
            <input type="hidden" name="<?= htmlspecialchars($name) ?>_country" id="<?= htmlspecialchars($idPrefix) ?>_country" value="<?= htmlspecialchars($defaultCode) ?>">
            <button type="button" class="phone-country-dropdown-btn flex flex-col items-center justify-center px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition cursor-pointer text-center min-w-[70px]">
                <span class="phone-selected-flag hidden"><?= $flags[$defaultKey] ?? '🏳️' ?></span>
                <span class="phone-selected-country text-[10px] text-gray-500 dark:text-zinc-400"><?= __('common.countries.' . $defaultKey) ?></span>
                <span class="phone-selected-code text-base font-bold text-gray-900 dark:text-white leading-tight"><?= htmlspecialchars($defaultCode) ?></span>
            </button>
            <div class="phone-country-dropdown hidden absolute left-0 top-full mt-1 w-44 bg-white dark:bg-zinc-800 rounded-lg shadow-lg border dark:border-zinc-700 py-1 z-50 max-h-64 overflow-y-auto">
                <?php foreach ($countries as $country): ?>
                <button type="button" class="phone-country-option w-full flex items-center gap-3 px-3 py-2 hover:bg-gray-100 dark:hover:bg-zinc-700 transition text-left"
                        data-code="<?= htmlspecialchars($country[0]) ?>"
                        data-flag="<?= $flags[$country[1]] ?? '🏳️' ?>"
                        data-key="<?= htmlspecialchars($country[1]) ?>"
                        data-name="<?= __('common.countries.' . $country[1]) ?>">
                    <span class="text-xl"><?= $flags[$country[1]] ?? '🏳️' ?></span>
                    <div class="flex flex-col">
                        <span class="text-sm text-gray-900 dark:text-white"><?= __('common.countries.' . $country[1]) ?></span>
                        <span class="text-xs text-gray-500 dark:text-zinc-400"><?= htmlspecialchars($country[0]) ?></span>
                    </div>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
        <!-- 전화번호 입력 -->
        <input type="tel" name="<?= htmlspecialchars($name) ?>_number" id="<?= htmlspecialchars($idPrefix) ?>_number"
               value="<?= htmlspecialchars($phoneNumber) ?>"
               class="flex-1 px-4 py-3 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
               placeholder="<?= htmlspecialchars($placeholder) ?>"
               maxlength="15"
               inputmode="tel"
               <?= $required ? 'required' : '' ?>>
        <!-- 조합된 전화번호 (hidden) -->
        <input type="hidden" name="<?= htmlspecialchars($name) ?>" id="<?= htmlspecialchars($idPrefix) ?>" value="<?= htmlspecialchars($value) ?>">
    </div>
    <?php if ($hint): ?>
    <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1"><?= htmlspecialchars($hint) ?></p>
    <?php endif; ?>
</div>
