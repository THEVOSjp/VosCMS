<?php

declare(strict_types=1);

namespace RzxLib\Core\Routing;

use Countable;
use IteratorAggregate;
use ArrayIterator;

/**
 * RouteCollection - 라우트 컬렉션
 *
 * 등록된 라우트들을 관리하고 매칭
 *
 * @package RzxLib\Core\Routing
 */
class RouteCollection implements Countable, IteratorAggregate
{
    /**
     * 메서드별 라우트 저장
     */
    protected array $routes = [];

    /**
     * 모든 라우트 (플랫)
     */
    protected array $allRoutes = [];

    /**
     * 이름이 지정된 라우트
     */
    protected array $namedRoutes = [];

    /**
     * 라우트 추가
     */
    public function add(Route $route): Route
    {
        // 메서드별 저장
        foreach ($route->getMethods() as $method) {
            $this->routes[$method][] = $route;
        }

        // 전체 라우트 저장
        $this->allRoutes[] = $route;

        // 이름이 있으면 저장
        if ($route->getName() !== null) {
            $this->namedRoutes[$route->getName()] = $route;
        }

        return $route;
    }

    /**
     * 요청에 매칭되는 라우트 찾기
     */
    public function match(string $method, string $uri): ?Route
    {
        $method = strtoupper($method);
        $routes = $this->routes[$method] ?? [];

        foreach ($routes as $route) {
            if ($route->matches($method, $uri)) {
                return $route;
            }
        }

        // HEAD 요청인 경우 GET 라우트 확인
        if ($method === 'HEAD') {
            $routes = $this->routes['GET'] ?? [];

            foreach ($routes as $route) {
                if ($route->matches('GET', $uri)) {
                    return $route;
                }
            }
        }

        return null;
    }

    /**
     * 이름으로 라우트 가져오기
     */
    public function getByName(string $name): ?Route
    {
        return $this->namedRoutes[$name] ?? null;
    }

    /**
     * 이름이 존재하는지 확인
     */
    public function hasNamedRoute(string $name): bool
    {
        return isset($this->namedRoutes[$name]);
    }

    /**
     * 특정 메서드의 라우트 가져오기
     */
    public function getRoutesByMethod(string $method): array
    {
        return $this->routes[strtoupper($method)] ?? [];
    }

    /**
     * 모든 라우트 가져오기
     */
    public function getRoutes(): array
    {
        return $this->allRoutes;
    }

    /**
     * 이름이 지정된 라우트 목록
     */
    public function getNamedRoutes(): array
    {
        return $this->namedRoutes;
    }

    /**
     * 라우트 개수 반환
     */
    public function count(): int
    {
        return count($this->allRoutes);
    }

    /**
     * 이터레이터 반환
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->allRoutes);
    }

    /**
     * URI로 라우트 필터링
     */
    public function getByUri(string $uri): array
    {
        $uri = '/' . trim($uri, '/');

        return array_filter($this->allRoutes, function (Route $route) use ($uri) {
            return $route->getUri() === $uri;
        });
    }

    /**
     * 컨트롤러로 라우트 필터링
     */
    public function getByController(string $controller): array
    {
        return array_filter($this->allRoutes, function (Route $route) use ($controller) {
            return $route->getController() === $controller;
        });
    }

    /**
     * 디버그용 라우트 목록
     */
    public function toArray(): array
    {
        $result = [];

        foreach ($this->allRoutes as $route) {
            $result[] = [
                'methods' => $route->getMethods(),
                'uri' => $route->getUri(),
                'name' => $route->getName(),
                'action' => $this->formatAction($route),
                'middleware' => $route->getMiddleware(),
            ];
        }

        return $result;
    }

    /**
     * 액션 포맷팅
     */
    protected function formatAction(Route $route): string
    {
        $action = $route->getAction();

        if ($action instanceof \Closure) {
            return 'Closure';
        }

        if (is_array($action)) {
            $controller = is_string($action[0]) ? $action[0] : get_class($action[0]);
            return $controller . '@' . ($action[1] ?? '__invoke');
        }

        if (is_string($action)) {
            return $action;
        }

        return 'Unknown';
    }

    /**
     * 컬렉션 초기화
     */
    public function flush(): void
    {
        $this->routes = [];
        $this->allRoutes = [];
        $this->namedRoutes = [];
    }
}
