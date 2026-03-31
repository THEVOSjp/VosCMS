<?php

declare(strict_types=1);

namespace RzxLib\Core\Validation;

use Exception;

/**
 * Validation Exception
 *
 * 검증 실패 시 발생하는 예외
 *
 * @package RzxLib\Core\Validation
 */
class ValidationException extends Exception
{
    /**
     * Validator 인스턴스
     */
    public Validator $validator;

    /**
     * HTTP 응답 상태 코드
     */
    public int $status = 422;

    /**
     * 리다이렉트 경로
     */
    public ?string $redirectTo = null;

    /**
     * ValidationException 생성자
     */
    public function __construct(Validator $validator, ?string $message = null)
    {
        parent::__construct($message ?? '입력값 검증에 실패했습니다.');

        $this->validator = $validator;
    }

    /**
     * 검증 오류 반환
     */
    public function errors(): array
    {
        return $this->validator->errors();
    }

    /**
     * 첫 번째 오류 메시지 반환
     */
    public function first(?string $attribute = null): ?string
    {
        return $this->validator->first($attribute);
    }

    /**
     * 모든 오류 메시지 반환
     */
    public function all(): array
    {
        return $this->validator->all();
    }

    /**
     * 리다이렉트 경로 설정
     */
    public function redirectTo(string $path): static
    {
        $this->redirectTo = $path;
        return $this;
    }

    /**
     * HTTP 상태 코드 설정
     */
    public function status(int $status): static
    {
        $this->status = $status;
        return $this;
    }

    /**
     * JSON 응답용 배열 변환
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'errors' => $this->errors(),
        ];
    }

    /**
     * JSON 응답 반환
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }

    /**
     * 빠른 예외 생성 (정적)
     */
    public static function withMessages(array $messages): static
    {
        $validator = new Validator([], []);

        // 리플렉션을 사용하여 오류 설정
        $reflection = new \ReflectionProperty(Validator::class, 'errors');
        $reflection->setAccessible(true);
        $reflection->setValue($validator, $messages);

        return new static($validator);
    }
}
