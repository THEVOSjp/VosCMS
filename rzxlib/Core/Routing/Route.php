<?php

declare(strict_types=1);

namespace RzxLib\Core\Routing;

use Closure;

/**
 * Route - 개별 라우트
 *
 * HTTP 라우트 정보 및 핸들링
 *
 * @package RzxLib\Core\Routing
 */
class Route
{
    /**
     * HTTP 메서드
     */
    protected array $methods;

    /**
     * URI 패턴
     */
    protected string $uri;

    /**
     * 액션 (컨트롤러 또는 클로저)
     */
    protected array|Closure|string $action;

    /**
     * 라우트 이름
     */
    protected ?string $name = null;

    /**
     * 미들웨어 목록
     */
    protected array $middleware = [];

    /**
     * URI 파라미터
     */
    protected array $parameters = [];

    /**
     * 파라미터 정규식 제약조건
     */
    protected array $wheres = [];

    /**
     * 컴파일된 정규식 패턴
     */
    protected ?string $compiled = null;

    /**
     * Route 생성자
     */
    public function __construct(array $methods, string $uri, array|Closure|string $action)
    {
        $this->methods = $methods;
        $this->uri = '/' . trim($uri, '/');
        $this->action = $action;
    }

    /**
     * 라우트 이름 설정
     */
    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * 미들웨어 추가
     */
    public function middleware(array|string $middleware): self
    {
        $this->middleware = array_merge($this->middleware, (array) $middleware);
        return $this;
    }

    /**
     * 미들웨어 목록 반환
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * 파라미터 제약조건 설정
     */
    public function where(array|string $name, ?string $expression = null): self
    {
        if (is_array($name)) {
            $this->wheres = array_merge($this->wheres, $name);
        } else {
            $this->wheres[$name] = $expression;
        }

        $this->compiled = null; // 재컴파일 필요

        return $this;
    }

    /**
     * 숫자만 허용하는 파라미터
     */
    public function whereNumber(string $name): self
    {
        return $this->where($name, '[0-9]+');
    }

    /**
     * 알파벳만 허용하는 파라미터
     */
    public function whereAlpha(string $name): self
    {
        return $this->where($name, '[a-zA-Z]+');
    }

    /**
     * 알파벳과 숫자만 허용하는 파라미터
     */
    public function whereAlphaNumeric(string $name): self
    {
        return $this->where($name, '[a-zA-Z0-9]+');
    }

    /**
     * UUID 형식 파라미터
     */
    public function whereUuid(string $name): self
    {
        return $this->where($name, '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}');
    }

    /**
     * 요청 URI와 매칭 확인
     */
    public function matches(string $method, string $uri): bool
    {
        // HTTP 메서드 확인
        if (!in_array(strtoupper($method), $this->methods)) {
            return false;
        }

        // URI 매칭
        $pattern = $this->compile();
        $uri = '/' . trim($uri, '/');

        if (preg_match($pattern, $uri, $matches)) {
            // 파라미터 추출
            $this->parameters = $this->extractParameters($matches);
            return true;
        }

        return false;
    }

    /**
     * 정규식 패턴 컴파일
     */
    protected function compile(): string
    {
        if ($this->compiled !== null) {
            return $this->compiled;
        }

        $pattern = $this->uri;

        // {param} 형태의 파라미터를 정규식으로 변환
        $pattern = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\??\}/', function ($matches) {
            $param = $matches[1];
            $optional = str_ends_with($matches[0], '?}');
            $regex = $this->wheres[$param] ?? '[^/]+';

            if ($optional) {
                return '(?:(' . $regex . '))?';
            }

            return '(' . $regex . ')';
        }, $pattern);

        $this->compiled = '#^' . $pattern . '$#u';

        return $this->compiled;
    }

    /**
     * 매치된 파라미터 추출
     */
    protected function extractParameters(array $matches): array
    {
        $parameters = [];

        // URI에서 파라미터 이름 추출
        preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)\??\}/', $this->uri, $paramNames);

        foreach ($paramNames[1] as $index => $name) {
            if (isset($matches[$index + 1]) && $matches[$index + 1] !== '') {
                $parameters[$name] = $matches[$index + 1];
            }
        }

        return $parameters;
    }

    /**
     * 파라미터 값 반환
     */
    public function parameter(string $name, mixed $default = null): mixed
    {
        return $this->parameters[$name] ?? $default;
    }

    /**
     * 모든 파라미터 반환
     */
    public function parameters(): array
    {
        return $this->parameters;
    }

    /**
     * 파라미터 설정
     */
    public function setParameter(string $name, mixed $value): self
    {
        $this->parameters[$name] = $value;
        return $this;
    }

    /**
     * URL 생성
     */
    public function generateUrl(array $parameters = []): string
    {
        $url = $this->uri;

        // 파라미터 치환
        $url = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\??\}/', function ($matches) use ($parameters) {
            $param = $matches[1];
            $optional = str_ends_with($matches[0], '?}');

            if (isset($parameters[$param])) {
                return (string) $parameters[$param];
            }

            if ($optional) {
                return '';
            }

            throw new \InvalidArgumentException("파라미터 [{$param}]이 필요합니다.");
        }, $url);

        // 중복 슬래시 정리
        return preg_replace('#/+#', '/', $url);
    }

    /**
     * 라우트 실행
     */
    public function run(): mixed
    {
        // 클로저인 경우
        if ($this->action instanceof Closure) {
            return call_user_func_array($this->action, array_values($this->parameters));
        }

        // [Controller::class, 'method'] 형태
        if (is_array($this->action)) {
            [$controller, $method] = $this->action;

            if (is_string($controller)) {
                $controller = new $controller();
            }

            return call_user_func_array([$controller, $method], array_values($this->parameters));
        }

        // 'Controller@method' 형태
        if (is_string($this->action) && str_contains($this->action, '@')) {
            [$controller, $method] = explode('@', $this->action);
            $controller = new $controller();

            return call_user_func_array([$controller, $method], array_values($this->parameters));
        }

        throw new \RuntimeException('유효하지 않은 라우트 액션입니다.');
    }

    /**
     * Getter 메서드들
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getAction(): array|Closure|string
    {
        return $this->action;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * 컨트롤러 클래스 반환
     */
    public function getController(): ?string
    {
        if (is_array($this->action)) {
            return is_string($this->action[0]) ? $this->action[0] : get_class($this->action[0]);
        }

        if (is_string($this->action) && str_contains($this->action, '@')) {
            return explode('@', $this->action)[0];
        }

        return null;
    }

    /**
     * 액션 메서드 반환
     */
    public function getActionMethod(): ?string
    {
        if (is_array($this->action)) {
            return $this->action[1] ?? null;
        }

        if (is_string($this->action) && str_contains($this->action, '@')) {
            return explode('@', $this->action)[1];
        }

        return null;
    }
}
