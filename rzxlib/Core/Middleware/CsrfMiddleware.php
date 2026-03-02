<?php

declare(strict_types=1);

namespace RzxLib\Core\Middleware;

use RzxLib\Core\Http\Request;
use RzxLib\Core\Http\Response;
use Closure;

/**
 * CsrfMiddleware - CSRF 보호 미들웨어
 *
 * Cross-Site Request Forgery 공격 방지
 *
 * @package RzxLib\Core\Middleware
 */
class CsrfMiddleware implements MiddlewareInterface
{
    /**
     * CSRF 검사에서 제외할 URI
     */
    protected array $except = [];

    /**
     * 토큰 이름
     */
    protected string $tokenName = '_token';

    /**
     * 세션 키
     */
    protected string $sessionKey = '_csrf_token';

    /**
     * CsrfMiddleware 생성자
     */
    public function __construct(array $except = [])
    {
        $this->except = $except;
    }

    /**
     * 요청 처리
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 읽기 전용 메서드는 검사 스킵
        if ($this->isReading($request)) {
            return $next($request);
        }

        // 제외 목록 확인
        if ($this->inExceptArray($request)) {
            return $next($request);
        }

        // 토큰 검증
        if (!$this->tokensMatch($request)) {
            if ($request->isAjax() || $request->wantsJson()) {
                return Response::error('CSRF 토큰이 유효하지 않습니다.', 419);
            }

            // 일반 요청인 경우
            return Response::view(
                '<h1>419 - 페이지 만료</h1><p>세션이 만료되었습니다. 페이지를 새로고침 해주세요.</p>',
                419
            );
        }

        return $next($request);
    }

    /**
     * 읽기 전용 메서드인지 확인
     */
    protected function isReading(Request $request): bool
    {
        return in_array($request->method(), ['GET', 'HEAD', 'OPTIONS']);
    }

    /**
     * 제외 목록에 있는지 확인
     */
    protected function inExceptArray(Request $request): bool
    {
        $uri = $request->uri();

        foreach ($this->except as $except) {
            // 와일드카드 지원
            $pattern = str_replace('*', '.*', $except);

            if (preg_match('#^' . $pattern . '$#', $uri)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 토큰 매칭 확인
     */
    protected function tokensMatch(Request $request): bool
    {
        $token = $this->getTokenFromRequest($request);
        $sessionToken = $this->getTokenFromSession();

        return $token !== null
            && $sessionToken !== null
            && hash_equals($sessionToken, $token);
    }

    /**
     * 요청에서 토큰 가져오기
     */
    protected function getTokenFromRequest(Request $request): ?string
    {
        // POST 데이터에서
        $token = $request->input($this->tokenName);

        // 헤더에서 (AJAX)
        if (!$token) {
            $token = $request->header('X-CSRF-TOKEN');
        }

        // X-XSRF-TOKEN 헤더 (암호화된 쿠키에서)
        if (!$token) {
            $token = $request->header('X-XSRF-TOKEN');
        }

        return $token;
    }

    /**
     * 세션에서 토큰 가져오기
     */
    protected function getTokenFromSession(): ?string
    {
        $this->startSession();

        return $_SESSION[$this->sessionKey] ?? null;
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
     * 새 토큰 생성
     */
    public static function generateToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $token = bin2hex(random_bytes(32));
        $_SESSION['_csrf_token'] = $token;

        return $token;
    }

    /**
     * 현재 토큰 반환 (없으면 생성)
     */
    public static function token(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['_csrf_token'])) {
            return self::generateToken();
        }

        return $_SESSION['_csrf_token'];
    }

    /**
     * 토큰 필드 HTML 생성
     */
    public static function tokenField(): string
    {
        return '<input type="hidden" name="_token" value="' . htmlspecialchars(self::token()) . '">';
    }

    /**
     * 토큰 메타 태그 HTML 생성 (AJAX용)
     */
    public static function tokenMeta(): string
    {
        return '<meta name="csrf-token" content="' . htmlspecialchars(self::token()) . '">';
    }
}
