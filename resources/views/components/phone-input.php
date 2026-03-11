<?php
/**
 * 전화번호 입력 컴포넌트 (국제전화번호)
 * 각 국가명은 해당 국가의 네이티브 언어로 표시됩니다.
 *
 * 사용법:
 * <?php
 * $phoneInputConfig = [
 *     'name' => 'phone',
 *     'id' => 'phone',
 *     'label' => '전화번호',
 *     'value' => '',
 *     'country_code' => '+82',
 *     'phone_number' => '',
 *     'required' => false,
 *     'hint' => '',
 *     'placeholder' => '010-1234-5678',
 *     'show_label' => true,
 * ];
 * include BASE_PATH . '/resources/views/components/phone-input.php';
 * ?>
 */

// 기본 설정 ($_phoneConf로 명명하여 외부 $config 변수 충돌 방지)
$_phoneConf = $phoneInputConfig ?? [];
$name = $_phoneConf['name'] ?? 'phone';
$idPrefix = $_phoneConf['id'] ?? $name;
$label = $_phoneConf['label'] ?? __('auth.register.phone');
$value = $_phoneConf['value'] ?? '';
$countryCode = $_phoneConf['country_code'] ?? '+82';
$phoneNumber = $_phoneConf['phone_number'] ?? '';
$required = $_phoneConf['required'] ?? false;
$hint = $_phoneConf['hint'] ?? '';
$placeholder = $_phoneConf['placeholder'] ?? '010-1234-5678';
$showLabel = $_phoneConf['show_label'] ?? true;

// 언어별 우선 국가
$localePriority = [
    'ko' => 'kr', 'ja' => 'jp', 'en' => 'us', 'zh' => 'cn',
    'es' => 'es', 'fr' => 'fr', 'de' => 'de', 'pt' => 'br',
    'ru' => 'ru', 'ar' => 'sa',
];
$currentLocale = function_exists('current_locale') ? current_locale() : 'ko';
$priorityCountry = $localePriority[$currentLocale] ?? 'kr';

