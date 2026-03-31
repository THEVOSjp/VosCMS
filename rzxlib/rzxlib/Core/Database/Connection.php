<?php

declare(strict_types=1);

namespace RzxLib\Core\Database;

use PDO;
use PDOException;
use PDOStatement;

/**
 * Database Connection Class
 *
 * PDO 래퍼 클래스로 데이터베이스 연결 및 쿼리 실행을 관리합니다.
 *
 * @package RzxLib\Core\Database
 */
class Connection
{
    /**
     * 싱글톤 인스턴스
     */
    protected static ?Connection $instance = null;

    /**
     * PDO 인스턴스
     */
    protected ?PDO $pdo = null;

    /**
     * 연결 설정
     */
    protected array $config;

    /**
     * 테이블 접두사
     */
    protected string $prefix;

    /**
     * 쿼리 로그
     */
    protected array $queryLog = [];

    /**
     * 쿼리 로깅 활성화 여부
     */
    protected bool $loggingQueries = false;

    /**
     * 싱글톤 인스턴스 반환
     */
    public static function getInstance(): static
    {
        if (static::$instance === null) {
            static::$instance = static::createFromEnv();
        }

        return static::$instance;
    }

    /**
     * 환경 변수에서 연결 생성
     */
    protected static function createFromEnv(): static
    {
        $config = [
            'driver' => $_ENV['DB_CONNECTION'] ?? 'mysql',
            'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
            'port' => $_ENV['DB_PORT'] ?? '3306',
            'database' => $_ENV['DB_DATABASE'] ?? 'rezlyx',
            'username' => $_ENV['DB_USERNAME'] ?? 'root',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
            'collation' => $_ENV['DB_COLLATION'] ?? 'utf8mb4_unicode_ci',
            'prefix' => $_ENV['DB_PREFIX'] ?? 'rzx_',
        ];

        return new static($config);
    }

    /**
     * 싱글톤 인스턴스 설정
     */
    public static function setInstance(?Connection $connection): void
    {
        static::$instance = $connection;
    }

    /**
     * Connection 생성자
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->prefix = $config['prefix'] ?? '';
    }

    /**
     * PDO 연결 생성
     */
    public function connect(): PDO
    {
        if ($this->pdo !== null) {
            return $this->pdo;
        }

        $driver = $this->config['driver'] ?? 'mysql';

        $this->pdo = match ($driver) {
            'mysql' => $this->createMySqlConnection(),
            'sqlite' => $this->createSqliteConnection(),
            default => throw new PDOException("Unsupported driver: {$driver}")
        };

        return $this->pdo;
    }

