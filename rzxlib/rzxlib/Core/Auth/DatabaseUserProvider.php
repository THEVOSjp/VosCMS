<?php

declare(strict_types=1);

namespace RzxLib\Core\Auth;

use RzxLib\Core\Database\DatabaseManager;

/**
 * Database User Provider
 *
 * 데이터베이스 기반 사용자 제공자
 *
 * @package RzxLib\Core\Auth
 */
class DatabaseUserProvider implements UserProviderInterface
{
    /**
     * 데이터베이스 매니저
     */
    protected DatabaseManager $db;

    /**
     * 사용자 테이블명
     */
    protected string $table;

    /**
     * DatabaseUserProvider 생성자
     */
    public function __construct(DatabaseManager $db, string $table = 'users')
    {
        $this->db = $db;
        $this->table = $table;
    }

    /**
     * ID로 사용자 조회
     */
    public function retrieveById(int|string $identifier): ?array
    {
        return $this->db->table($this->table)
            ->where('id', $identifier)
            ->first();
    }

    /**
     * Remember Token으로 사용자 조회
     */
    public function retrieveByToken(int|string $identifier, string $token): ?array
    {
        $user = $this->retrieveById($identifier);

        if ($user === null) {
            return null;
        }

        $rememberToken = $user['remember_token'] ?? '';

        return hash_equals($rememberToken, $token) ? $user : null;
    }

    /**
     * Remember Token 업데이트
     */
    public function updateRememberToken(int|string $identifier, string $token): void
    {
        $this->db->table($this->table)
            ->where('id', $identifier)
            ->update(['remember_token' => $token]);
    }

    /**
     * 자격 증명으로 사용자 조회
     */
    public function retrieveByCredentials(array $credentials): ?array
    {
        // 비밀번호는 조회 조건에서 제외
        $query = $this->db->table($this->table);

        foreach ($credentials as $key => $value) {
            if (str_contains($key, 'password')) {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->first();
    }

    /**
     * 자격 증명 검증
     */
    public function validateCredentials(array $user, array $credentials): bool
    {
        $password = $credentials['password'] ?? '';
        $hashedPassword = $user['password'] ?? '';

        return PasswordHasher::verify($password, $hashedPassword);
    }

    /**
     * 이메일로 사용자 조회
     */
    public function findByEmail(string $email): ?array
    {
        return $this->db->table($this->table)
            ->where('email', $email)
            ->first();
    }

    /**
     * 사용자 생성
     */
    public function create(array $data): int
    {
        // 비밀번호 해시
        if (isset($data['password'])) {
            $data['password'] = PasswordHasher::hash($data['password']);
        }

        // 생성 시간 설정
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
        $data['updated_at'] = $data['updated_at'] ?? date('Y-m-d H:i:s');

        return $this->db->table($this->table)->insertGetId($data);
    }

    /**
     * 사용자 업데이트
     */
    public function update(int|string $id, array $data): int
    {
        // 비밀번호가 있으면 해시
        if (isset($data['password'])) {
            $data['password'] = PasswordHasher::hash($data['password']);
        }

        // 업데이트 시간 설정
        $data['updated_at'] = $data['updated_at'] ?? date('Y-m-d H:i:s');

        return $this->db->table($this->table)
            ->where('id', $id)
            ->update($data);
    }

    /**
     * 사용자 삭제
     */
    public function delete(int|string $id): int
    {
        return $this->db->table($this->table)
            ->where('id', $id)
            ->delete();
    }

    /**
     * 이메일 존재 여부 확인
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $query = $this->db->table($this->table)
            ->where('email', $email);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * 비밀번호 재설정 토큰 생성
     */
    public function createPasswordResetToken(string $email): ?string
    {
        $user = $this->findByEmail($email);

        if ($user === null) {
            return null;
        }

        $token = bin2hex(random_bytes(32));

        $this->db->table('password_resets')->insert([
            'email' => $email,
            'token' => hash('sha256', $token),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $token;
    }

    /**
     * 비밀번호 재설정 토큰 검증
     */
    public function validatePasswordResetToken(string $email, string $token): bool
    {
        $record = $this->db->table('password_resets')
            ->where('email', $email)
            ->first();

        if ($record === null) {
            return false;
        }

        // 토큰 만료 확인 (기본 60분)
        $expireMinutes = 60;
        $createdAt = strtotime($record['created_at']);
        $expiresAt = $createdAt + ($expireMinutes * 60);

        if (time() > $expiresAt) {
            $this->deletePasswordResetToken($email);
            return false;
        }

        return hash_equals($record['token'], hash('sha256', $token));
    }

    /**
     * 비밀번호 재설정 토큰 삭제
     */
    public function deletePasswordResetToken(string $email): void
    {
        $this->db->table('password_resets')
            ->where('email', $email)
            ->delete();
    }
}
