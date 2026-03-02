<?php

declare(strict_types=1);

namespace RzxLib\Core\Auth;

/**
 * Guard Interface
 *
 * 인증 가드가 구현해야 하는 인터페이스
 *
 * @package RzxLib\Core\Auth
 */
interface GuardInterface
{
    /**
     * 현재 인증된 사용자 반환
     */
    public function user(): ?array;

    /**
     * 현재 인증된 사용자 ID 반환
     */
    public function id(): int|string|null;

    /**
     * 사용자 인증 여부 확인
     */
    public function check(): bool;

    /**
     * 게스트 여부 확인
     */
    public function guest(): bool;

    /**
     * 자격 증명으로 로그인 시도
     */
    public function attempt(array $credentials, bool $remember = false): bool;

    /**
     * 자격 증명 검증만 수행
     */
    public function validate(array $credentials): bool;

    /**
     * 사용자 로그인
     */
    public function login(array $user, bool $remember = false): void;

    /**
     * ID로 사용자 로그인
     */
    public function loginUsingId(int|string $id, bool $remember = false): ?array;

    /**
     * 로그아웃
     */
    public function logout(): void;

    /**
     * 세션 없이 한 번만 인증
     */
    public function once(array $credentials): bool;

    /**
     * 사용자 설정 (세션에 저장하지 않음)
     */
    public function setUser(array $user): void;
}
