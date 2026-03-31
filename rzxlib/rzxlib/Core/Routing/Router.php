<?php

declare(strict_types=1);

namespace RzxLib\Core\Routing;

use Closure;

/**
 * Router - HTTP 라우터
 *
 * HTTP 요청을 적절한 컨트롤러/액션으로 라우팅
 *
 * @package RzxLib\Core\Routing
 */
class Router
{
    /**
     * 등록된 라우트 컬렉션
     */
    protected RouteCollection $routes;

    /**
     * 현재 그룹 스택
     */
    protected array $groupStack = [];

    /**
     * 글로벌 미들웨어
     */
    protected array $middleware = [];

    /**
     * 라우트 이름 별칭
     */
    protected array $namedRoutes = [];

    /**
     * Router 생성자
     */
    public function __construct()
    {
        $this->routes = new RouteCollection();
    }

    /**
     * GET 라우트 등록
     */
    public function get(string $uri, array|Closure|string $action): Route
    {
        return $this->addRoute(['GET', 'HEAD'], $uri, $action);
    }

    /**
     * POST 라우트 등록
     */
    public function post(string $uri, array|Closure|string $action): Route
    {
        return $this->addRoute(['POST'], $uri, $action);
    }

    /**
     * PUT 라우트 등록
     */
    public function put(string $uri, array|Closure|string $action): Route
    {
        return $this->addRoute(['PUT'], $uri, $action);
    }

    /**
     * PATCH 라우트 등록
     */
    public function patch(string $uri, array|Closure|string $action): Route
    {
        return $this->addRoute(['PATCH'], $uri, $action);
    }

    /**
     * DELETE 라우트 등록
     */
    public function delete(string $uri, array|Closure|string $action): Route
    {
        return $this->addRoute(['DELETE'], $uri, $action);
    }

    /**
     * OPTIONS 라우트 등록
     */
    public function options(string $uri, array|Closure|string $action): Route
    {
        return $this->addRoute(['OPTIONS'], $uri, $action);
    }

    /**
     * 모든 HTTP 메서드에 대한 라우트 등록
     */
    public function any(string $uri, array|Closure|string $action): Route
    {
        return $this->addRoute(['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $uri, $action);
    }

    /**
     * 여러 HTTP 메서드에 대한 라우트 등록
     */
    public function match(array $methods, string $uri, array|Closure|string $action): Route
    {
        return $this->addRoute(array_map('strtoupper', $methods), $uri, $action);
    }

    /**
     * 라우트 그룹 정의
     */
    public function group(array $attributes, Closure $callback): void
    {
        $this->groupStack[] = $this->mergeGroupAttributes($attributes);

        $callback($this);

        array_pop($this->groupStack);
    }

    /**
     * 접두사 그룹
     */
    public function prefix(string $prefix): self
    {
        $this->groupStack[] = ['prefix' => $prefix];
        return $this;
    }

    /**
     * 미들웨어 그룹
     */
    public function middlewareGroup(array|string $middleware): self
    {
        $this->groupStack[] = ['middleware' => (array) $middleware];
        return $this;
    }

    /**
     * 라우트 추가
     */
    protected function addRoute(array $methods, string $uri, array|Closure|string $action): Route
    {
        $uri = $this->applyPrefix($uri);

        $route = new Route($methods, $uri, $action);

        // 그룹 미들웨어 적용
        $middleware = $this->getGroupMiddleware();
        if (!empty($middleware)) {
            $route->middleware($middleware);
        }

        $this->routes->add($route);

        return $route;
    }

    /**
     * URI에 접두사 적용
     */
    protected function applyPrefix(string $uri): string
    {
        $prefix = $this->getGroupPrefix();

        if (empty($prefix)) {
            return '/' . trim($uri, '/');
        }

        return '/' . trim($prefix, '/') . '/' . trim($uri, '/');
    }

    /**
     * 현재 그룹 접두사 가져오기
     */
    protected function getGroupPrefix(): string
    {
        $prefix = '';

        foreach ($this->groupStack as $group) {
            if (isset($group['prefix'])) {
                $prefix .= '/' . trim($group['prefix'], '/');
            }
        }

        return trim($prefix, '/');
    }

    /**
     * 현재 그룹 미들웨어 가져오기
     */
    protected function getGroupMiddleware(): array
    {
        $middleware = [];

        foreach ($this->groupStack as $group) {
            if (isset($group['middleware'])) {
                $middleware = array_merge($middleware, (array) $group['middleware']);
            }
        }

        return $middleware;
    }

    /**
     * 그룹 속성 병합
     */
    protected function mergeGroupAttributes(array $new): array
    {
        $old = end($this->groupStack) ?: [];

        return [
            'prefix' => trim(($old['prefix'] ?? '') . '/' . ($new['prefix'] ?? ''), '/'),
            'middleware' => array_merge($old['middleware'] ?? [], $new['middleware'] ?? []),
            'namespace' => $new['namespace'] ?? ($old['namespace'] ?? null),
        ];
    }

    /**
     * 요청에 매칭되는 라우트 찾기
     */
    public function match(string $method, string $uri): ?Route
    {
        return $this->routes->match($method, $uri);
    }

    /**
     * 요청 디스패치
     */
    public function dispatch(string $method, string $uri): mixed
    {
        $route = $this->routes->match($method, $uri);

        if ($route === null) {
            return null;
        }

        return $route;
    }

    /**
     * 이름으로 라우트 URL 생성
     */
    public function route(string $name, array $parameters = []): string
    {
        $route = $this->routes->getByName($name);

        if ($route === null) {
            throw new \InvalidArgumentException("라우트 [{$name}]를 찾을 수 없습니다.");
        }

        return $route->generateUrl($parameters);
    }

    /**
     * 라우트 컬렉션 반환
     */
    public function getRoutes(): RouteCollection
    {
        return $this->routes;
    }

    /**
     * 글로벌 미들웨어 추가
     */
    public function pushMiddleware(string $middleware): self
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    /**
     * 글로벌 미들웨어 반환
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * 리소스 라우트 등록 (CRUD)
     */
    public function resource(string $name, string $controller, array $options = []): void
    {
        $only = $options['only'] ?? ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'];
        $except = $options['except'] ?? [];

        $actions = array_diff($only, $except);

        if (in_array('index', $actions)) {
            $this->get($name, [$controller, 'index'])->name("{$name}.index");
        }

        if (in_array('create', $actions)) {
            $this->get("{$name}/create", [$controller, 'create'])->name("{$name}.create");
        }

        if (in_array('store', $actions)) {
            $this->post($name, [$controller, 'store'])->name("{$name}.store");
        }

        if (in_array('show', $actions)) {
            $this->get("{$name}/{id}", [$controller, 'show'])->name("{$name}.show");
        }

        if (in_array('edit', $actions)) {
            $this->get("{$name}/{id}/edit", [$controller, 'edit'])->name("{$name}.edit");
        }

        if (in_array('update', $actions)) {
            $this->put("{$name}/{id}", [$controller, 'update'])->name("{$name}.update");
            $this->patch("{$name}/{id}", [$controller, 'update']);
        }

        if (in_array('destroy', $actions)) {
            $this->delete("{$name}/{id}", [$controller, 'destroy'])->name("{$name}.destroy");
        }
    }

    /**
     * API 리소스 라우트 등록 (create, edit 제외)
     */
    public function apiResource(string $name, string $controller, array $options = []): void
    {
        $options['except'] = array_merge($options['except'] ?? [], ['create', 'edit']);
        $this->resource($name, $controller, $options);
    }
}