// 국가 목록 [국가코드, ISO코드, 네이티브명]
$countries = [
    // 아시아-태평양
    ['+82', 'kr', '대한민국'],
    ['+81', 'jp', '日本'],
    ['+86', 'cn', '中国'],
    ['+886', 'tw', '臺灣'],
    ['+852', 'hk', '香港'],
    ['+853', 'mo', '澳門'],
    ['+65', 'sg', 'Singapore'],
    ['+66', 'th', 'ไทย'],
    ['+84', 'vn', 'Việt Nam'],
    ['+60', 'my', 'Malaysia'],
    ['+62', 'id', 'Indonesia'],
    ['+63', 'ph', 'Pilipinas'],
    ['+91', 'in', 'India'],
    ['+92', 'pk', 'Pakistan'],
    ['+93', 'af', 'Afghanistan'],
    ['+94', 'lk', 'Sri Lanka'],
    ['+95', 'mm', 'Myanmar'],
    ['+98', 'ir', 'Iran'],
    ['+880', 'bd', 'Bangladesh'],
    ['+856', 'la', 'Laos'],
    ['+855', 'kh', 'Cambodia'],
    ['+977', 'np', 'Nepal'],
    ['+976', 'mn', 'Mongolia'],
    ['+850', 'kp', '조선'],
    ['+975', 'bt', 'Bhutan'],
    ['+960', 'mv', 'Maldives'],
    ['+673', 'bn', 'Brunei'],
    ['+670', 'tl', 'Timor-Leste'],
    // 북미
    ['+1', 'us', 'United States'],
    ['+1', 'ca', 'Canada'],
    // 중남미
    ['+52', 'mx', 'México'],
    ['+55', 'br', 'Brasil'],
    ['+54', 'ar', 'Argentina'],
    ['+56', 'cl', 'Chile'],
    ['+57', 'co', 'Colombia'],
    ['+51', 'pe', 'Perú'],
    ['+58', 've', 'Venezuela'],
    ['+593', 'ec', 'Ecuador'],
    ['+591', 'bo', 'Bolivia'],
    ['+595', 'py', 'Paraguay'],
    ['+598', 'uy', 'Uruguay'],
    ['+506', 'cr', 'Costa Rica'],
    ['+507', 'pa', 'Panamá'],
    ['+503', 'sv', 'El Salvador'],
    ['+502', 'gt', 'Guatemala'],
    ['+504', 'hn', 'Honduras'],
    ['+505', 'ni', 'Nicaragua'],
    ['+53', 'cu', 'Cuba'],
    ['+1809', 'do', 'Rep. Dominicana'],
    ['+1876', 'jm', 'Jamaica'],
    ['+1868', 'tt', 'Trinidad'],
    // 유럽
    ['+44', 'gb', 'United Kingdom'],
    ['+49', 'de', 'Deutschland'],
    ['+33', 'fr', 'France'],
    ['+39', 'it', 'Italia'],
    ['+34', 'es', 'España'],
    ['+351', 'pt', 'Portugal'],
    ['+31', 'nl', 'Nederland'],
    ['+32', 'be', 'België'],
    ['+41', 'ch', 'Schweiz'],
    ['+43', 'at', 'Österreich'],
    ['+46', 'se', 'Sverige'],
    ['+47', 'no', 'Norge'],
    ['+45', 'dk', 'Danmark'],
    ['+358', 'fi', 'Suomi'],
    ['+354', 'is', 'Ísland'],
    ['+353', 'ie', 'Ireland'],
    ['+48', 'pl', 'Polska'],
    ['+420', 'cz', 'Česko'],
    ['+421', 'sk', 'Slovensko'],
    ['+36', 'hu', 'Magyarország'],
    ['+40', 'ro', 'România'],
    ['+359', 'bg', 'България'],
    ['+30', 'gr', 'Ελλάδα'],
    ['+90', 'tr', 'Türkiye'],
    ['+380', 'ua', 'Україна'],
    ['+7', 'ru', 'Россия'],
    ['+375', 'by', 'Беларусь'],
    ['+370', 'lt', 'Lietuva'],
    ['+371', 'lv', 'Latvija'],
    ['+372', 'ee', 'Eesti'],
    ['+385', 'hr', 'Hrvatska'],
    ['+386', 'si', 'Slovenija'],
    ['+381', 'rs', 'Србија'],
    ['+382', 'me', 'Crna Gora'],
    ['+387', 'ba', 'BiH'],
    ['+389', 'mk', 'Македонија'],
    ['+355', 'al', 'Shqipëria'],
    ['+383', 'xk', 'Kosova'],
    ['+352', 'lu', 'Luxembourg'],
    ['+377', 'mc', 'Monaco'],
    ['+378', 'sm', 'San Marino'],
    ['+376', 'ad', 'Andorra'],
    ['+350', 'gi', 'Gibraltar'],
    ['+356', 'mt', 'Malta'],
    ['+357', 'cy', 'Κύπρος'],
    // 오세아니아
    ['+61', 'au', 'Australia'],
    ['+64', 'nz', 'New Zealand'],
    ['+679', 'fj', 'Fiji'],
    ['+675', 'pg', 'Papua New Guinea'],
    ['+685', 'ws', 'Samoa'],
    ['+676', 'to', 'Tonga'],
    ['+678', 'vu', 'Vanuatu'],
    ['+674', 'nr', 'Nauru'],
    ['+686', 'ki', 'Kiribati'],
    ['+677', 'sb', 'Solomon Islands'],
    ['+691', 'fm', 'Micronesia'],
    ['+680', 'pw', 'Palau'],
    ['+692', 'mh', 'Marshall Islands'],
    // 중동
    ['+971', 'ae', 'UAE'],
    ['+966', 'sa', 'السعودية'],
    ['+965', 'kw', 'الكويت'],
    ['+973', 'bh', 'البحرين'],
    ['+974', 'qa', 'قطر'],
    ['+968', 'om', 'عُمان'],
    ['+962', 'jo', 'الأردن'],
    ['+961', 'lb', 'لبنان'],
    ['+963', 'sy', 'سوريا'],
    ['+964', 'iq', 'العراق'],
    ['+972', 'il', 'Israel'],
    ['+970', 'ps', 'فلسطين'],
    ['+967', 'ye', 'اليمن'],
    // 아프리카
    ['+20', 'eg', 'مصر'],
    ['+212', 'ma', 'المغرب'],
    ['+213', 'dz', 'الجزائر'],
    ['+216', 'tn', 'تونس'],
    ['+218', 'ly', 'ليبيا'],
    ['+234', 'ng', 'Nigeria'],
    ['+27', 'za', 'South Africa'],
    ['+254', 'ke', 'Kenya'],
    ['+255', 'tz', 'Tanzania'],
    ['+256', 'ug', 'Uganda'],
    ['+251', 'et', 'Ethiopia'],
    ['+233', 'gh', 'Ghana'],
    ['+225', 'ci', "Côte d'Ivoire"],
    ['+221', 'sn', 'Sénégal'],
    ['+237', 'cm', 'Cameroun'],
    ['+243', 'cd', 'RD Congo'],
    ['+242', 'cg', 'Congo'],
    ['+263', 'zw', 'Zimbabwe'],
    ['+260', 'zm', 'Zambia'],
    ['+258', 'mz', 'Moçambique'],
    ['+244', 'ao', 'Angola'],
    ['+249', 'sd', 'السودان'],
    ['+211', 'ss', 'South Sudan'],
    ['+252', 'so', 'Soomaaliya'],
    ['+253', 'dj', 'Djibouti'],
    ['+291', 'er', 'Eritrea'],
    ['+250', 'rw', 'Rwanda'],
    ['+257', 'bi', 'Burundi'],
    ['+265', 'mw', 'Malawi'],
    ['+266', 'ls', 'Lesotho'],
    ['+267', 'bw', 'Botswana'],
    ['+268', 'sz', 'Eswatini'],
    ['+264', 'na', 'Namibia'],
    ['+261', 'mg', 'Madagasikara'],
    ['+230', 'mu', 'Mauritius'],
    ['+248', 'sc', 'Seychelles'],
    ['+238', 'cv', 'Cabo Verde'],
    ['+220', 'gm', 'Gambia'],
    ['+224', 'gn', 'Guinée'],
    ['+231', 'lr', 'Liberia'],
    ['+232', 'sl', 'Sierra Leone'],
    ['+223', 'ml', 'Mali'],
    ['+227', 'ne', 'Niger'],
    ['+226', 'bf', 'Burkina Faso'],
    ['+228', 'tg', 'Togo'],
    ['+229', 'bj', 'Bénin'],
    ['+235', 'td', 'Tchad'],
    ['+241', 'ga', 'Gabon'],
    // 카리브해
    ['+1242', 'bs', 'Bahamas'],
    ['+1246', 'bb', 'Barbados'],
    ['+1268', 'ag', 'Antigua'],
    ['+1441', 'bm', 'Bermuda'],
    ['+1473', 'gd', 'Grenada'],
    ['+1671', 'gu', 'Guam'],
    ['+1758', 'lc', 'Saint Lucia'],
    ['+1767', 'dm', 'Dominica'],
    ['+1787', 'pr', 'Puerto Rico'],
    ['+1869', 'kn', 'St. Kitts'],
    // 기타
    ['+995', 'ge', 'Georgia'],
    ['+994', 'az', 'Azərbaycan'],
    ['+374', 'am', 'Armenia'],
    ['+7', 'kz', 'Қazaqstan'],
    ['+998', 'uz', "O'zbekiston"],
    ['+992', 'tj', 'Тоҷикистон'],
    ['+996', 'kg', 'Кыргызстан'],
    ['+993', 'tm', 'Türkmenistan'],
    ['+599', 'cw', 'Curaçao'],
    ['+590', 'gp', 'Guadeloupe'],
    ['+596', 'mq', 'Martinique'],
    ['+297', 'aw', 'Aruba'],
];

