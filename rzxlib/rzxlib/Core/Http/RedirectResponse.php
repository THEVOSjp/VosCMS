<?php

declare(strict_types=1);

namespace RzxLib\Core\Http;

/**
 * RedirectResponse - 리다이렉트 응답
 *
 * 리다이렉트 응답 및 플래시 데이터 지원
 *
 * @package RzxLib\Core\Http
 */
class RedirectResponse extends Response
{
    /**
     * 타겟 URL
     */
    protected string $targetUrl;

    /**
     * RedirectResponse 생성자
     */
    public function __construct(string $url, int $status = 302)
    {
        parent::__construct('', $status);

        $this->targetUrl = $url;
        $this->header('Location', $url);
    }

    /**
     * 타겟 URL 반환
     */
    public function getTargetUrl(): string
    {
        return $this->targetUrl;
    }

    /**
     * 플래시 메시지와 함께 리다이렉트
     */
    public function with(string $key, mixed $value): self
    {
        $this->startSession();
        $_SESSION['_flash'][$key] = $value;

        return $this;
    }

    /**
     * 여러 플래시 메시지와 함께 리다이렉트
     */
    public function withMany(array $data): self
    {
        foreach ($data as $key => $value) {
            $this->with($key, $value);
        }

        return $this;
    }

    /**
     * 성공 메시지와 함께 리다이렉트
     */
    public function withSuccess(string $message): self
    {
        return $this->with('success', $message);
    }

    /**
     * 에러 메시지와 함께 리다이렉트
     */
    public function withError(string $message): self
    {
        return $this->with('error', $message);
    }

    /**
     * 경고 메시지와 함께 리다이렉트
     */
    public function withWarning(string $message): self
    {
        return $this->with('warning', $message);
    }

    /**
     * 정보 메시지와 함께 리다이렉트
     */
    public function withInfo(string $message): self
    {
        return $this->with('info', $message);
    }

    /**
     * 검증 에러와 함께 리다이렉트
     */
    public function withErrors(array $errors): self
    {
        $this->startSession();
        $_SESSION['_errors'] = $errors;

        return $this;
    }

    /**
     * 이전 입력값과 함께 리다이렉트
     */
    public function withInput(array $input = []): self
    {
        $this->startSession();

        if (empty($input)) {
            $input = array_merge($_GET, $_POST);
        }

        // 민감한 필드 제거
        $except = ['password', 'password_confirmation', 'current_password'];
        foreach ($except as $field) {
            unset($input[$field]);
        }

        $_SESSION['_old_input'] = $input;

        return $this;
    }

    /**
     * 특정 입력값 제외하고 저장
     */
    public function withInputExcept(array $keys): self
    {
        $input = array_merge($_GET, $_POST);

        foreach ($keys as $key) {
            unset($input[$key]);
        }

        return $this->withInput($input);
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
     * 응답 전송
     */
    public function send(): void
    {
        // 리다이렉트는 세션을 저장하고 종료
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        parent::send();
        exit;
    }
}
