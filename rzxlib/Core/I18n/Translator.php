<?php

declare(strict_types=1);

namespace RzxLib\Core\I18n {

/**
 * Translator - 다국어 번역 클래스
 *
 * @package RzxLib\Core\I18n
 */
class Translator
{
    /**
     * 현재 로케일
     */
    protected static string $locale = 'ko';

    /**
     * 폴백 로케일
     */
    protected static string $fallbackLocale = 'ko';

    /**
     * 지원 로케일 목록
     */
    protected static array $supportedLocales = ['ko', 'en', 'ja'];

    /**
     * 로드된 번역 데이터
     */
    protected static array $translations = [];

    /**
     * 번역 파일 경로
     */
    protected static string $langPath = '';

    /**
     * 초기화 여부
     */
    protected static bool $initialized = false;

    /**
     * 초기화
     */
    public static function init(string $langPath, ?string $locale = null): void
    {
        // 이미 초기화된 경우 스킵
        if (self::$initialized) {
            return;
        }

        self::$langPath = rtrim($langPath, '/\\');

        if ($locale && in_array($locale, self::$supportedLocales)) {
            self::$locale = $locale;
            self::saveLocale($locale);
        } else {
            // 세션 또는 쿠키에서 로케일 확인, 없으면 브라우저 언어 감지
            $detectedLocale = self::detectLocale();
            self::$locale = $detectedLocale;

            // 처음 접속 시 (세션/쿠키에 없는 경우) 감지된 언어를 저장
            if (!self::hasStoredLocale()) {
                self::saveLocale($detectedLocale);
            }
        }

        self::$initialized = true;
    }

    /**
     * 저장된 로케일이 있는지 확인
     */
    protected static function hasStoredLocale(): bool
    {
        return (isset($_SESSION['locale']) && in_array($_SESSION['locale'], self::$supportedLocales))
            || (isset($_COOKIE['locale']) && in_array($_COOKIE['locale'], self::$supportedLocales));
    }

    /**
     * 로케일 저장 (세션 + 쿠키)
     */
    protected static function saveLocale(string $locale): void
    {
        if (in_array($locale, self::$supportedLocales)) {
            $_SESSION['locale'] = $locale;
            // 쿠키는 1년간 유지
            if (!headers_sent()) {
                setcookie('locale', $locale, [
                    'expires' => time() + (86400 * 365),
                    'path' => '/',
                    'httponly' => false,
                    'samesite' => 'Lax'
                ]);
            }
        }
    }

    /**
     * 로케일 감지
     */
    protected static function detectLocale(): string
    {
        // 1. 세션에서 확인 (가장 우선)
        if (isset($_SESSION['locale']) && in_array($_SESSION['locale'], self::$supportedLocales)) {
            return $_SESSION['locale'];
        }

        // 2. 쿠키에서 확인 (두 번째 우선)
        if (isset($_COOKIE['locale']) && in_array($_COOKIE['locale'], self::$supportedLocales)) {
            return $_COOKIE['locale'];
        }

        // 3. 브라우저 Accept-Language 헤더에서 감지 (처음 접속 시)
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            // Accept-Language 파싱 (예: "ko-KR,ko;q=0.9,en-US;q=0.8,en;q=0.7,ja;q=0.6")
            $acceptLangs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
            foreach ($acceptLangs as $lang) {
                // 품질 값 제거하고 언어 코드만 추출
                $langCode = strtolower(substr(trim(explode(';', $lang)[0]), 0, 2));
                if (in_array($langCode, self::$supportedLocales)) {
                    return $langCode;
                }
            }
        }

        // 4. 폴백 로케일 반환
        return self::$fallbackLocale;
    }

    /**
     * 현재 로케일 반환
     */
    public static function getLocale(): string
    {
        return self::$locale;
    }

    /**
     * 로케일 설정 (사용자가 명시적으로 언어 변경 시)
     */
    public static function setLocale(string $locale): void
    {
        if (in_array($locale, self::$supportedLocales)) {
            self::$locale = $locale;
            self::saveLocale($locale);
        }
    }

    /**
     * 지원 로케일 목록
     */
    public static function getSupportedLocales(): array
    {
        return self::$supportedLocales;
    }

    /**
     * 번역 파일 로드
     */
    protected static function loadTranslations(string $group, string $locale): array
    {
        $cacheKey = "{$locale}.{$group}";

        if (isset(self::$translations[$cacheKey])) {
            return self::$translations[$cacheKey];
        }

        $filePath = self::$langPath . "/{$locale}/{$group}.php";

        if (file_exists($filePath)) {
            self::$translations[$cacheKey] = require $filePath;
        } else {
            self::$translations[$cacheKey] = [];
        }

        return self::$translations[$cacheKey];
    }

