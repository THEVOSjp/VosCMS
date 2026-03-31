<?php

declare(strict_types=1);

namespace RzxLib\Core\Validation;

/**
 * Validator Class
 *
 * 입력 데이터 유효성 검사 클래스
 *
 * @package RzxLib\Core\Validation
 */
class Validator
{
    /**
     * 검증할 데이터
     */
    protected array $data;

    /**
     * 검증 규칙
     */
    protected array $rules;

    /**
     * 사용자 정의 메시지
     */
    protected array $customMessages;

    /**
     * 속성 별명
     */
    protected array $customAttributes;

    /**
     * 검증 오류
     */
    protected array $errors = [];

    /**
     * 검증 통과 여부
     */
    protected bool $passed = false;

    /**
     * 기본 오류 메시지
     */
    protected array $defaultMessages = [
        'required' => ':attribute 필드는 필수입니다.',
        'email' => ':attribute 필드는 유효한 이메일 주소여야 합니다.',
        'min' => ':attribute 필드는 최소 :min 자 이상이어야 합니다.',
        'max' => ':attribute 필드는 최대 :max 자를 초과할 수 없습니다.',
        'between' => ':attribute 필드는 :min에서 :max 사이여야 합니다.',
        'numeric' => ':attribute 필드는 숫자여야 합니다.',
        'integer' => ':attribute 필드는 정수여야 합니다.',
        'string' => ':attribute 필드는 문자열이어야 합니다.',
        'array' => ':attribute 필드는 배열이어야 합니다.',
        'boolean' => ':attribute 필드는 참 또는 거짓이어야 합니다.',
        'confirmed' => ':attribute 확인이 일치하지 않습니다.',
        'same' => ':attribute 필드와 :other 필드가 일치해야 합니다.',
        'different' => ':attribute 필드와 :other 필드는 달라야 합니다.',
        'in' => '선택한 :attribute 값이 유효하지 않습니다.',
        'not_in' => '선택한 :attribute 값이 유효하지 않습니다.',
        'regex' => ':attribute 형식이 올바르지 않습니다.',
        'url' => ':attribute 필드는 유효한 URL이어야 합니다.',
        'date' => ':attribute 필드는 유효한 날짜여야 합니다.',
        'date_format' => ':attribute 필드가 :format 형식과 일치하지 않습니다.',
        'before' => ':attribute 필드는 :date 이전 날짜여야 합니다.',
        'after' => ':attribute 필드는 :date 이후 날짜여야 합니다.',
        'alpha' => ':attribute 필드는 문자만 포함해야 합니다.',
        'alpha_num' => ':attribute 필드는 문자와 숫자만 포함해야 합니다.',
        'alpha_dash' => ':attribute 필드는 문자, 숫자, 대시, 밑줄만 포함해야 합니다.',
        'unique' => ':attribute 값이 이미 사용 중입니다.',
        'exists' => '선택한 :attribute 값이 유효하지 않습니다.',
        'digits' => ':attribute 필드는 :digits 자리 숫자여야 합니다.',
        'digits_between' => ':attribute 필드는 :min에서 :max 자리 사이여야 합니다.',
        'phone' => ':attribute 필드는 유효한 전화번호여야 합니다.',
        'password' => ':attribute 필드는 대문자, 소문자, 숫자, 특수문자를 포함해야 합니다.',
        'nullable' => '',
        'sometimes' => '',
    ];

    /**
     * Validator 생성자
     */
    public function __construct(
        array $data,
        array $rules,
        array $customMessages = [],
        array $customAttributes = []
    ) {
        $this->data = $data;
        $this->rules = $rules;
        $this->customMessages = $customMessages;
        $this->customAttributes = $customAttributes;
    }

    /**
     * 검증 수행
     */
    public function validate(): bool
    {
        $this->errors = [];

        foreach ($this->rules as $attribute => $rules) {
            $this->validateAttribute($attribute, $this->parseRules($rules));
        }

        $this->passed = empty($this->errors);

        return $this->passed;
    }

    /**
     * 규칙 파싱
     */
    protected function parseRules(string|array $rules): array
    {
        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }

