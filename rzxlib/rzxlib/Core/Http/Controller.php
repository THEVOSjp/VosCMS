<?php

declare(strict_types=1);

namespace RzxLib\Core\Http;

use RzxLib\Core\Validation\Validator;
use RzxLib\Core\Validation\ValidationException;

/**
 * Controller - 기본 컨트롤러
 *
 * 모든 컨트롤러의 기반 클래스
 *
 * @package RzxLib\Core\Http
 */
abstract class Controller
{
    /**
     * 미들웨어 목록
     */
    protected array $middleware = [];

    /**
     * 현재 요청
     */
    protected ?Request $request = null;

    /**
     * 미들웨어 등록
     */
    protected function middleware(array|string $middleware, array $options = []): self
    {
        $middleware = (array) $middleware;

        foreach ($middleware as $m) {
            $this->middleware[] = [
                'middleware' => $m,
                'options' => $options,
            ];
        }

        return $this;
    }

    /**
     * 등록된 미들웨어 반환
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * 요청 설정
     */
    public function setRequest(Request $request): self
    {
        $this->request = $request;
        return $this;
    }

    /**
     * 뷰 렌더링
     */
    protected function view(string $view, array $data = [], int $status = 200): Response
    {
        // 뷰 파일 경로 결정
        $viewPath = $this->resolveViewPath($view);

        if (!file_exists($viewPath)) {
            throw new \RuntimeException("뷰 파일을 찾을 수 없습니다: {$view}");
        }

        // 데이터를 변수로 추출
        extract($data);

        // 출력 버퍼링
        ob_start();
        include $viewPath;
        $content = ob_get_clean();

        return Response::view($content ?: '', $status);
    }

    /**
     * 뷰 경로 결정
     */
    protected function resolveViewPath(string $view): string
    {
        // 도트 표기법을 경로로 변환 (예: admin.dashboard -> admin/dashboard)
        $view = str_replace('.', '/', $view);

        // 기본 뷰 디렉토리
        $basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3);

        return $basePath . '/resources/views/' . $view . '.php';
    }

    /**
     * JSON 응답
     */
    protected function json(mixed $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    /**
     * 성공 JSON 응답
     */
    protected function success(mixed $data = null, string $message = '성공'): Response
    {
        return Response::success($data, $message);
    }

    /**
     * 에러 JSON 응답
     */
    protected function error(string $message, int $status = 400): Response
    {
        return Response::error($message, $status);
    }

    /**
     * 리다이렉트
     */
    protected function redirect(string $url, int $status = 302): RedirectResponse
    {
        return new RedirectResponse($url, $status);
    }

    /**
     * 이전 페이지로 리다이렉트
     */
    protected function back(): RedirectResponse
    {
        $referer = $this->request?->referer() ?? '/';
        return new RedirectResponse($referer);
    }

    /**
     * 이름 있는 라우트로 리다이렉트
     */
    protected function redirectRoute(string $name, array $parameters = []): RedirectResponse
    {
        // 라우터에서 URL 생성 (추후 구현)
        $url = $this->generateRouteUrl($name, $parameters);
        return new RedirectResponse($url);
    }

    /**
     * 라우트 URL 생성
     */
    protected function generateRouteUrl(string $name, array $parameters = []): string
    {
        // 간단한 구현 - 실제로는 Router와 연동 필요
        return '/' . $name . '?' . http_build_query($parameters);
    }

    /**
     * 입력값 검증
     */
    protected function validate(array $data, array $rules, array $messages = []): array
    {
        $validator = new Validator($data, $rules, $messages);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * 요청 입력값 검증
     */
    protected function validateRequest(array $rules, array $messages = []): array
    {
        if (!$this->request) {
            throw new \RuntimeException('요청 객체가 설정되지 않았습니다.');
        }

        return $this->request->validate($rules, $messages);
    }

    /**
     * 파일 다운로드
     */
    protected function download(string $filePath, ?string $name = null): Response
    {
        return Response::download($filePath, $name);
    }

    /**
     * 파일 응답
     */
    protected function file(string $filePath): Response
    {
        return Response::file($filePath);
    }

    /**
     * 빈 응답
     */
    protected function noContent(): Response
    {
        return Response::noContent();
    }

    /**
     * 404 응답
     */
    protected function notFound(string $message = '리소스를 찾을 수 없습니다.'): Response
    {
        return Response::error($message, 404);
    }

    /**
     * 401 응답
     */
    protected function unauthorized(string $message = '인증이 필요합니다.'): Response
    {
        return Response::error($message, 401);
    }

    /**
     * 403 응답
     */
    protected function forbidden(string $message = '권한이 없습니다.'): Response
    {
        return Response::error($message, 403);
    }

    /**
     * 세션에 플래시 메시지 저장
     */
    protected function flash(string $key, mixed $value): self
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['_flash'][$key] = $value;

        return $this;
    }

    /**
     * 세션에서 플래시 메시지 가져오기
     */
    protected function getFlash(string $key, mixed $default = null): mixed
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);

        return $value;
    }

    /**
     * 이전 입력값 저장
     */
    protected function withInput(array $input): self
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['_old_input'] = $input;

        return $this;
    }

    /**
     * 이전 입력값 가져오기
     */
    protected function old(string $key, mixed $default = null): mixed
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return $_SESSION['_old_input'][$key] ?? $default;
    }
}
