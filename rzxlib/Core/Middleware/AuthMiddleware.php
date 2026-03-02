<?php

declare(strict_types=1);

namespace RzxLib\Core\Middleware;

use RzxLib\Core\Http\Request;
use RzxLib\Core\Http\Response;
use RzxLib\Core\Http\RedirectResponse;
use Closure;

/**
 * AuthMiddleware - 인증 미들웨어
 *
 * 로그인된 사용자만 접근 허용
 *
 * @package RzxLib\Core\Middleware
 */
class AuthMiddleware implements MiddlewareInterface
{
    /**
     * 가드 이름
     */
    protected string $guard;

    /**
     * AuthMiddleware 생성자
     */
    public function __construct(string $guard = 'web')
    {
        $this->guard = $guard;
    }

    /**
     * 요청 처리
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->check()) {
            // AJAX 또는 JSON 요청인 경우
            if ($request->isAjax() || $request->wantsJson()) {
                return Response::error('인증이 필요합니다.', 401);
            }

            // 일반 요청인 경우 로그인 페이지로 리다이렉트
            $redirect = new RedirectResponse('/login');
            $redirect->with('intended', $request->fullUrl());
            $redirect->withError('로그인이 필요합니다.');

            return $redirect;
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
            return auth($this->guard)->check();
        }

        // 기본 세션 기반 확인
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}
