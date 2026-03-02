<?php

declare(strict_types=1);

namespace RzxLib\Core\Database;

/**
 * Query Builder Class
 *
 * SQL 쿼리를 유연하게 생성하기 위한 빌더 클래스
 *
 * @package RzxLib\Core\Database
 */
class QueryBuilder
{
    /**
     * 데이터베이스 연결
     */
    protected Connection $connection;

    /**
     * 테이블명
     */
    protected string $table = '';

    /**
     * SELECT 컬럼
     */
    protected array $columns = ['*'];

    /**
     * WHERE 조건
     */
    protected array $wheres = [];

    /**
     * ORDER BY
     */
    protected array $orders = [];

    /**
     * GROUP BY
     */
    protected array $groups = [];

    /**
     * HAVING
     */
    protected array $havings = [];

    /**
     * JOIN
     */
    protected array $joins = [];

    /**
     * LIMIT
     */
    protected ?int $limitValue = null;

    /**
     * OFFSET
     */
    protected ?int $offsetValue = null;

    /**
     * 바인딩 값
     */
    protected array $bindings = [
        'select' => [],
        'join' => [],
        'where' => [],
        'having' => [],
        'order' => [],
    ];

    /**
     * DISTINCT 여부
     */
    protected bool $distinct = false;

    /**
     * QueryBuilder 생성자
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * 테이블 설정
     */
    public function table(string $table): static
    {
        $this->table = $this->connection->prefixTable($table);
        return $this;
    }

    /**
     * 원본 테이블명 설정 (접두사 없이)
     */
    public function from(string $table): static
    {
        $this->table = $table;
        return $this;
    }

    /**
     * SELECT 컬럼 설정
     */
    public function select(array|string $columns = ['*']): static
    {
        $this->columns = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    /**
     * SELECT 컬럼 추가
     */
    public function addSelect(array|string $columns): static
    {
        $columns = is_array($columns) ? $columns : [$columns];

        if ($this->columns === ['*']) {
            $this->columns = [];
        }

        $this->columns = array_merge($this->columns, $columns);
        return $this;
    }

    /**
     * DISTINCT 설정
     */
    public function distinct(): static
    {
        $this->distinct = true;
        return $this;
    }

    /**
     * WHERE 조건 추가
     */
    public function where(string|array $column, mixed $operator = null, mixed $value = null, string $boolean = 'AND'): static
    {
        // 배열인 경우 여러 조건 추가
        if (is_array($column)) {
            foreach ($column as $key => $val) {
                $this->where($key, '=', $val, $boolean);
            }
            return $this;
        }

        // 두 인자만 제공된 경우 (column, value)
        if ($value === null && $operator !== null) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => $boolean,
        ];

        $this->addBinding($value, 'where');

        return $this;
    }

    /**
     * OR WHERE 조건 추가
     */
    public function orWhere(string|array $column, mixed $operator = null, mixed $value = null): static
    {
        return $this->where($column, $operator, $value, 'OR');
    }

    /**
     * WHERE IN 조건
     */
    public function whereIn(string $column, array $values, string $boolean = 'AND', bool $not = false): static
    {
        $type = $not ? 'not in' : 'in';

        $this->wheres[] = [
            'type' => $type,
            'column' => $column,
            'values' => $values,
            'boolean' => $boolean,
        ];

        foreach ($values as $value) {
            $this->addBinding($value, 'where');
        }

        return $this;
    }

    /**
     * WHERE NOT IN 조건
     */
    public function whereNotIn(string $column, array $values, string $boolean = 'AND'): static
    {
        return $this->whereIn($column, $values, $boolean, true);
    }

    /**
     * WHERE NULL 조건
     */
    public function whereNull(string $column, string $boolean = 'AND', bool $not = false): static
    {
        $type = $not ? 'not null' : 'null';

        $this->wheres[] = [
            'type' => $type,
            'column' => $column,
            'boolean' => $boolean,
        ];

        return $this;
    }

    /**
     * WHERE NOT NULL 조건
     */
    public function whereNotNull(string $column, string $boolean = 'AND'): static
    {
        return $this->whereNull($column, $boolean, true);
    }

    /**
     * WHERE BETWEEN 조건
     */
    public function whereBetween(string $column, array $values, string $boolean = 'AND', bool $not = false): static
    {
        $type = $not ? 'not between' : 'between';

        $this->wheres[] = [
            'type' => $type,
            'column' => $column,
            'values' => $values,
            'boolean' => $boolean,
        ];

        $this->addBinding($values[0], 'where');
        $this->addBinding($values[1], 'where');

        return $this;
    }

