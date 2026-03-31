<?php

declare(strict_types=1);

namespace RzxLib\Core\Auth;

use RzxLib\Core\Application;

/**
 * JWT Guard
 *
 * JWT 기반 API 인증 가드
 *
 * @package RzxLib\Core\Auth
 */
class JwtGuard implements GuardInterface
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
     * 현재 JWT 토큰
     */
    protected ?string $token = null;

    /**
     * JwtGuard 생성자
     */
    public function __construct(string $name, UserProviderInterface $provider, Application $app)
    {
        $this->name = $name;
        $this->provider = $provider;
        $this->app = $app;
    }

    /**
     * 현재 인증된 사용자 반환
     */
    public function user(): ?array
    {
        if ($this->user !== null) {
            return $this->user;
        }

        $token = $this->getTokenFromRequest();

        if ($token === null) {
            return null;
        }

        $payload = $this->validateToken($token);

        if ($payload === null) {
            return null;
        }

        $this->token = $token;
        $this->user = $this->provider->retrieveById($payload['sub']);

        return $this->user;
    }

    /**
     * 요청에서 토큰 추출
     */
    protected function getTokenFromRequest(): ?string
    {
        // Authorization 헤더에서 Bearer 토큰 추출
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }

        // 쿼리 파라미터에서 추출
        return $_GET['token'] ?? null;
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
     * 사용자 로그인 (토큰 생성)
     */
    public function login(array $user, bool $remember = false): void
    {
        $this->user = $user;
        $ttl = $remember ? $this->getRefreshTtl() : $this->getTtl();
        $this->token = $this->createToken($user, $ttl);
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
     * 로그아웃 (토큰 무효화)
     */
    public function logout(): void
    {
        // JWT는 무상태이므로 클라이언트에서 토큰 삭제 필요
        // 블랙리스트 구현이 필요한 경우 여기에 추가
        $this->user = null;
        $this->token = null;
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
     * 사용자 설정
     */
    public function setUser(array $user): void
    {
        $this->user = $user;
    }

    /**
     * JWT 토큰 생성
     */
    public function createToken(array $user, int $ttl = 60): string
    {
        $header = [
            'typ' => 'JWT',
            'alg' => $this->getAlgorithm(),
        ];

        $now = time();
        $payload = [
            'iss' => $this->app->config('app.url', 'localhost'),
            'iat' => $now,
            'exp' => $now + ($ttl * 60),
            'nbf' => $now,
            'sub' => $user['id'],
            'jti' => bin2hex(random_bytes(16)),
        ];

        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));

        $signature = $this->createSignature("{$headerEncoded}.{$payloadEncoded}");

        return "{$headerEncoded}.{$payloadEncoded}.{$signature}";
    }

    /**
     * JWT 토큰 검증
     */
    public function validateToken(string $token): ?array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        [$headerEncoded, $payloadEncoded, $signature] = $parts;

        // 서명 검증
        $expectedSignature = $this->createSignature("{$headerEncoded}.{$payloadEncoded}");

        if (!hash_equals($expectedSignature, $signature)) {
            return null;
        }

        $payload = json_decode($this->base64UrlDecode($payloadEncoded), true);

        if ($payload === null) {
            return null;
        }

        // 만료 확인
        if (isset($payload['exp']) && time() > $payload['exp']) {
            return null;
        }

        // nbf 확인
        if (isset($payload['nbf']) && time() < $payload['nbf']) {
            return null;
        }

        return $payload;
    }

    /**
     * 토큰 리프레시
     */
    public function refresh(): ?string
    {
        $user = $this->user();

        if ($user === null) {
            return null;
        }

        $this->token = $this->createToken($user, $this->getTtl());

        return $this->token;
    }

    /**
     * 서명 생성
     */
    protected function createSignature(string $data): string
    {
        $secret = $this->getSecret();

        return $this->base64UrlEncode(
            hash_hmac('sha256', $data, $secret, true)
        );
    }

    /**
     * Base64 URL 인코딩
     */
    protected function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64 URL 디코딩
     */
    protected function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * JWT 시크릿 키 반환
     */
    protected function getSecret(): string
    {
        return $this->app->config('auth.jwt.secret', '');
    }

    /**
     * JWT 알고리즘 반환
     */
    protected function getAlgorithm(): string
    {
        return $this->app->config('auth.jwt.algo', 'HS256');
    }

    /**
     * 토큰 TTL 반환 (분)
     */
    protected function getTtl(): int
    {
        return (int) $this->app->config('auth.jwt.ttl', 60);
    }

    /**
     * 리프레시 TTL 반환 (분)
     */
    protected function getRefreshTtl(): int
    {
        return (int) $this->app->config('auth.jwt.refresh_ttl', 20160);
    }

    /**
     * 현재 토큰 반환
     */
    public function getToken(): ?string
    {
        return $this->token;
    }

    /**
     * 토큰 설정
     */
    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    /**
     * 토큰 페이로드 반환
     */
    public function getPayload(): ?array
    {
        if ($this->token === null) {
            return null;
        }

        return $this->validateToken($this->token);
    }

    /**
     * 사용자 제공자 반환
     */
    public function getProvider(): UserProviderInterface
    {
        return $this->provider;
    }
}
