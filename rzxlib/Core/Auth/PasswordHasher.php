<?php

declare(strict_types=1);

namespace RzxLib\Core\Auth;

/**
 * Password Hasher
 *
 * 비밀번호 해싱 유틸리티 클래스
 *
 * @package RzxLib\Core\Auth
 */
class PasswordHasher
{
    /**
     * 기본 해싱 알고리즘
     */
    protected static int $algorithm = PASSWORD_BCRYPT;

    /**
     * 해싱 옵션
     */
    protected static array $options = [
        'cost' => 12,
    ];

    /**
     * 비밀번호 해시 생성
     */
    public static function hash(string $password): string
    {
        $hash = password_hash($password, self::$algorithm, self::$options);

        if ($hash === false) {
            throw new \RuntimeException('비밀번호 해싱에 실패했습니다.');
        }

        return $hash;
    }

    /**
     * 비밀번호 검증
     */
    public static function verify(string $password, string $hash): bool
    {
        if (empty($hash)) {
            return false;
        }

        return password_verify($password, $hash);
    }

    /**
     * 재해싱 필요 여부 확인
     */
    public static function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, self::$algorithm, self::$options);
    }

    /**
     * 해시 정보 반환
     */
    public static function info(string $hash): array
    {
        return password_get_info($hash);
    }

    /**
     * 알고리즘 설정
     */
    public static function setAlgorithm(int $algorithm): void
    {
        self::$algorithm = $algorithm;
    }

    /**
     * 옵션 설정
     */
    public static function setOptions(array $options): void
    {
        self::$options = array_merge(self::$options, $options);
    }

    /**
     * 비밀번호 강도 검증
     */
    public static function checkStrength(string $password): array
    {
        $errors = [];
        $strength = 0;

        // 최소 길이 확인
        if (strlen($password) < 8) {
            $errors[] = '비밀번호는 최소 8자 이상이어야 합니다.';
        } else {
            $strength++;
        }

        // 대문자 포함 확인
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = '대문자를 포함해야 합니다.';
        } else {
            $strength++;
        }

        // 소문자 포함 확인
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = '소문자를 포함해야 합니다.';
        } else {
            $strength++;
        }

        // 숫자 포함 확인
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = '숫자를 포함해야 합니다.';
        } else {
            $strength++;
        }

        // 특수문자 포함 확인
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $errors[] = '특수문자를 포함해야 합니다.';
        } else {
            $strength++;
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'strength' => $strength,
            'level' => match (true) {
                $strength >= 5 => 'strong',
                $strength >= 3 => 'medium',
                default => 'weak'
            }
        ];
    }

    /**
     * 임시 비밀번호 생성
     */
    public static function generateTemporary(int $length = 12): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';

        // 각 문자 타입에서 최소 1개씩 포함
        $password .= $chars[random_int(0, 25)];  // 소문자
        $password .= $chars[random_int(26, 51)]; // 대문자
        $password .= $chars[random_int(52, 61)]; // 숫자
        $password .= $chars[random_int(62, 69)]; // 특수문자

        // 나머지 랜덤 채우기
        for ($i = 4; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }

        // 문자열 섞기
        return str_shuffle($password);
    }
}