// 국가 플래그 이모지 매핑
$flags = [
    'kr' => '🇰🇷', 'jp' => '🇯🇵', 'cn' => '🇨🇳', 'tw' => '🇹🇼', 'hk' => '🇭🇰',
    'mo' => '🇲🇴', 'sg' => '🇸🇬', 'th' => '🇹🇭', 'vn' => '🇻🇳', 'my' => '🇲🇾',
    'id' => '🇮🇩', 'ph' => '🇵🇭', 'in' => '🇮🇳', 'pk' => '🇵🇰', 'af' => '🇦🇫',
    'lk' => '🇱🇰', 'mm' => '🇲🇲', 'ir' => '🇮🇷', 'bd' => '🇧🇩', 'la' => '🇱🇦',
    'kh' => '🇰🇭', 'np' => '🇳🇵', 'mn' => '🇲🇳', 'kp' => '🇰🇵', 'bt' => '🇧🇹',
    'mv' => '🇲🇻', 'bn' => '🇧🇳', 'tl' => '🇹🇱', 'us' => '🇺🇸', 'ca' => '🇨🇦',
    'mx' => '🇲🇽', 'br' => '🇧🇷', 'ar' => '🇦🇷', 'cl' => '🇨🇱', 'co' => '🇨🇴',
    'pe' => '🇵🇪', 've' => '🇻🇪', 'ec' => '🇪🇨', 'bo' => '🇧🇴', 'py' => '🇵🇾',
    'uy' => '🇺🇾', 'cr' => '🇨🇷', 'pa' => '🇵🇦', 'sv' => '🇸🇻', 'gt' => '🇬🇹',
    'hn' => '🇭🇳', 'ni' => '🇳🇮', 'cu' => '🇨🇺', 'do' => '🇩🇴', 'jm' => '🇯🇲',
    'tt' => '🇹🇹', 'gb' => '🇬🇧', 'de' => '🇩🇪', 'fr' => '🇫🇷', 'it' => '🇮🇹',
    'es' => '🇪🇸', 'pt' => '🇵🇹', 'nl' => '🇳🇱', 'be' => '🇧🇪', 'ch' => '🇨🇭',
    'at' => '🇦🇹', 'se' => '🇸🇪', 'no' => '🇳🇴', 'dk' => '🇩🇰', 'fi' => '🇫🇮',
    'is' => '🇮🇸', 'ie' => '🇮🇪', 'pl' => '🇵🇱', 'cz' => '🇨🇿', 'sk' => '🇸🇰',
    'hu' => '🇭🇺', 'ro' => '🇷🇴', 'bg' => '🇧🇬', 'gr' => '🇬🇷', 'tr' => '🇹🇷',
    'ua' => '🇺🇦', 'ru' => '🇷🇺', 'by' => '🇧🇾', 'lt' => '🇱🇹', 'lv' => '🇱🇻',
    'ee' => '🇪🇪', 'hr' => '🇭🇷', 'si' => '🇸🇮', 'rs' => '🇷🇸', 'me' => '🇲🇪',
    'ba' => '🇧🇦', 'mk' => '🇲🇰', 'al' => '🇦🇱', 'xk' => '🇽🇰', 'lu' => '🇱🇺',
    'mc' => '🇲🇨', 'sm' => '🇸🇲', 'ad' => '🇦🇩', 'gi' => '🇬🇮', 'mt' => '🇲🇹',
    'cy' => '🇨🇾', 'au' => '🇦🇺', 'nz' => '🇳🇿', 'fj' => '🇫🇯', 'pg' => '🇵🇬',
    'ws' => '🇼🇸', 'to' => '🇹🇴', 'vu' => '🇻🇺', 'nr' => '🇳🇷', 'ki' => '🇰🇮',
    'sb' => '🇸🇧', 'fm' => '🇫🇲', 'pw' => '🇵🇼', 'mh' => '🇲🇭', 'ae' => '🇦🇪',
    'sa' => '🇸🇦', 'kw' => '🇰🇼', 'bh' => '🇧🇭', 'qa' => '🇶🇦', 'om' => '🇴🇲',
    'jo' => '🇯🇴', 'lb' => '🇱🇧', 'sy' => '🇸🇾', 'iq' => '🇮🇶', 'il' => '🇮🇱',
    'ps' => '🇵🇸', 'ye' => '🇾🇪', 'eg' => '🇪🇬', 'ma' => '🇲🇦', 'dz' => '🇩🇿',
    'tn' => '🇹🇳', 'ly' => '🇱🇾', 'ng' => '🇳🇬', 'za' => '🇿🇦', 'ke' => '🇰🇪',
    'tz' => '🇹🇿', 'ug' => '🇺🇬', 'et' => '🇪🇹', 'gh' => '🇬🇭', 'ci' => '🇨🇮',
    'sn' => '🇸🇳', 'cm' => '🇨🇲', 'cd' => '🇨🇩', 'cg' => '🇨🇬', 'zw' => '🇿🇼',
    'zm' => '🇿🇲', 'mz' => '🇲🇿', 'ao' => '🇦🇴', 'sd' => '🇸🇩', 'ss' => '🇸🇸',
    'so' => '🇸🇴', 'dj' => '🇩🇯', 'er' => '🇪🇷', 'rw' => '🇷🇼', 'bi' => '🇧🇮',
    'mw' => '🇲🇼', 'ls' => '🇱🇸', 'bw' => '🇧🇼', 'sz' => '🇸🇿', 'na' => '🇳🇦',
    'mg' => '🇲🇬', 'mu' => '🇲🇺', 'sc' => '🇸🇨', 'cv' => '🇨🇻', 'gm' => '🇬🇲',
    'gn' => '🇬🇳', 'lr' => '🇱🇷', 'sl' => '🇸🇱', 'ml' => '🇲🇱', 'ne' => '🇳🇪',
    'bf' => '🇧🇫', 'tg' => '🇹🇬', 'bj' => '🇧🇯', 'td' => '🇹🇩', 'ga' => '🇬🇦',
    'bs' => '🇧🇸', 'bb' => '🇧🇧', 'ag' => '🇦🇬', 'bm' => '🇧🇲', 'gd' => '🇬🇩',
    'gu' => '🇬🇺', 'lc' => '🇱🇨', 'dm' => '🇩🇲', 'pr' => '🇵🇷', 'kn' => '🇰🇳',
    'ge' => '🇬🇪', 'az' => '🇦🇿', 'am' => '🇦🇲', 'kz' => '🇰🇿', 'uz' => '🇺🇿',
    'tj' => '🇹🇯', 'kg' => '🇰🇬', 'tm' => '🇹🇲', 'cw' => '🇨🇼', 'gp' => '🇬🇵',
    'mq' => '🇲🇶', 'aw' => '🇦🇼',
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
$defaultName = $defaultCountry[2];

// 현재 선택된 국가 찾기
foreach ($countries as $country) {
    if ($country[0] === $defaultCode) {
        $defaultKey = $country[1];
        $defaultName = $country[2];
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
            <button type="button" class="phone-country-dropdown-btn flex flex-col items-center justify-center px-3 py-2 bg-white dark:bg-zinc-700 border border-gray-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition cursor-pointer text-center min-w-[70px]">
                <span class="phone-selected-flag hidden"><?= $flags[$defaultKey] ?? '🏳️' ?></span>
                <span class="phone-selected-country text-[10px] text-gray-500 dark:text-zinc-400"><?= htmlspecialchars($defaultName) ?></span>
                <span class="phone-selected-code text-base font-bold text-gray-900 dark:text-white leading-tight"><?= htmlspecialchars($defaultCode) ?></span>
            </button>
            <div class="phone-country-dropdown hidden absolute left-0 top-full mt-1 w-52 bg-white dark:bg-zinc-800 rounded-lg shadow-lg border border-gray-200 dark:border-zinc-700 py-1 z-50 max-h-72 overflow-y-auto">
                <!-- 검색 입력창 -->
                <div class="sticky top-0 bg-white dark:bg-zinc-800 px-2 py-2 border-b border-gray-200 dark:border-zinc-700">
                    <input type="text" class="phone-country-search w-full px-3 py-2 text-sm bg-gray-50 dark:bg-zinc-700 border border-gray-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-zinc-400"
                           placeholder="<?= __('common.search') ?? 'Search' ?>">
                </div>
                <?php foreach ($countries as $country): ?>
                <button type="button" class="phone-country-option w-full flex items-center gap-3 px-3 py-2 hover:bg-gray-100 dark:hover:bg-zinc-700 transition text-left"
                        data-code="<?= htmlspecialchars($country[0]) ?>"
                        data-flag="<?= $flags[$country[1]] ?? '🏳️' ?>"
                        data-key="<?= htmlspecialchars($country[1]) ?>"
                        data-name="<?= htmlspecialchars($country[2]) ?>">
                    <span class="text-xl"><?= $flags[$country[1]] ?? '🏳️' ?></span>
                    <div class="flex flex-col flex-1 min-w-0">
                        <span class="text-sm text-gray-900 dark:text-white truncate"><?= htmlspecialchars($country[2]) ?></span>
                        <span class="text-xs text-gray-500 dark:text-zinc-400"><?= htmlspecialchars($country[0]) ?></span>
                    </div>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
        <!-- 전화번호 입력 -->
        <input type="tel" name="<?= htmlspecialchars($name) ?>_number" id="<?= htmlspecialchars($idPrefix) ?>_number"
               value="<?= htmlspecialchars($phoneNumber) ?>"
               class="flex-1 px-4 py-3 bg-white dark:bg-zinc-700 text-gray-900 dark:text-white border border-gray-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition placeholder-gray-400 dark:placeholder-zinc-400"
               placeholder="<?= htmlspecialchars($placeholder) ?>"
               maxlength="20"
               inputmode="tel"
               <?= $required ? 'required' : '' ?>>
        <!-- 조합된 전화번호 (hidden) -->
        <input type="hidden" name="<?= htmlspecialchars($name) ?>" id="<?= htmlspecialchars($idPrefix) ?>" value="<?= htmlspecialchars($value) ?>">
    </div>
    <?php if ($hint): ?>
    <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1"><?= htmlspecialchars($hint) ?></p>
    <?php endif; ?>
</div>
