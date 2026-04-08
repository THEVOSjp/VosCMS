<?php
/**
 * RezlyX Language Module
 * DB에서 활성화된 언어 목록을 읽어 반환하는 모듈
 * 관리자/프론트엔드 공통으로 사용 (admin-topbar.php와 동일한 구조)
 */

namespace RzxLib\Core\Modules;

class LanguageModule
{
    /** @var array 기본 제공 언어 목록 (admin-topbar.php와 동일) */
    private static array $defaultLanguages = [
        'ko' => ['name' => '한국어', 'native' => '한국어'],
        'en' => ['name' => 'English', 'native' => 'English'],
        'ja' => ['name' => '日本語', 'native' => '日本語'],
        'zh_CN' => ['name' => '중국어(간체)', 'native' => '中文(中国)'],
        'zh_TW' => ['name' => '중국어(번체)', 'native' => '中文(臺灣)'],
        'de' => ['name' => '독일어', 'native' => 'Deutsch'],
        'es' => ['name' => '스페인어', 'native' => 'Español'],
        'fr' => ['name' => '프랑스어', 'native' => 'Français'],
        'mn' => ['name' => '몽골어', 'native' => 'Монгол'],
        'ru' => ['name' => '러시아어', 'native' => 'Русский'],
        'tr' => ['name' => '터키어', 'native' => 'Türkçe'],
        'vi' => ['name' => '베트남어', 'native' => 'Tiếng Việt'],
        'id' => ['name' => '인도네시아어', 'native' => 'Bahasa Indonesia'],
    ];

    /**
     * 언어 모듈 데이터 반환
     *
     * @param array $siteSettings DB의 rzx_settings 데이터
     * @param string $currentLocale 현재 로케일
     * @return array
     */
    public static function getData(array $siteSettings, string $currentLocale = 'ko'): array
    {
        // DB에서 활성 언어 코드 목록 (JSON 배열)
        $supportedCodes = ['ko', 'en', 'ja']; // 기본값
        if (!empty($siteSettings['supported_languages'])) {
            $decoded = json_decode($siteSettings['supported_languages'], true);
            if (is_array($decoded) && !empty($decoded)) {
                $supportedCodes = $decoded;
            }
        }

        // 커스텀 언어 (관리자가 추가한 언어)
        $customLanguages = [];
        if (!empty($siteSettings['custom_languages'])) {
            $decoded = json_decode($siteSettings['custom_languages'], true);
            if (is_array($decoded)) {
                $customLanguages = $decoded;
            }
        }

        // 전체 언어 목록 = 기본 + 커스텀 (admin-topbar.php와 동일)
        $allLanguages = array_merge(self::$defaultLanguages, $customLanguages);

        // 활성 언어만 필터링하여 languages 배열 구성
        // ['ko' => '한국어', 'en' => 'English', ...] 형태 (native 이름 사용)
        $languages = [];
        foreach ($supportedCodes as $code) {
            if (isset($allLanguages[$code])) {
                $languages[$code] = $allLanguages[$code]['native'] ?? strtoupper($code);
            } else {
                $languages[$code] = strtoupper($code);
            }
        }

        // 현재 언어 정보
        $currentLangInfo = $allLanguages[$currentLocale] ?? ['name' => strtoupper($currentLocale), 'native' => strtoupper($currentLocale)];

        return [
            'languages' => $languages,
            'allLanguages' => $allLanguages,
            'supportedCodes' => $supportedCodes,
            'currentLocale' => $currentLocale,
            'defaultLocale' => $siteSettings['default_language'] ?? 'ko',
            'currentLangInfo' => $currentLangInfo,
        ];
    }

    /**
     * 기본 제공 언어 목록 반환
     *
     * @return array
     */
    public static function getDefaultLanguages(): array
    {
        return self::$defaultLanguages;
    }
}
