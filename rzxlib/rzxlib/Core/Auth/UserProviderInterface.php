<?php

declare(strict_types=1);

namespace RzxLib\Core\Auth;

/**
 * User Provider Interface
 *
 * 사용자 제공자가 구현해야 하는 인터페이스
 *
 * @package RzxLib\Core\Auth
 */
interface UserProviderInterface
{
    /**
     * ID로 사용자 조회
     */
    public function retrieveById(int|string $identifier): ?array;

    /**
     * Remember Token으로 사용자 조회
     */
    public function retrieveByToken(int|string $identifier, string $token): ?array;

    /**
     * Remember Token 업데이트
     */
    public function updateRememberToken(int|string $identifier, string $token): void;

    /**
     * 자격 증명으로 사용자 조회
     */
    public function retrieveByCredentials(array $credentials): ?array;

    /**
     * 자격 증명 검증
     */
    public function validateCredentials(array $user, array $credentials): bool;
}