        return array_map(function ($rule) {
            if (is_string($rule) && str_contains($rule, ':')) {
                [$name, $parameters] = explode(':', $rule, 2);
                return ['name' => $name, 'parameters' => explode(',', $parameters)];
            }

            return ['name' => $rule, 'parameters' => []];
        }, $rules);
    }

    /**
     * 속성 검증
     */
    protected function validateAttribute(string $attribute, array $rules): void
    {
        $value = $this->getValue($attribute);

        // sometimes 규칙 처리
        if ($this->hasRule($rules, 'sometimes') && !$this->hasValue($attribute)) {
            return;
        }

        // nullable 규칙 처리
        if ($this->hasRule($rules, 'nullable') && $this->isEmpty($value)) {
            return;
        }

        foreach ($rules as $rule) {
            $ruleName = $rule['name'];
            $parameters = $rule['parameters'];

            // 건너뛸 규칙
            if (in_array($ruleName, ['nullable', 'sometimes'])) {
                continue;
            }

            $method = 'validate' . $this->studly($ruleName);

            if (method_exists($this, $method)) {
                if (!$this->$method($attribute, $value, $parameters)) {
                    $this->addError($attribute, $ruleName, $parameters);
                }
            }
        }
    }

    /**
     * 규칙 존재 여부 확인
     */
    protected function hasRule(array $rules, string $name): bool
    {
        foreach ($rules as $rule) {
            if ($rule['name'] === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * 값 조회
     */
    protected function getValue(string $attribute): mixed
    {
        return $this->data[$attribute] ?? null;
    }

    /**
     * 값 존재 여부 확인
     */
    protected function hasValue(string $attribute): bool
    {
        return array_key_exists($attribute, $this->data);
    }

    /**
     * 빈 값 확인
     */
    protected function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || (is_array($value) && empty($value));
    }

    /**
     * 오류 추가
     */
    protected function addError(string $attribute, string $rule, array $parameters = []): void
    {
        $message = $this->getMessage($attribute, $rule, $parameters);
        $this->errors[$attribute][] = $message;
    }

    /**
     * 오류 메시지 생성
     */
    protected function getMessage(string $attribute, string $rule, array $parameters): string
    {
        // 사용자 정의 메시지 확인
        $customKey = "{$attribute}.{$rule}";
        if (isset($this->customMessages[$customKey])) {
            $message = $this->customMessages[$customKey];
        } elseif (isset($this->customMessages[$rule])) {
            $message = $this->customMessages[$rule];
        } else {
            $message = $this->defaultMessages[$rule] ?? ':attribute 값이 유효하지 않습니다.';
        }

        // 속성명 교체
        $attributeName = $this->customAttributes[$attribute] ?? $attribute;
        $message = str_replace(':attribute', $attributeName, $message);

        // 파라미터 교체
        foreach ($parameters as $index => $param) {
            $message = str_replace(':' . $index, $param, $message);
        }

        // 특정 키 교체
        if (isset($parameters[0])) {
            $message = str_replace([':min', ':max', ':format', ':date', ':digits', ':other'], [$parameters[0], $parameters[1] ?? '', $parameters[0], $parameters[0], $parameters[0], $parameters[0]], $message);
        }

        return $message;
    }

    /**
     * StudlyCase 변환
     */
    protected function studly(string $value): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $value)));
    }

    // ==================== 검증 규칙 메서드 ====================

    /**
     * 필수 값 검증
     */
    protected function validateRequired(string $attribute, mixed $value, array $parameters): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_string($value) && trim($value) === '') {
            return false;
        }

        if (is_array($value) && empty($value)) {
            return false;
        }

        return true;
    }

    /**
     * 이메일 검증
     */
    protected function validateEmail(string $attribute, mixed $value, array $parameters): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * 최소 길이 검증
     */
    protected function validateMin(string $attribute, mixed $value, array $parameters): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }

        $min = (int) ($parameters[0] ?? 0);

        if (is_numeric($value)) {
            return $value >= $min;
        }

        if (is_string($value)) {
            return mb_strlen($value) >= $min;
        }

        if (is_array($value)) {
            return count($value) >= $min;
        }

        return false;
    }

    /**
     * 최대 길이 검증
     */
    protected function validateMax(string $attribute, mixed $value, array $parameters): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }

        $max = (int) ($parameters[0] ?? 0);

        if (is_numeric($value)) {
            return $value <= $max;
        }

        if (is_string($value)) {
            return mb_strlen($value) <= $max;
        }

        if (is_array($value)) {
            return count($value) <= $max;
        }

        return false;
    }

    /**
     * 범위 검증
     */
    protected function validateBetween(string $attribute, mixed $value, array $parameters): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }

        $min = (int) ($parameters[0] ?? 0);
        $max = (int) ($parameters[1] ?? 0);

        $size = is_numeric($value) ? $value : mb_strlen((string) $value);

        return $size >= $min && $size <= $max;
    }

    /**
     * 숫자 검증
     */
    protected function validateNumeric(string $attribute, mixed $value, array $parameters): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }

        return is_numeric($value);
    }

    /**
     * 정수 검증
     */
    protected function validateInteger(string $attribute, mixed $value, array $parameters): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * 문자열 검증
     */
    protected function validateString(string $attribute, mixed $value, array $parameters): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }

        return is_string($value);
    }

    /**
     * 배열 검증
     */
    protected function validateArray(string $attribute, mixed $value, array $parameters): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }

        return is_array($value);
    }

    /**
     * 불리언 검증
     */
    protected function validateBoolean(string $attribute, mixed $value, array $parameters): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }

        $acceptable = [true, false, 0, 1, '0', '1', 'true', 'false'];

        return in_array($value, $acceptable, true);
    }

    /**
     * 확인 필드 검증
     */
    protected function validateConfirmed(string $attribute, mixed $value, array $parameters): bool
    {
        $confirmationField = $attribute . '_confirmation';
        $confirmValue = $this->getValue($confirmationField);

        return $value === $confirmValue;
    }

    /**
     * 동일 값 검증
     */
    protected function validateSame(string $attribute, mixed $value, array $parameters): bool
    {
        $other = $parameters[0] ?? '';
        return $value === $this->getValue($other);
    }

    /**
     * 다른 값 검증
     */
    protected function validateDifferent(string $attribute, mixed $value, array $parameters): bool
    {
        $other = $parameters[0] ?? '';
        return $value !== $this->getValue($other);
    }

    /**
     * In 검증
     */
    protected function validateIn(string $attribute, mixed $value, array $parameters): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }

        return in_array($value, $parameters);
    }

    /**
     * Not In 검증
     */
    protected function validateNotIn(string $attribute, mixed $value, array $parameters): bool
    {
        return !in_array($value, $parameters);
    }

    /**
     * 정규식 검증
     */
    protected function validateRegex(string $attribute, mixed $value, array $parameters): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }

        $pattern = $parameters[0] ?? '';

        return preg_match($pattern, (string) $value) > 0;
    }

    /**
     * URL 검증
     */
    protected function validateUrl(string $attribute, mixed $value, array $parameters): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * 날짜 검증
     */
    protected function validateDate(string $attribute, mixed $value, array $parameters): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }

        return strtotime($value) !== false;
    }

    /**
     * 날짜 형식 검증
     */
    protected function validateDateFormat(string $attribute, mixed $value, array $parameters): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }

        $format = $parameters[0] ?? 'Y-m-d';
        $d = \DateTime::createFromFormat($format, $value);

        return $d && $d->format($format) === $value;
    }

    /**
     * 알파벳만 검증
     */
    protected function validateAlpha(string $attribute, mixed $value, array $parameters): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }

        return preg_match('/^[\pL\pM]+$/u', $value) > 0;
    }

    /**
     * 알파벳+숫자 검증
     */
    protected function validateAlphaNum(string $attribute, mixed $value, array $parameters): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }

        return preg_match('/^[\pL\pM\pN]+$/u', $value) > 0;
    }

    /**
     * 알파벳+숫자+대시+밑줄 검증
     */
    protected function validateAlphaDash(string $attribute, mixed $value, array $parameters): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }

        return preg_match('/^[\pL\pM\pN_-]+$/u', $value) > 0;
    }

    /**
     * 자릿수 검증
     */
    protected function validateDigits(string $attribute, mixed $value, array $parameters): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }

        $length = (int) ($parameters[0] ?? 0);

        return ctype_digit((string) $value) && strlen((string) $value) === $length;
    }

    /**
     * 자릿수 범위 검증
     */
    protected function validateDigitsBetween(string $attribute, mixed $value, array $parameters): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }

        $min = (int) ($parameters[0] ?? 0);
        $max = (int) ($parameters[1] ?? 0);
        $length = strlen((string) $value);

        return ctype_digit((string) $value) && $length >= $min && $length <= $max;
    }

    /**
     * 전화번호 검증
     */
    protected function validatePhone(string $attribute, mixed $value, array $parameters): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }

        // 한국/일본 전화번호 형식 지원
        return preg_match('/^[\d\-\+\(\)\s]{8,20}$/', $value) > 0;
    }

    /**
     * 비밀번호 강도 검증
     */
    protected function validatePassword(string $attribute, mixed $value, array $parameters): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }

        // 최소 8자, 대문자, 소문자, 숫자, 특수문자 포함
        return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $value) > 0;
    }

    // ==================== 결과 메서드 ====================

    /**
     * 검증 통과 여부
     */
    public function passes(): bool
    {
        if (empty($this->errors) && !$this->passed) {
            $this->validate();
        }

        return $this->passed;
    }

    /**
     * 검증 실패 여부
     */
    public function fails(): bool
    {
        return !$this->passes();
    }

    /**
     * 오류 메시지 반환
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * 첫 번째 오류 메시지 반환
     */
    public function first(?string $attribute = null): ?string
    {
        if ($attribute !== null) {
            return $this->errors[$attribute][0] ?? null;
        }

        foreach ($this->errors as $messages) {
            return $messages[0] ?? null;
        }

        return null;
    }

    /**
     * 모든 오류 메시지 (플랫)
     */
    public function all(): array
    {
        $messages = [];

        foreach ($this->errors as $attribute => $errors) {
            foreach ($errors as $error) {
                $messages[] = $error;
            }
        }

        return $messages;
    }

    /**
     * 검증된 데이터 반환
     */
    public function validated(): array
    {
        if (!$this->passes()) {
            throw new ValidationException($this);
        }

        return array_intersect_key($this->data, $this->rules);
    }

    /**
     * 안전하게 검증된 데이터 반환
     */
    public function safe(): array
    {
        return $this->passes() ? array_intersect_key($this->data, $this->rules) : [];
    }
}
