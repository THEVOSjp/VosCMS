<?php

declare(strict_types=1);

namespace RzxLib\Core\Validation;

/**
 * Validation Rules Helper
 *
 * 자주 사용되는 검증 규칙 조합을 위한 헬퍼 클래스
 *
 * @package RzxLib\Core\Validation
 */
class Rules
{
    /**
     * 필수 이메일 규칙
     */
    public static function email(): string
    {
        return 'required|email|max:255';
    }

    /**
     * 선택적 이메일 규칙
     */
    public static function emailNullable(): string
    {
        return 'nullable|email|max:255';
    }

    /**
     * 비밀번호 규칙
     */
    public static function password(int $min = 8): string
    {
        return "required|string|min:{$min}|password";
    }

    /**
     * 비밀번호 확인 규칙
     */
    public static function passwordConfirmed(int $min = 8): string
    {
        return "required|string|min:{$min}|confirmed";
    }

    /**
     * 이름 규칙
     */
    public static function name(int $min = 2, int $max = 50): string
    {
        return "required|string|min:{$min}|max:{$max}";
    }

    /**
     * 선택적 이름 규칙
     */
    public static function nameNullable(int $min = 2, int $max = 50): string
    {
        return "nullable|string|min:{$min}|max:{$max}";
    }

    /**
     * 전화번호 규칙
     */
    public static function phone(): string
    {
        return 'required|phone';
    }

    /**
     * 선택적 전화번호 규칙
     */
    public static function phoneNullable(): string
    {
        return 'nullable|phone';
    }

    /**
     * 날짜 규칙
     */
    public static function date(string $format = 'Y-m-d'): string
    {
        return "required|date_format:{$format}";
    }

    /**
     * 선택적 날짜 규칙
     */
    public static function dateNullable(string $format = 'Y-m-d'): string
    {
        return "nullable|date_format:{$format}";
    }

    /**
     * 시간 규칙
     */
    public static function time(string $format = 'H:i'): string
    {
        return "required|date_format:{$format}";
    }

    /**
     * 날짜시간 규칙
     */
    public static function datetime(string $format = 'Y-m-d H:i:s'): string
    {
        return "required|date_format:{$format}";
    }

    /**
     * 정수 규칙
     */
    public static function integer(?int $min = null, ?int $max = null): string
    {
        $rules = 'required|integer';

        if ($min !== null) {
            $rules .= "|min:{$min}";
        }

        if ($max !== null) {
            $rules .= "|max:{$max}";
        }

        return $rules;
    }

    /**
     * 선택적 정수 규칙
     */
    public static function integerNullable(?int $min = null, ?int $max = null): string
    {
        $rules = 'nullable|integer';

        if ($min !== null) {
            $rules .= "|min:{$min}";
        }

        if ($max !== null) {
            $rules .= "|max:{$max}";
        }

        return $rules;
    }

    /**
     * 숫자 규칙
     */
    public static function numeric(?float $min = null, ?float $max = null): string
    {
        $rules = 'required|numeric';

        if ($min !== null) {
            $rules .= "|min:{$min}";
        }

        if ($max !== null) {
            $rules .= "|max:{$max}";
        }

        return $rules;
    }

    /**
     * 불리언 규칙
     */
    public static function boolean(): string
    {
        return 'required|boolean';
    }

    /**
     * 선택적 불리언 규칙
     */
    public static function booleanNullable(): string
    {
        return 'nullable|boolean';
    }

    /**
     * URL 규칙
     */
    public static function url(): string
    {
        return 'required|url';
    }

    /**
     * 선택적 URL 규칙
     */
    public static function urlNullable(): string
    {
        return 'nullable|url';
    }

    /**
     * 텍스트 규칙
     */
    public static function text(int $min = 1, int $max = 65535): string
    {
        return "required|string|min:{$min}|max:{$max}";
    }

    /**
     * 선택적 텍스트 규칙
     */
    public static function textNullable(int $min = 1, int $max = 65535): string
    {
        return "nullable|string|min:{$min}|max:{$max}";
    }

    /**
     * 배열 규칙
     */
    public static function array(): string
    {
        return 'required|array';
    }

    /**
     * 선택적 배열 규칙
     */
    public static function arrayNullable(): string
    {
        return 'nullable|array';
    }

    /**
     * 선택 항목 규칙 (in)
     */
    public static function in(array $values): string
    {
        return 'required|in:' . implode(',', $values);
    }

    /**
     * 선택적 선택 항목 규칙
     */
    public static function inNullable(array $values): string
    {
        return 'nullable|in:' . implode(',', $values);
    }

    /**
     * 슬러그 규칙 (URL 친화적 문자열)
     */
    public static function slug(int $min = 1, int $max = 255): string
    {
        return "required|alpha_dash|min:{$min}|max:{$max}";
    }

    /**
     * 사용자명 규칙
     */
    public static function username(int $min = 3, int $max = 20): string
    {
        return "required|alpha_dash|min:{$min}|max:{$max}";
    }

    /**
     * 예약 관련 규칙 모음
     */
    public static function reservation(): array
    {
        return [
            'service_id' => 'required|integer',
            'date' => 'required|date_format:Y-m-d',
            'time' => 'required|date_format:H:i',
            'name' => self::name(),
            'email' => self::email(),
            'phone' => self::phone(),
            'memo' => self::textNullable(0, 500),
        ];
    }

    /**
     * 사용자 등록 규칙 모음
     */
    public static function userRegistration(): array
    {
        return [
            'name' => self::name(),
            'email' => self::email(),
            'password' => self::passwordConfirmed(),
            'phone' => self::phoneNullable(),
        ];
    }

    /**
     * 사용자 로그인 규칙 모음
     */
    public static function userLogin(): array
    {
        return [
            'email' => self::email(),
            'password' => 'required|string',
            'remember' => self::booleanNullable(),
        ];
    }

    /**
     * 프로필 업데이트 규칙 모음
     */
    public static function profileUpdate(): array
    {
        return [
            'name' => self::nameNullable(),
            'phone' => self::phoneNullable(),
        ];
    }

    /**
     * 비밀번호 변경 규칙 모음
     */
    public static function passwordChange(): array
    {
        return [
            'current_password' => 'required|string',
            'password' => self::passwordConfirmed(),
        ];
    }

    /**
     * 비밀번호 재설정 규칙 모음
     */
    public static function passwordReset(): array
    {
        return [
            'email' => self::email(),
            'token' => 'required|string',
            'password' => self::passwordConfirmed(),
        ];
    }

    /**
     * 서비스 생성 규칙 모음
     */
    public static function serviceCreate(): array
    {
        return [
            'name' => self::name(1, 100),
            'description' => self::textNullable(0, 1000),
            'duration' => self::integer(15, 480),
            'price' => self::numeric(0),
            'is_active' => self::booleanNullable(),
        ];
    }

    /**
     * 연락처 문의 규칙 모음
     */
    public static function contactForm(): array
    {
        return [
            'name' => self::name(),
            'email' => self::email(),
            'subject' => 'required|string|max:200',
            'message' => self::text(10, 5000),
        ];
    }
}
