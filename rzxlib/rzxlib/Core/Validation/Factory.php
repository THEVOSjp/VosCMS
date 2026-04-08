<?php

declare(strict_types=1);

namespace RzxLib\Core\Validation;

use RzxLib\Core\Application;

/**
 * Validation Factory
 *
 * Validator 인스턴스 생성 팩토리 클래스
 *
 * @package RzxLib\Core\Validation
 */
class Factory
{
    /**
     * 애플리케이션 인스턴스
     */
    protected Application $app;

    /**
     * 사용자 정의 검증 규칙
     */
    protected array $extensions = [];

    /**
     * 암묵적 규칙 (빈 값도 검사)
     */
    protected array $implicitExtensions = [];

    /**
     * 기본 커스텀 메시지
     */
    protected array $fallbackMessages = [];

    /**
     * Factory 생성자
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Validator 인스턴스 생성
     */
    public function make(
        array $data,
        array $rules,
        array $messages = [],
        array $customAttributes = []
    ): Validator {
        $messages = array_merge($this->fallbackMessages, $messages);

        $validator = new Validator($data, $rules, $messages, $customAttributes);

        // 커스텀 규칙 추가
        $this->addExtensions($validator);

        return $validator;
    }

    /**
     * 확장 규칙 추가
     */
    protected function addExtensions(Validator $validator): void
    {
        foreach ($this->extensions as $name => $extension) {
            $validator->addExtension($name, $extension);
        }

        foreach ($this->implicitExtensions as $name => $extension) {
            $validator->addImplicitExtension($name, $extension);
        }
    }

    /**
     * 사용자 정의 규칙 등록
     */
    public function extend(string $rule, callable $extension, ?string $message = null): void
    {
        $this->extensions[$rule] = $extension;

        if ($message !== null) {
            $this->fallbackMessages[$rule] = $message;
        }
    }

    /**
     * 암묵적 규칙 등록 (빈 값도 검사)
     */
    public function extendImplicit(string $rule, callable $extension, ?string $message = null): void
    {
        $this->implicitExtensions[$rule] = $extension;

        if ($message !== null) {
            $this->fallbackMessages[$rule] = $message;
        }
    }

    /**
     * 기본 메시지 교체
     */
    public function replacer(string $rule, string $message): void
    {
        $this->fallbackMessages[$rule] = $message;
    }

    /**
     * 빠른 검증 수행
     */
    public function validate(
        array $data,
        array $rules,
        array $messages = [],
        array $customAttributes = []
    ): array {
        return $this->make($data, $rules, $messages, $customAttributes)->validated();
    }

    /**
     * 검증 실패 시 예외 없이 결과 반환
     */
    public function validateSafe(
        array $data,
        array $rules,
        array $messages = [],
        array $customAttributes = []
    ): array {
        $validator = $this->make($data, $rules, $messages, $customAttributes);

        return [
            'valid' => $validator->passes(),
            'errors' => $validator->errors(),
            'data' => $validator->safe(),
        ];
    }
}
