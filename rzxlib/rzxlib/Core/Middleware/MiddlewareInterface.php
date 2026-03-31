<?php

declare(strict_types=1);

namespace RzxLib\Core\Middleware;

use RzxLib\Core\Http\Request;
use RzxLib\Core\Http\Response;
use Closure;

/**
 * MiddlewareInterface - 미들웨어 인터페이스
 *
 * 모든 미들웨어가 구현해야 하는 인터페이스
 *
 * @package RzxLib\Core\Middleware
 */
interface MiddlewareInterface
{
    /**
     * 요청 처리
     *
     * @param Request $request HTTP 요청
     * @param Closure $next 다음 미들웨어/핸들러
     * @return Response HTTP 응답
     */
    public function handle(Request $request, Closure $next): Response;
}
