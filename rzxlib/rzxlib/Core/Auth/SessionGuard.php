<?php

declare(strict_types=1);

namespace RzxLib\Core\Auth;

use RzxLib\Core\Application;

/**
 * Session Guard
 *
 * 세션 기반 인증 가드
 *
 * @package RzxLib\Core\Auth
 */
class SessionGuard implements GuardInterface
{
    /**
     * 가드 이름
     */
    protected string $name;

    /**
     * 사용자 제공자
     */
    protected UserProviderInterface $provider;

    /**
     * 애플리케이션 인스턴스
     */
    protected Application $app;

    /**
     * 현재 인증된 사용자
     */
    protected ?array $user = null;

    /**
     * 사용자가 로드되었는지 여부
     */
    protected bool $userLoaded = false;

    /**
     * Remember 쿠키명
     */
    protected string $rememberCookieName = 'remember_token';

    /**
     * 세션 키 접두사
     */
    protected string $sessionKeyPrefix = 'auth_';

    /**
     * SessionGuard 생성자
     */
    public function __construct(string $name, UserProviderInterface $provider, Application $app)
    {
        $this->name = $name;
        $this->provider = $provider;
        $this->app = $app;

        $this->startSession();
    }

    /**
     * 세션 시작
     */
    protected function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * 세션 키 반환
     */
    protected function getSessionKey(): string
    {
        return $this->sessionKeyPrefix . $this->name;
    }

    /**
     * 현재 인증된 사용자 반환
     */
    public function user(): ?array
    {
        if ($this->userLoaded) {
            return $this->user;
        }

        $this->userLoaded = true;

        // 세션에서 사용자 ID 확인
        $id = $this->getSessionUserId();

        if ($id !== null) {
            $this->user = $this->provider->retrieveById($id);
            return $this->user;
        }

        // Remember 쿠키 확인
        $recaller = $this->getRecallerCookie();

        if ($recaller !== null) {
            $this->user = $this->userFromRecaller($recaller);

            if ($this->user !== null) {
                $this->updateSession($this->user['id']);
            }
        }

        return $this->user;
    }

    /**
     * 세션에서 사용자 ID 조회
     */
    protected function getSessionUserId(): int|string|null
    {
        return $_SESSION[$this->getSessionKey()] ?? null;
    }

    /**
     * 세션 업데이트
     */
    protected function updateSession(int|string $id): void
    {
        $_SESSION[$this->getSessionKey()] = $id;
    }

    /**
     * Remember 쿠키 조회
     */
    protected function getRecallerCookie(): ?string
    {
        return $_COOKIE[$this->rememberCookieName . '_' . $this->name] ?? null;
    }

    /**
     * Recaller에서 사용자 복원
     */
    protected function userFromRecaller(string $recaller): ?array
    {
        $parts = explode('|', $recaller);

        if (count($parts) !== 2) {
            return null;
        }

        [$id, $token] = $parts;

        return $this->provider->retrieveByToken($id, $token);
    }

    /**
     * 현재 인증된 사용자 ID 반환
     */
    public function id(): int|string|null
    {
        $user = $this->user();
        return $user['id'] ?? null;
    }

    /**
     * 사용자 인증 여부 확인
     */
    public function check(): bool
    {
        return $this->user() !== null;
    }

    /**
     * 게스트 여부 확인
     */
    public function guest(): bool
    {
        return !$this->check();
    }

    /**
     * 자격 증명으로 로그인 시도
     */
    public function attempt(array $credentials, bool $remember = false): bool
    {
        $user = $this->provider->retrieveByCredentials($credentials);

        if ($user !== null && $this->provider->validateCredentials($user, $credentials)) {
            $this->login($user, $remember);
            return true;
        }

        return false;
    }

    /**
     * 자격 증명 검증만 수행
     */
    public function validate(array $credentials): bool
    {
        $user = $this->provider->retrieveByCredentials($credentials);

        if ($user === null) {
            return false;
        }

        return $this->provider->validateCredentials($user, $credentials);
    }

    /**
     * 사용자 로그인
     */
    public function login(array $user, bool $remember = false): void
    {
        $this->updateSession($user['id']);

        if ($remember) {
            $this->createRememberCookie($user);
        }

        $this->user = $user;
        $this->userLoaded = true;
    }

    /**
     * ID로 사용자 로그인
     */
    public function loginUsingId(int|string $id, bool $remember = false): ?array
    {
        $user = $this->provider->retrieveById($id);

        if ($user !== null) {
            $this->login($user, $remember);
            return $user;
        }

        return null;
    }

    /**
     * Remember 쿠키 생성
     */
    protected function createRememberCookie(array $user): void
    {
        $token = bin2hex(random_bytes(32));

        $this->provider->updateRememberToken($user['id'], $token);

        $value = $user['id'] . '|' . $token;
        $expire = time() + (60 * 60 * 24 * 30); // 30일

        setcookie(
            $this->rememberCookieName . '_' . $this->name,
            $value,
            [
                'expires' => $expire,
                'path' => '/',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );
    }

    /**
     * 로그아웃
     */
    public function logout(): void
    {
        $user = $this->user();

        if ($user !== null) {
            $this->provider->updateRememberToken($user['id'], '');
        }

        // 세션에서 제거
        unset($_SESSION[$this->getSessionKey()]);

        // Remember 쿠키 삭제
        if (isset($_COOKIE[$this->rememberCookieName . '_' . $this->name])) {
            setcookie(
                $this->rememberCookieName . '_' . $this->name,
                '',
                [
                    'expires' => time() - 3600,
                    'path' => '/',
                    'secure' => isset($_SERVER['HTTPS']),
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]
            );
        }

        $this->user = null;
        $this->userLoaded = false;
    }

    /**
     * 세션 없이 한 번만 인증
     */
    public function once(array $credentials): bool
    {
        $user = $this->provider->retrieveByCredentials($credentials);

        if ($user !== null && $this->provider->validateCredentials($user, $credentials)) {
            $this->setUser($user);
            return true;
        }

        return false;
    }

    /**
     * 사용자 설정 (세션에 저장하지 않음)
     */
    public function setUser(array $user): void
    {
        $this->user = $user;
        $this->userLoaded = true;
    }

    /**
     * 사용자 제공자 반환
     */
    public function getProvider(): UserProviderInterface
    {
        return $this->provider;
    }

    /**
     * 비밀번호 확인
     */
    public function hasValidCredentials(?array $user, array $credentials): bool
    {
        if ($user === null) {
            return false;
        }

        return $this->provider->validateCredentials($user, $credentials);
    }

    /**
     * 세션 재생성
     */
    public function regenerateSession(): void
    {
        $id = $this->getSessionUserId();
        session_regenerate_id(true);

        if ($id !== null) {
            $this->updateSession($id);
        }
    }
}
