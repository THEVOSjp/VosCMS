<?php

declare(strict_types=1);

namespace RzxLib\Core\Auth;

use RzxLib\Core\Application;

/**
 * Auth Manager Class
 *
 * 인증 시스템을 관리하는 메인 매니저 클래스
 *
 * @package RzxLib\Core\Auth
 */
class AuthManager
{
    /**
     * 애플리케이션 인스턴스
     */
    protected Application $app;

    /**
     * 가드 인스턴스 저장소
     */
    protected array $guards = [];

    /**
     * 사용자 제공자 인스턴스 저장소
     */
    protected array $providers = [];

    /**
     * AuthManager 생성자
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * 가드 인스턴스 획득
     */
    public function guard(?string $name = null): GuardInterface
    {
        $name = $name ?? $this->getDefaultGuard();

        if (!isset($this->guards[$name])) {
            $this->guards[$name] = $this->resolveGuard($name);
        }

        return $this->guards[$name];
    }

    /**
     * 가드 인스턴스 생성
     */
    protected function resolveGuard(string $name): GuardInterface
    {
        $config = $this->getConfig($name);

        if ($config === null) {
            throw new \InvalidArgumentException("Auth guard [{$name}] is not defined.");
        }

        $driver = $config['driver'] ?? 'session';
        $provider = $this->createUserProvider($config['provider'] ?? 'users');

        return match ($driver) {
            'session' => new SessionGuard($name, $provider, $this->app),
            'jwt' => new JwtGuard($name, $provider, $this->app),
            default => throw new \InvalidArgumentException("Auth guard driver [{$driver}] is not supported.")
        };
    }

    /**
     * 사용자 제공자 생성
     */
    protected function createUserProvider(string $name): UserProviderInterface
    {
        $config = $this->app->config("auth.providers.{$name}");

        if ($config === null) {
            throw new \InvalidArgumentException("User provider [{$name}] is not defined.");
        }

        $driver = $config['driver'] ?? 'database';

        return match ($driver) {
            'database' => new DatabaseUserProvider(
                $this->app->make('db'),
                $config['table'] ?? 'users'
            ),
            default => throw new \InvalidArgumentException("User provider driver [{$driver}] is not supported.")
        };
    }

    /**
     * 가드 설정 반환
     */
    protected function getConfig(string $name): ?array
    {
        return $this->app->config("auth.guards.{$name}");
    }

    /**
     * 기본 가드명 반환
     */
    public function getDefaultGuard(): string
    {
        return $this->app->config('auth.defaults.guard', 'web');
    }

    /**
     * 기본 가드명 설정
     */
    public function setDefaultGuard(string $name): void
    {
        $this->app->config['auth']['defaults']['guard'] = $name;
    }

    /**
     * 현재 인증된 사용자 반환
     */
    public function user(): ?array
    {
        return $this->guard()->user();
    }

    /**
     * 사용자 인증 여부 확인
     */
    public function check(): bool
    {
        return $this->guard()->check();
    }

    /**
     * 게스트 여부 확인
     */
    public function guest(): bool
    {
        return !$this->check();
    }

    /**
     * 현재 인증된 사용자 ID 반환
     */
    public function id(): int|string|null
    {
        return $this->guard()->id();
    }

    /**
     * 자격 증명으로 로그인 시도
     */
    public function attempt(array $credentials, bool $remember = false): bool
    {
        return $this->guard()->attempt($credentials, $remember);
    }

    /**
     * 자격 증명 검증만 수행 (로그인 없이)
     */
    public function validate(array $credentials): bool
    {
        return $this->guard()->validate($credentials);
    }

    /**
     * 사용자 로그인
     */
    public function login(array $user, bool $remember = false): void
    {
        $this->guard()->login($user, $remember);
    }

    /**
     * ID로 사용자 로그인
     */
    public function loginUsingId(int|string $id, bool $remember = false): ?array
    {
        return $this->guard()->loginUsingId($id, $remember);
    }

    /**
     * 로그아웃
     */
    public function logout(): void
    {
        $this->guard()->logout();
    }

    /**
     * 비밀번호 해시 생성
     */
    public function hashPassword(string $password): string
    {
        return PasswordHasher::hash($password);
    }

    /**
     * 비밀번호 검증
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return PasswordHasher::verify($password, $hash);
    }

    /**
     * 비밀번호 재해싱 필요 여부 확인
     */
    public function needsRehash(string $hash): bool
    {
        return PasswordHasher::needsRehash($hash);
    }

    /**
     * 동적 메서드 호출 (기본 가드로 위임)
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->guard()->$method(...$parameters);
    }
}
