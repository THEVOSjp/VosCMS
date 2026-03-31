<?php

declare(strict_types=1);

namespace RzxLib\Core\Middleware;

use RzxLib\Core\Http\Request;
use RzxLib\Core\Http\Response;
use RzxLib\Core\Http\RedirectResponse;
use Closure;

/**
 * GuestMiddleware - 게스트 전용 미들웨어
 *
 * 로그인하지 않은 사용자만 접근 허용 (로그인/회원가입 페이지 등)
 *
 * @package RzxLib\Core\Middleware
 */
class GuestMiddleware implements MiddlewareInterface
{
    /**
     * 리다이렉트 URL
     */
    protected string $redirectTo;

    /**
     * GuestMiddleware 생성자
     */
    public function __construct(string $redirectTo = '/')
    {
        $this->redirectTo = $redirectTo;
    }

    /**
     * 요청 처리
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->check()) {
            // AJAX 또는 JSON 요청인 경우
            if ($request->isAjax() || $request->wantsJson()) {
                return Response::error('이미 로그인되어 있습니다.', 403);
            }

            // 일반 요청인 경우 홈/대시보드로 리다이렉트
            return new RedirectResponse($this->redirectTo);
        }

        return $next($request);
    }

    /**
     * 인증 확인
     */
    protected function check(): bool
    {
        // 세션 시작
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // AuthManager 사용 가능한 경우
        if (function_exists('auth')) {
            return auth()->check();
        }

        // 기본 세션 기반 확인
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}
