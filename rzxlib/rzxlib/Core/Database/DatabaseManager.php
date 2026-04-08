<?php

declare(strict_types=1);

namespace RzxLib\Core\Database;

use RzxLib\Core\Application;

/**
 * Database Manager Class
 *
 * 다중 데이터베이스 연결을 관리하는 매니저 클래스
 *
 * @package RzxLib\Core\Database
 */
class DatabaseManager
{
    /**
     * 애플리케이션 인스턴스
     */
    protected Application $app;

    /**
     * 연결 인스턴스 저장소
     */
    protected array $connections = [];

    /**
     * DatabaseManager 생성자
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * 데이터베이스 연결 획득
     */
    public function connection(?string $name = null): Connection
    {
        $name = $name ?? $this->getDefaultConnection();

        if (!isset($this->connections[$name])) {
            $this->connections[$name] = $this->makeConnection($name);
        }

        return $this->connections[$name];
    }

    /**
     * 새 연결 생성
     */
    protected function makeConnection(string $name): Connection
    {
        $config = $this->getConfig($name);

        if ($config === null) {
            throw new \InvalidArgumentException("Database connection [{$name}] not configured.");
        }

        return new Connection($config);
    }

    /**
     * 연결 설정 반환
     */
    protected function getConfig(string $name): ?array
    {
        return $this->app->config("database.connections.{$name}");
    }

    /**
     * 기본 연결명 반환
     */
    public function getDefaultConnection(): string
    {
        return $this->app->config('database.default', 'mysql');
    }

    /**
     * 기본 연결명 설정
     */
    public function setDefaultConnection(string $name): void
    {
        $this->app->config['database']['default'] = $name;
    }

    /**
     * 테이블 쿼리 빌더 시작
     */
    public function table(string $table, ?string $connection = null): QueryBuilder
    {
        return $this->connection($connection)
            ->getQueryBuilder()
            ->table($table);
    }

    /**
     * 연결 해제
     */
    public function disconnect(?string $name = null): void
    {
        $name = $name ?? $this->getDefaultConnection();

        if (isset($this->connections[$name])) {
            $this->connections[$name]->disconnect();
            unset($this->connections[$name]);
        }
    }

    /**
     * 모든 연결 해제
     */
    public function disconnectAll(): void
    {
        foreach (array_keys($this->connections) as $name) {
            $this->disconnect($name);
        }
    }

    /**
     * 연결 재설정
     */
    public function reconnect(?string $name = null): Connection
    {
        $name = $name ?? $this->getDefaultConnection();
        $this->disconnect($name);

        return $this->connection($name);
    }

    /**
     * 트랜잭션 시작
     */
    public function beginTransaction(?string $connection = null): bool
    {
        return $this->connection($connection)->beginTransaction();
    }

    /**
     * 트랜잭션 커밋
     */
    public function commit(?string $connection = null): bool
    {
        return $this->connection($connection)->commit();
    }

    /**
     * 트랜잭션 롤백
     */
    public function rollBack(?string $connection = null): bool
    {
        return $this->connection($connection)->rollBack();
    }

    /**
     * 트랜잭션 내에서 콜백 실행
     */
    public function transaction(callable $callback, ?string $connection = null): mixed
    {
        return $this->connection($connection)->transaction($callback);
    }

    /**
     * 원시 SELECT 쿼리 실행
     */
    public function select(string $query, array $bindings = [], ?string $connection = null): array
    {
        return $this->connection($connection)->select($query, $bindings);
    }

    /**
     * 단일 행 SELECT 쿼리 실행
     */
    public function selectOne(string $query, array $bindings = [], ?string $connection = null): ?array
    {
        return $this->connection($connection)->selectOne($query, $bindings);
    }

    /**
     * 원시 INSERT 쿼리 실행
     */
    public function insert(string $query, array $bindings = [], ?string $connection = null): bool
    {
        return $this->connection($connection)->insert($query, $bindings);
    }

    /**
     * 원시 UPDATE 쿼리 실행
     */
    public function update(string $query, array $bindings = [], ?string $connection = null): int
    {
        return $this->connection($connection)->update($query, $bindings);
    }

    /**
     * 원시 DELETE 쿼리 실행
     */
    public function delete(string $query, array $bindings = [], ?string $connection = null): int
    {
        return $this->connection($connection)->delete($query, $bindings);
    }

    /**
     * 원시 쿼리 실행
     */
    public function statement(string $query, array $bindings = [], ?string $connection = null): bool
    {
        return $this->connection($connection)->statement($query, $bindings);
    }

    /**
     * 연결 목록 반환
     */
    public function getConnections(): array
    {
        return $this->connections;
    }

    /**
     * 동적 메서드 호출 (기본 연결로 위임)
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->connection()->$method(...$parameters);
    }
}
