<?php

declare(strict_types=1);

namespace RzxLib\Core\Middleware;

use RzxLib\Core\Http\Request;
use RzxLib\Core\Http\Response;
use Closure;

/**
 * MiddlewareStack - 미들웨어 스택
 *
 * 미들웨어 파이프라인 관리 및 실행
 *
 * @package RzxLib\Core\Middleware
 */
class MiddlewareStack
{
    /**
     * 등록된 미들웨어 목록
     */
    protected array $middleware = [];

    /**
     * 미들웨어 별칭
     */
    protected array $aliases = [];

    /**
     * 미들웨어 그룹
     */
    protected array $groups = [];

    /**
     * MiddlewareStack 생성자
     */
    public function __construct()
    {
        $this->registerDefaultAliases();
    }

    /**
     * 기본 별칭 등록
     */
    protected function registerDefaultAliases(): void
    {
        $this->aliases = [
            'auth' => AuthMiddleware::class,
            'guest' => GuestMiddleware::class,
            'csrf' => CsrfMiddleware::class,
            'throttle' => ThrottleMiddleware::class,
        ];

        $this->groups = [
            'web' => [
                CsrfMiddleware::class,
            ],
            'api' => [
                ThrottleMiddleware::class,
            ],
        ];
    }

    /**
     * 미들웨어 추가
     */
    public function add(MiddlewareInterface|string $middleware): self
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    /**
     * 미들웨어 맨 앞에 추가
     */
    public function prepend(MiddlewareInterface|string $middleware): self
    {
        array_unshift($this->middleware, $middleware);
        return $this;
    }

    /**
     * 미들웨어 별칭 등록
     */
    public function alias(string $name, string $class): self
    {
        $this->aliases[$name] = $class;
        return $this;
    }

    /**
     * 미들웨어 그룹 등록
     */
    public function group(string $name, array $middleware): self
    {
        $this->groups[$name] = $middleware;
        return $this;
    }

    /**
     * 미들웨어 목록으로 스택 설정
     */
    public function set(array $middleware): self
    {
        $this->middleware = $middleware;
        return $this;
    }

    /**
     * 미들웨어 해제 (별칭/그룹 지원)
     */
    public function resolve(string|array $middleware): array
    {
        if (is_string($middleware)) {
            // 그룹인 경우
            if (isset($this->groups[$middleware])) {
                return $this->groups[$middleware];
            }

            // 별칭인 경우
            if (isset($this->aliases[$middleware])) {
                return [$this->aliases[$middleware]];
            }

            // 클래스명인 경우
            return [$middleware];
        }

        $resolved = [];
        foreach ($middleware as $m) {
            $resolved = array_merge($resolved, $this->resolve($m));
        }

        return $resolved;
    }

    /**
     * 파이프라인 실행
     */
    public function handle(Request $request, Closure $destination): Response
    {
        $pipeline = array_reduce(
            array_reverse($this->middleware),
            $this->createMiddlewareCarry(),
            $this->prepareDestination($destination)
        );

        return $pipeline($request);
    }

    /**
     * 미들웨어 체인 생성
     */
    protected function createMiddlewareCarry(): Closure
    {
        return function (Closure $next, MiddlewareInterface|string $middleware): Closure {
            return function (Request $request) use ($next, $middleware): Response {
                // 문자열인 경우 인스턴스 생성
                if (is_string($middleware)) {
                    $middleware = $this->resolveMiddleware($middleware);
                }

                return $middleware->handle($request, $next);
            };
        };
    }

    /**
     * 미들웨어 해석 및 인스턴스 생성
     */
    protected function resolveMiddleware(string $middleware): MiddlewareInterface
    {
        // 파라미터 파싱 (예: throttle:60,1)
        $parameters = [];
        if (str_contains($middleware, ':')) {
            [$middleware, $paramStr] = explode(':', $middleware, 2);
            $parameters = explode(',', $paramStr);
        }

        // 별칭 해석
        if (isset($this->aliases[$middleware])) {
            $middleware = $this->aliases[$middleware];
        }

        // 인스턴스 생성
        if (!class_exists($middleware)) {
            throw new \RuntimeException("미들웨어 클래스를 찾을 수 없습니다: {$middleware}");
        }

        $instance = new $middleware(...$parameters);

        if (!$instance instanceof MiddlewareInterface) {
            throw new \RuntimeException("미들웨어는 MiddlewareInterface를 구현해야 합니다: {$middleware}");
        }

        return $instance;
    }

    /**
     * 최종 목적지 준비
     */
    protected function prepareDestination(Closure $destination): Closure
    {
        return function (Request $request) use ($destination): Response {
            $result = $destination($request);

            // Response가 아닌 경우 래핑
            if (!$result instanceof Response) {
                if (is_array($result)) {
                    return Response::json($result);
                }
                return new Response((string) $result);
            }

            return $result;
        };
    }

    /**
     * 스택 초기화
     */
    public function flush(): self
    {
        $this->middleware = [];
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
     * 정적 실행 헬퍼
     */
    public static function through(array $middleware, Request $request, Closure $destination): Response
    {
        $stack = new static();
        $stack->set($middleware);

        return $stack->handle($request, $destination);
    }
}