    /**
     * 번역 가져오기
     *
     * @param string $key 번역 키 (예: 'common.welcome', 'booking.title')
     * @param array $replace 치환할 변수
     * @param string|null $locale 로케일 (null이면 현재 로케일)
     * @return string
     */
    public static function get(string $key, array $replace = [], ?string $locale = null): string
    {
        $locale = $locale ?? self::$locale;

        // 키 파싱 (group.key.subkey)
        $parts = explode('.', $key);
        $group = array_shift($parts);
        $keyPath = implode('.', $parts);

        // 번역 로드
        $translations = self::loadTranslations($group, $locale);

        // 키로 값 찾기
        $value = self::getNestedValue($translations, $keyPath);

        // 값이 없으면 폴백 로케일에서 찾기
        if ($value === null && $locale !== self::$fallbackLocale) {
            $fallbackTranslations = self::loadTranslations($group, self::$fallbackLocale);
            $value = self::getNestedValue($fallbackTranslations, $keyPath);
        }

        // 여전히 없으면 키 반환
        if ($value === null) {
            return $key;
        }

        // 변수 치환
        if (!empty($replace)) {
            foreach ($replace as $search => $replacement) {
                $value = str_replace(":{$search}", (string) $replacement, $value);
            }
        }

        return $value;
    }

    /**
     * 중첩된 배열에서 값 가져오기
     */
    protected static function getNestedValue(array $array, string $key): ?string
    {
        if (empty($key)) {
            return is_string($array) ? $array : null;
        }

        $keys = explode('.', $key);
        $value = $array;

        foreach ($keys as $k) {
            if (!is_array($value) || !isset($value[$k])) {
                return null;
            }
            $value = $value[$k];
        }

        return is_string($value) ? $value : null;
    }

    /**
     * 번역이 존재하는지 확인
     */
    public static function has(string $key, ?string $locale = null): bool
    {
        $locale = $locale ?? self::$locale;

        $parts = explode('.', $key);
        $group = array_shift($parts);
        $keyPath = implode('.', $parts);

        $translations = self::loadTranslations($group, $locale);

        return self::getNestedValue($translations, $keyPath) !== null;
    }

    /**
     * 복수형 번역
     *
     * @param string $key 번역 키
     * @param int $count 개수
     * @param array $replace 치환할 변수
     * @return string
     */
    public static function choice(string $key, int $count, array $replace = []): string
    {
        $replace['count'] = $count;
        $value = self::get($key, $replace);

        // 복수형 처리 (|로 구분)
        if (str_contains($value, '|')) {
            $parts = explode('|', $value);

            if ($count === 0 && isset($parts[0])) {
                $value = trim($parts[0]);
            } elseif ($count === 1 && isset($parts[1])) {
                $value = trim($parts[1]);
            } elseif (isset($parts[2])) {
                $value = trim($parts[2]);
            } else {
                $value = trim(end($parts));
            }
        }

        return $value;
    }

    /**
     * 로케일별 이름 가져오기
     */
    public static function getLocaleName(string $locale): string
    {
        return match ($locale) {
            'ko' => '한국어',
            'en' => 'English',
            'ja' => '日本語',
            default => $locale,
        };
    }

    /**
     * 모든 로케일 정보 가져오기
     */
    public static function getAllLocales(): array
    {
        return array_map(function ($locale) {
            return [
                'code' => $locale,
                'name' => self::getLocaleName($locale),
                'active' => $locale === self::$locale,
            ];
        }, self::$supportedLocales);
    }
}

} // end namespace RzxLib\Core\I18n

// ============================================================================
// 전역 헬퍼 함수 (전역 네임스페이스)
// ============================================================================
namespace {
    use RzxLib\Core\I18n\Translator;

    if (!function_exists('__')) {
        /**
         * 번역 헬퍼 함수
         *
         * @param string $key 번역 키
         * @param array $replace 치환 변수
         * @return string
         */
        function __(string $key, array $replace = []): string
        {
            return Translator::get($key, $replace);
        }
    }

    if (!function_exists('trans')) {
        /**
         * 번역 헬퍼 (alias)
         */
        function trans(string $key, array $replace = []): string
        {
            return Translator::get($key, $replace);
        }
    }

    if (!function_exists('trans_choice')) {
        /**
         * 복수형 번역 헬퍼
         */
        function trans_choice(string $key, int $count, array $replace = []): string
        {
            return Translator::choice($key, $count, $replace);
        }
    }

    if (!function_exists('current_locale')) {
        /**
         * 현재 로케일 반환
         */
        function current_locale(): string
        {
            return Translator::getLocale();
        }
    }
} // end global namespace