    /**
     * MySQL 연결 생성
     */
    protected function createMySqlConnection(): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $this->config['host'] ?? '127.0.0.1',
            $this->config['port'] ?? '3306',
            $this->config['database'] ?? '',
            $this->config['charset'] ?? 'utf8mb4'
        );

        // PHP 8.2+ 에서 PDO 옵션의 타입 검사가 엄격해짐
        // DSN에 charset이 지정되어 있으므로 MYSQL_ATTR_INIT_COMMAND 불필요
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        return new PDO(
            $dsn,
            $this->config['username'] ?? 'root',
            $this->config['password'] ?? '',
            $options
        );
    }

    /**
     * SQLite 연결 생성
     */
    protected function createSqliteConnection(): PDO
    {
        $database = $this->config['database'] ?? ':memory:';

        return new PDO("sqlite:{$database}", null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    /**
     * PDO 인스턴스 반환
     */
    public function getPdo(): PDO
    {
        return $this->connect();
    }

    /**
     * 쿼리 실행 (SELECT)
     */
    public function select(string $query, array $bindings = []): array
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            $statement = $this->prepared($query);
            $this->bindValues($statement, $bindings);
            $statement->execute();

            return $statement->fetchAll();
        });
    }

    /**
     * 단일 행 조회
     */
    public function selectOne(string $query, array $bindings = []): ?array
    {
        $results = $this->select($query, $bindings);

        return count($results) > 0 ? $results[0] : null;
    }

    /**
     * INSERT 쿼리 실행
     */
    public function insert(string $query, array $bindings = []): bool
    {
        return $this->statement($query, $bindings);
    }

    /**
     * UPDATE 쿼리 실행
     */
    public function update(string $query, array $bindings = []): int
    {
        return $this->affectingStatement($query, $bindings);
    }

    /**
     * DELETE 쿼리 실행
     */
    public function delete(string $query, array $bindings = []): int
    {
        return $this->affectingStatement($query, $bindings);
    }

    /**
     * 일반 쿼리 실행
     */
    public function statement(string $query, array $bindings = []): bool
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            $statement = $this->prepared($query);
            $this->bindValues($statement, $bindings);

            return $statement->execute();
        });
    }

    /**
     * 영향받은 행 수를 반환하는 쿼리 실행
     */
    public function affectingStatement(string $query, array $bindings = []): int
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            $statement = $this->prepared($query);
            $this->bindValues($statement, $bindings);
            $statement->execute();

            return $statement->rowCount();
        });
    }

    /**
     * 마지막 삽입 ID 반환
     */
    public function lastInsertId(): string
    {
        return $this->getPdo()->lastInsertId();
    }

    /**
     * Prepared Statement 생성
     */
    protected function prepared(string $query): PDOStatement
    {
        $statement = $this->getPdo()->prepare($query);

        if ($statement === false) {
            throw new PDOException("Failed to prepare statement: {$query}");
        }

        return $statement;
    }

    /**
     * 바인딩 값 설정
     */
    protected function bindValues(PDOStatement $statement, array $bindings): void
    {
        foreach ($bindings as $key => $value) {
            $type = match (true) {
                is_int($value) => PDO::PARAM_INT,
                is_bool($value) => PDO::PARAM_BOOL,
                is_null($value) => PDO::PARAM_NULL,
                default => PDO::PARAM_STR
            };

            $statement->bindValue(
                is_string($key) ? $key : $key + 1,
                $value,
                $type
            );
        }
    }

    /**
     * 쿼리 실행 래퍼
     */
    protected function run(string $query, array $bindings, callable $callback): mixed
    {
        $start = microtime(true);

        try {
            $result = $callback($query, $bindings);
        } catch (PDOException $e) {
            throw new PDOException(
                $e->getMessage() . " (SQL: {$query})",
                (int) $e->getCode(),
                $e
            );
        }

        if ($this->loggingQueries) {
            $this->queryLog[] = [
                'query' => $query,
                'bindings' => $bindings,
                'time' => round((microtime(true) - $start) * 1000, 2),
            ];
        }

        return $result;
    }

    /**
     * 트랜잭션 시작
     */
    public function beginTransaction(): bool
    {
        return $this->getPdo()->beginTransaction();
    }

    /**
     * 트랜잭션 커밋
     */
    public function commit(): bool
    {
        return $this->getPdo()->commit();
    }

    /**
     * 트랜잭션 롤백
     */
    public function rollBack(): bool
    {
        return $this->getPdo()->rollBack();
    }

    /**
     * 트랜잭션 내에서 콜백 실행
     */
    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);
            $this->commit();

            return $result;
        } catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }

    /**
     * 테이블 접두사 반환
     */
    public function getTablePrefix(): string
    {
        return $this->prefix;
    }

    /**
     * 테이블명에 접두사 추가
     */
    public function prefixTable(string $table): string
    {
        return $this->prefix . $table;
    }

    /**
     * 쿼리 로깅 활성화
     */
    public function enableQueryLog(): void
    {
        $this->loggingQueries = true;
    }

    /**
     * 쿼리 로깅 비활성화
     */
    public function disableQueryLog(): void
    {
        $this->loggingQueries = false;
    }

    /**
     * 쿼리 로그 반환
     */
    public function getQueryLog(): array
    {
        return $this->queryLog;
    }

    /**
     * 연결 해제
     */
    public function disconnect(): void
    {
        $this->pdo = null;
    }

    /**
     * 연결 재설정
     */
    public function reconnect(): PDO
    {
        $this->disconnect();
        return $this->connect();
    }

    /**
     * 쿼리 빌더 인스턴스 생성
     */
    public function getQueryBuilder(): QueryBuilder
    {
        return new QueryBuilder($this);
    }

    /**
     * 테이블 쿼리 빌더 시작
     */
    public function table(string $table): QueryBuilder
    {
        return $this->getQueryBuilder()->table($table);
    }
}
