<?php

declare(strict_types=1);

namespace RzxLib\Core\Helpers;

/**
 * Encryption - 암호화/복호화 헬퍼 클래스
 *
 * AES-256-CBC 알고리즘을 사용하여 민감한 데이터를 암호화/복호화합니다.
 *
 * @package RzxLib\Core\Helpers
 */
class Encryption
{
    /**
     * 암호화 알고리즘
     */
    private const CIPHER = 'aes-256-cbc';

    /**
     * 암호화 키 캐시
     */
    private static ?string $key = null;

    /**
     * 암호화 키 가져오기
     *
     * @return string
     * @throws \RuntimeException
     */
    private static function getKey(): string
    {
        if (self::$key !== null) {
            return self::$key;
        }

        $key = $_ENV['APP_KEY'] ?? $_SERVER['APP_KEY'] ?? getenv('APP_KEY');

        if (empty($key)) {
            throw new \RuntimeException('APP_KEY 환경변수가 설정되지 않았습니다.');
        }

        // base64 인코딩된 키인 경우 디코딩
        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        // 키 길이가 32바이트가 아니면 해시하여 32바이트로 만듦
        if (strlen($key) !== 32) {
            $key = hash('sha256', $key, true);
        }

        self::$key = $key;
        return self::$key;
    }

    /**
     * 데이터 암호화
     *
     * @param string|null $value 암호화할 값
     * @return string|null 암호화된 값 (base64 인코딩)
     */
    public static function encrypt(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        try {
            $key = self::getKey();
            $ivLength = openssl_cipher_iv_length(self::CIPHER);
            $iv = openssl_random_pseudo_bytes($ivLength);

            $encrypted = openssl_encrypt(
                $value,
                self::CIPHER,
                $key,
                OPENSSL_RAW_DATA,
                $iv
            );

            if ($encrypted === false) {
                throw new \RuntimeException('암호화 실패');
            }

            // IV + 암호화된 데이터를 합쳐서 base64 인코딩
            $payload = base64_encode($iv . $encrypted);

            // 암호화된 데이터임을 표시하는 접두사 추가
            return 'enc:' . $payload;

        } catch (\Throwable $e) {
            error_log('Encryption error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 데이터 복호화
     *
     * @param string|null $value 복호화할 값 (base64 인코딩된 암호화 데이터)
     * @return string|null 복호화된 값
     */
    public static function decrypt(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        // 암호화되지 않은 데이터는 그대로 반환
        if (!str_starts_with($value, 'enc:')) {
            return $value;
        }

        try {
            $key = self::getKey();
            $payload = base64_decode(substr($value, 4));

            if ($payload === false) {
                return $value; // 복호화 실패 시 원본 반환
            }

            $ivLength = openssl_cipher_iv_length(self::CIPHER);

            // 페이로드가 IV보다 짧으면 유효하지 않은 데이터
            if (strlen($payload) <= $ivLength) {
                return $value;
            }

            $iv = substr($payload, 0, $ivLength);
            $encrypted = substr($payload, $ivLength);

            $decrypted = @openssl_decrypt(
                $encrypted,
                self::CIPHER,
                $key,
                OPENSSL_RAW_DATA,
                $iv
            );

            if ($decrypted === false) {
                return $value; // 복호화 실패 시 원본 반환
            }

            return $decrypted;

        } catch (\Throwable $e) {
            error_log('Decryption error: ' . $e->getMessage());
            return $value; // 에러 시 원본 반환
        }
    }

    /**
     * 값이 암호화되어 있는지 확인
     *
     * @param string|null $value
     * @return bool
     */
    public static function isEncrypted(?string $value): bool
    {
        if ($value === null) {
            return false;
        }
        return str_starts_with($value, 'enc:');
    }

    /**
     * 배열의 특정 필드들을 암호화
     *
     * @param array $data 데이터 배열
     * @param array $fields 암호화할 필드 목록
     * @return array 암호화된 데이터 배열
     */
    public static function encryptFields(array $data, array $fields): array
    {
        foreach ($fields as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = self::encrypt($data[$field]);
            }
        }
        return $data;
    }

    /**
     * 배열의 특정 필드들을 복호화
     *
     * @param array $data 데이터 배열
     * @param array $fields 복호화할 필드 목록
     * @return array 복호화된 데이터 배열
     */
    public static function decryptFields(array $data, array $fields): array
    {
        foreach ($fields as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = self::decrypt($data[$field]);
            }
        }
        return $data;
    }

    /**
     * 암호화 키 생성 (설치 시 사용)
     *
     * @return string base64 인코딩된 32바이트 키
     */
    public static function generateKey(): string
    {
        return 'base64:' . base64_encode(random_bytes(32));
    }
}