    /**
     * WHERE LIKE 조건
     */
    public function whereLike(string $column, string $value, string $boolean = 'AND'): static
    {
        return $this->where($column, 'LIKE', $value, $boolean);
    }

    /**
     * JOIN
     */
    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): static
    {
        $table = $this->connection->prefixTable($table);

        $this->joins[] = [
            'type' => $type,
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
        ];

        return $this;
    }

    /**
     * LEFT JOIN
     */
    public function leftJoin(string $table, string $first, string $operator, string $second): static
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    /**
     * RIGHT JOIN
     */
    public function rightJoin(string $table, string $first, string $operator, string $second): static
    {
        return $this->join($table, $first, $operator, $second, 'RIGHT');
    }

    /**
     * ORDER BY
     */
    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $this->orders[] = [
            'column' => $column,
            'direction' => strtoupper($direction),
        ];

        return $this;
    }

    /**
     * ORDER BY DESC
     */
    public function orderByDesc(string $column): static
    {
        return $this->orderBy($column, 'DESC');
    }

    /**
     * 최신 순 정렬
     */
    public function latest(string $column = 'created_at'): static
    {
        return $this->orderByDesc($column);
    }

    /**
     * 오래된 순 정렬
     */
    public function oldest(string $column = 'created_at'): static
    {
        return $this->orderBy($column, 'ASC');
    }

    /**
     * GROUP BY
     */
    public function groupBy(string ...$columns): static
    {
        $this->groups = array_merge($this->groups, $columns);
        return $this;
    }

    /**
     * HAVING
     */
    public function having(string $column, string $operator, mixed $value, string $boolean = 'AND'): static
    {
        $this->havings[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => $boolean,
        ];

        $this->addBinding($value, 'having');

        return $this;
    }

    /**
     * LIMIT 설정
     */
    public function limit(int $value): static
    {
        $this->limitValue = max(0, $value);
        return $this;
    }

    /**
     * OFFSET 설정
     */
    public function offset(int $value): static
    {
        $this->offsetValue = max(0, $value);
        return $this;
    }

    /**
     * take 별칭 (limit)
     */
    public function take(int $value): static
    {
        return $this->limit($value);
    }

    /**
     * skip 별칭 (offset)
     */
    public function skip(int $value): static
    {
        return $this->offset($value);
    }

    /**
     * 페이지네이션용 LIMIT/OFFSET 설정
     */
    public function forPage(int $page, int $perPage = 15): static
    {
        return $this->offset(($page - 1) * $perPage)->limit($perPage);
    }

    /**
     * 바인딩 추가
     */
    protected function addBinding(mixed $value, string $type = 'where'): void
    {
        $this->bindings[$type][] = $value;
    }

    /**
     * 모든 바인딩 반환
     */
    public function getBindings(): array
    {
        return array_merge(
            $this->bindings['select'],
            $this->bindings['join'],
            $this->bindings['where'],
            $this->bindings['having'],
            $this->bindings['order']
        );
    }

    /**
     * SELECT 쿼리 생성
     */
    public function toSql(): string
    {
        $sql = $this->distinct ? 'SELECT DISTINCT ' : 'SELECT ';
        $sql .= implode(', ', $this->columns);
        $sql .= ' FROM ' . $this->table;

        // JOIN
        foreach ($this->joins as $join) {
            $sql .= " {$join['type']} JOIN {$join['table']} ON {$join['first']} {$join['operator']} {$join['second']}";
        }

        // WHERE
        $sql .= $this->compileWheres();

        // GROUP BY
        if (!empty($this->groups)) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groups);
        }

        // HAVING
        if (!empty($this->havings)) {
            $sql .= $this->compileHavings();
        }

        // ORDER BY
        if (!empty($this->orders)) {
            $orderParts = array_map(fn($o) => "{$o['column']} {$o['direction']}", $this->orders);
            $sql .= ' ORDER BY ' . implode(', ', $orderParts);
        }

        // LIMIT
        if ($this->limitValue !== null) {
            $sql .= ' LIMIT ' . $this->limitValue;
        }

        // OFFSET
        if ($this->offsetValue !== null) {
            $sql .= ' OFFSET ' . $this->offsetValue;
        }

        return $sql;
    }

    /**
     * WHERE 절 컴파일
     */
    protected function compileWheres(): string
    {
        if (empty($this->wheres)) {
            return '';
        }

        $sql = ' WHERE ';
        $first = true;

        foreach ($this->wheres as $where) {
            $connector = $first ? '' : " {$where['boolean']} ";
            $first = false;

            $sql .= match ($where['type']) {
                'basic' => "{$connector}{$where['column']} {$where['operator']} ?",
                'in' => "{$connector}{$where['column']} IN (" . implode(', ', array_fill(0, count($where['values']), '?')) . ")",
                'not in' => "{$connector}{$where['column']} NOT IN (" . implode(', ', array_fill(0, count($where['values']), '?')) . ")",
                'null' => "{$connector}{$where['column']} IS NULL",
                'not null' => "{$connector}{$where['column']} IS NOT NULL",
                'between' => "{$connector}{$where['column']} BETWEEN ? AND ?",
                'not between' => "{$connector}{$where['column']} NOT BETWEEN ? AND ?",
                default => ''
            };
        }

        return $sql;
    }

    /**
     * HAVING 절 컴파일
     */
    protected function compileHavings(): string
    {
        if (empty($this->havings)) {
            return '';
        }

        $sql = ' HAVING ';
        $first = true;

        foreach ($this->havings as $having) {
            $connector = $first ? '' : " {$having['boolean']} ";
            $first = false;
            $sql .= "{$connector}{$having['column']} {$having['operator']} ?";
        }

        return $sql;
    }

    /**
     * 결과 조회
     */
    public function get(): array
    {
        return $this->connection->select($this->toSql(), $this->getBindings());
    }

    /**
     * 첫 번째 결과 조회
     */
    public function first(): ?array
    {
        return $this->limit(1)->get()[0] ?? null;
    }

    /**
     * 특정 컬럼 값만 조회
     */
    public function value(string $column): mixed
    {
        $result = $this->select($column)->first();
        return $result[$column] ?? null;
    }

    /**
     * 특정 컬럼 값들 배열로 조회
     */
    public function pluck(string $column, ?string $key = null): array
    {
        $results = $this->get();

        if ($key === null) {
            return array_column($results, $column);
        }

        return array_column($results, $column, $key);
    }

    /**
     * 결과 개수 조회
     */
    public function count(string $column = '*'): int
    {
        return (int) $this->select("COUNT({$column}) as aggregate")->value('aggregate');
    }

    /**
     * 최대값 조회
     */
    public function max(string $column): mixed
    {
        return $this->select("MAX({$column}) as aggregate")->value('aggregate');
    }

    /**
     * 최소값 조회
     */
    public function min(string $column): mixed
    {
        return $this->select("MIN({$column}) as aggregate")->value('aggregate');
    }

    /**
     * 평균값 조회
     */
    public function avg(string $column): mixed
    {
        return $this->select("AVG({$column}) as aggregate")->value('aggregate');
    }

    /**
     * 합계 조회
     */
    public function sum(string $column): mixed
    {
        return $this->select("SUM({$column}) as aggregate")->value('aggregate');
    }

    /**
     * 존재 여부 확인
     */
    public function exists(): bool
    {
        return $this->count() > 0;
    }

    /**
     * 데이터 삽입
     */
    public function insert(array $values): bool
    {
        if (empty($values)) {
            return true;
        }

        // 다중 삽입 처리
        if (!is_array(reset($values))) {
            $values = [$values];
        }

        $columns = array_keys($values[0]);
        $placeholders = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';

        $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") VALUES ";
        $sql .= implode(', ', array_fill(0, count($values), $placeholders));

        $bindings = [];
        foreach ($values as $record) {
            foreach ($columns as $column) {
                $bindings[] = $record[$column] ?? null;
            }
        }

        return $this->connection->insert($sql, $bindings);
    }

    /**
     * 데이터 삽입 후 ID 반환
     */
    public function insertGetId(array $values): int
    {
        $this->insert($values);
        return (int) $this->connection->lastInsertId();
    }

    /**
     * 데이터 업데이트
     */
    public function update(array $values): int
    {
        $columns = array_keys($values);
        $setParts = array_map(fn($col) => "{$col} = ?", $columns);

        $sql = "UPDATE {$this->table} SET " . implode(', ', $setParts);
        $sql .= $this->compileWheres();

        $bindings = array_merge(array_values($values), $this->bindings['where']);

        return $this->connection->update($sql, $bindings);
    }

    /**
     * 데이터 삭제
     */
    public function delete(): int
    {
        $sql = "DELETE FROM {$this->table}";
        $sql .= $this->compileWheres();

        return $this->connection->delete($sql, $this->bindings['where']);
    }

    /**
     * ID로 조회
     */
    public function find(int|string $id, string $column = 'id'): ?array
    {
        return $this->where($column, $id)->first();
    }

    /**
     * 빌더 상태 초기화
     */
    public function newQuery(): static
    {
        return new static($this->connection);
    }
}
