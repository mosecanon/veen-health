<?php
namespace Veen\Core\Database;

use Closure;

/**
 * Fluent, injection-safe query builder on PDO prepared statements.
 * Identifiers are validated; values are always bound.
 */
class QueryBuilder
{
    private array $columns = ['*'];
    private array $wheres = [];   // [boolean, sql]
    private array $bindings = [];
    private array $joins = [];
    private array $orders = [];
    private array $groups = [];
    private ?int $limit = null;
    private ?int $offset = null;
    private bool $lock = false;

    private const OPERATORS = ['=', '!=', '<>', '<', '>', '<=', '>=', 'LIKE', 'NOT LIKE'];

    public function __construct(private string $table, private ?string $model = null)
    {
        self::ident($table);
    }

    public static function ident(string $name): string
    {
        if ($name === '*') {
            return '*';
        }
        if (preg_match('/^([A-Za-z_][A-Za-z0-9_]*)\.\*$/', $name, $m)) {
            return "`{$m[1]}`.*";
        }
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*(\.[A-Za-z_][A-Za-z0-9_]*)?$/', $name)) {
            throw new \InvalidArgumentException("Invalid identifier [$name]");
        }
        return implode('.', array_map(fn ($p) => "`$p`", explode('.', $name)));
    }

    public function select(string ...$columns): self
    {
        $this->columns = array_map([self::class, 'ident'], $columns);
        return $this;
    }

    /** Developer-supplied raw select (never user input). */
    public function selectRaw(string $sql): self
    {
        $this->columns = [$sql];
        return $this;
    }

    public function addSelectRaw(string $sql): self
    {
        if ($this->columns === ['*']) {
            $this->columns = ["`{$this->table}`.*"];
        }
        $this->columns[] = $sql;
        return $this;
    }

    public function join(string $table, string $first, string $op, string $second, string $type = 'INNER'): self
    {
        if (!in_array($op, self::OPERATORS, true)) {
            throw new \InvalidArgumentException("Invalid operator [$op]");
        }
        $type = strtoupper($type) === 'LEFT' ? 'LEFT' : 'INNER';
        $this->joins[] = "$type JOIN " . self::ident($table) . ' ON ' . self::ident($first) . " $op " . self::ident($second);
        return $this;
    }

    public function leftJoin(string $table, string $first, string $op, string $second): self
    {
        return $this->join($table, $first, $op, $second, 'LEFT');
    }

    public function where(string|Closure $column, mixed $operator = null, mixed $value = null, string $boolean = 'AND'): self
    {
        if ($column instanceof Closure) {
            $nested = new self($this->table);
            $column($nested);
            if ($nested->wheres) {
                $this->wheres[] = [$boolean, '(' . $nested->compileWheres() . ')'];
                array_push($this->bindings, ...$nested->bindings);
            }
            return $this;
        }
        if (func_num_args() === 2 || ($value === null && !in_array(strtoupper((string) $operator), self::OPERATORS, true))) {
            $value = $operator;
            $operator = '=';
        }
        $operator = strtoupper((string) $operator);
        if (!in_array($operator, self::OPERATORS, true)) {
            throw new \InvalidArgumentException("Invalid operator [$operator]");
        }
        if ($value === null) {
            return $operator === '=' ? $this->whereNull($column, $boolean) : $this->whereNotNull($column, $boolean);
        }
        $this->wheres[] = [$boolean, self::ident($column) . " $operator ?"];
        $this->bindings[] = $value;
        return $this;
    }

    public function orWhere(string|Closure $column, mixed $operator = null, mixed $value = null): self
    {
        if (func_num_args() === 2) {
            return $this->where($column, '=', $operator, 'OR');
        }
        return $this->where($column, $operator, $value, 'OR');
    }

    public function whereIn(string $column, array $values, string $boolean = 'AND'): self
    {
        if (!$values) {
            $this->wheres[] = [$boolean, '1 = 0'];
            return $this;
        }
        $this->wheres[] = [$boolean, self::ident($column) . ' IN (' . implode(',', array_fill(0, count($values), '?')) . ')'];
        array_push($this->bindings, ...array_values($values));
        return $this;
    }

    public function whereNull(string $column, string $boolean = 'AND'): self
    {
        $this->wheres[] = [$boolean, self::ident($column) . ' IS NULL'];
        return $this;
    }

    public function whereNotNull(string $column, string $boolean = 'AND'): self
    {
        $this->wheres[] = [$boolean, self::ident($column) . ' IS NOT NULL'];
        return $this;
    }

    /** Developer-supplied raw condition; values must be passed as bindings. */
    public function whereRaw(string $sql, array $bindings = [], string $boolean = 'AND'): self
    {
        $this->wheres[] = [$boolean, $sql];
        array_push($this->bindings, ...$bindings);
        return $this;
    }

    /** OR-LIKE search across several columns, grouped. */
    public function search(array $columns, ?string $term): self
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $this;
        }
        $like = '%' . addcslashes($term, '%_\\') . '%';
        return $this->where(function (self $q) use ($columns, $like) {
            foreach ($columns as $col) {
                $q->orWhere($col, 'LIKE', $like);
            }
        });
    }

    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $dir = strtolower($direction) === 'desc' ? 'DESC' : 'ASC';
        $this->orders[] = self::ident($column) . " $dir";
        return $this;
    }

    public function orderByRaw(string $sql): self
    {
        $this->orders[] = $sql;
        return $this;
    }

    public function latest(string $column = 'id'): self
    {
        return $this->orderBy($column, 'desc');
    }

    public function groupBy(string ...$columns): self
    {
        foreach ($columns as $c) {
            $this->groups[] = self::ident($c);
        }
        return $this;
    }

    public function limit(int $n): self { $this->limit = max(0, $n); return $this; }
    public function offset(int $n): self { $this->offset = max(0, $n); return $this; }
    public function lockForUpdate(): self { $this->lock = true; return $this; }

    private function compileWheres(): string
    {
        $sql = '';
        foreach ($this->wheres as $i => [$boolean, $clause]) {
            $sql .= ($i === 0 ? '' : " $boolean ") . $clause;
        }
        return $sql;
    }

    public function toSql(): string
    {
        $sql = 'SELECT ' . implode(', ', $this->columns) . ' FROM ' . self::ident($this->table);
        if ($this->joins) $sql .= ' ' . implode(' ', $this->joins);
        if ($this->wheres) $sql .= ' WHERE ' . $this->compileWheres();
        if ($this->groups) $sql .= ' GROUP BY ' . implode(', ', $this->groups);
        if ($this->orders) $sql .= ' ORDER BY ' . implode(', ', $this->orders);
        if ($this->limit !== null) $sql .= ' LIMIT ' . $this->limit;
        if ($this->offset !== null) $sql .= ' OFFSET ' . $this->offset;
        if ($this->lock) $sql .= ' FOR UPDATE';
        return $sql;
    }

    public function getBindings(): array
    {
        return $this->bindings;
    }

    private function hydrate(array $rows): array
    {
        if (!$this->model) {
            return $rows;
        }
        $class = $this->model;
        return array_map(fn ($r) => $class::hydrate($r), $rows);
    }

    public function get(): array
    {
        return $this->hydrate(DB::select($this->toSql(), $this->bindings));
    }

    public function first(): mixed
    {
        $clone = clone $this;
        $rows = $clone->limit(1)->get();
        return $rows[0] ?? null;
    }

    public function value(string $column): mixed
    {
        $clone = clone $this;
        $clone->columns = [self::ident($column)];
        $clone->model = null;
        $row = $clone->limit(1)->get();
        return $row ? array_values($row[0])[0] : null;
    }

    public function pluck(string $column): array
    {
        $clone = clone $this;
        $clone->columns = [self::ident($column)];
        $clone->model = null;
        return array_map(fn ($r) => array_values($r)[0], $clone->get());
    }

    private function aggregate(string $fn, string $column): mixed
    {
        $clone = clone $this;
        $clone->columns = ["$fn(" . self::ident($column) . ') AS agg'];
        $clone->orders = [];
        $clone->limit = $clone->offset = null;
        $clone->model = null;
        $row = DB::selectOne($clone->toSql(), $clone->bindings);
        return $row['agg'] ?? 0;
    }

    public function count(string $column = '*'): int { return (int) $this->aggregate('COUNT', $column); }
    public function sum(string $column): int|float { return (float) $this->aggregate('SUM', $column) + 0; }
    public function exists(): bool { return $this->count() > 0; }

    public function paginate(int $perPage = 15, ?int $page = null): Paginator
    {
        $page = max(1, $page ?? (int) ($_GET['page'] ?? 1));
        $total = $this->count();
        $clone = clone $this;
        $items = $clone->limit($perPage)->offset(($page - 1) * $perPage)->get();
        return new Paginator($items, $total, $perPage, $page);
    }

    public function insert(array $data): int
    {
        $cols = array_map([self::class, 'ident'], array_keys($data));
        $sql = 'INSERT INTO ' . self::ident($this->table) . ' (' . implode(', ', $cols) . ') VALUES (' . implode(', ', array_fill(0, count($data), '?')) . ')';
        DB::statement($sql, array_values($data));
        return (int) DB::connection()->lastInsertId();
    }

    public function update(array $data): int
    {
        if (!$data) return 0;
        $sets = implode(', ', array_map(fn ($c) => self::ident($c) . ' = ?', array_keys($data)));
        $sql = 'UPDATE ' . self::ident($this->table) . " SET $sets";
        if ($this->wheres) $sql .= ' WHERE ' . $this->compileWheres();
        else throw new \RuntimeException('Refusing to UPDATE without WHERE.');
        return DB::statement($sql, array_merge(array_values($data), $this->bindings));
    }

    /** Atomic increment/decrement: increment('qty', -3) */
    public function increment(string $column, int|float $by = 1, array $extra = []): int
    {
        $col = self::ident($column);
        $sets = ["$col = $col + ?"];
        $values = [$by];
        foreach ($extra as $c => $v) { $sets[] = self::ident($c) . ' = ?'; $values[] = $v; }
        if (!$this->wheres) throw new \RuntimeException('Refusing to UPDATE without WHERE.');
        $sql = 'UPDATE ' . self::ident($this->table) . ' SET ' . implode(', ', $sets) . ' WHERE ' . $this->compileWheres();
        return DB::statement($sql, array_merge($values, $this->bindings));
    }

    public function delete(): int
    {
        if (!$this->wheres) throw new \RuntimeException('Refusing to DELETE without WHERE.');
        $sql = 'DELETE FROM ' . self::ident($this->table) . ' WHERE ' . $this->compileWheres();
        return DB::statement($sql, $this->bindings);
    }
}
