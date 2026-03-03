<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;

/**
 * Database Class
 * 
 * PDO wrapper with fluent query builder interface.
 * Provides secure database operations with prepared statements.
 */
class Database
{
    /**
     * @var PDO|null Database connection instance
     */
    private static ?PDO $instance = null;

    /**
     * @var string Current table name
     */
    private string $table = '';

    /**
     * @var array WHERE conditions
     */
    private array $wheres = [];

    /**
     * @var array WHERE bindings
     */
    private array $whereBindings = [];

    /**
     * @var array Columns to select
     */
    private array $selects = ['*'];

    /**
     * @var array JOIN clauses
     */
    private array $joins = [];

    /**
     * @var array ORDER BY clauses
     */
    private array $orders = [];

    /**
     * @var int|null LIMIT value
     */
    private ?int $limit = null;

    /**
     * @var int|null OFFSET value
     */
    private ?int $offset = null;

    /**
     * @var array GROUP BY columns
     */
    private array $groups = [];

    /**
     * @var array HAVING conditions
     */
    private array $havings = [];

    /**
     * @var array HAVING bindings
     */
    private array $havingBindings = [];

    /**
     * @var string Last executed SQL query (for debugging)
     */
    private string $lastQuery = '';

    /**
     * Get database connection instance (Singleton)
     *
     * @return PDO
     * @throws PDOException
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::$instance = self::connect();
        }
        return self::$instance;
    }

    /**
     * Establish database connection
     *
     * @return PDO
     * @throws PDOException
     */
    private static function connect(): PDO
    {
        $driver = env('DB_DRIVER', 'mysql');
        $host = env('DB_HOST', 'localhost');
        $port = env('DB_PORT', '3306');
        $database = env('DB_DATABASE', 'simrs');
        $username = env('DB_USERNAME', 'root');
        $password = env('DB_PASSWORD', '');
        $charset = 'utf8mb4';

        // Build DSN based on driver
        $dsn = match ($driver) {
            'mysql' => "mysql:host={$host};port={$port};dbname={$database};charset={$charset}",
            'sqlite' => "sqlite:" . base_path($database . '.db'),
            'pgsql' => "pgsql:host={$host};port={$port};dbname={$database}",
            default => throw new PDOException("Unsupported database driver: {$driver}")
        };

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => false,
        ];

        try {
            $pdo = new PDO($dsn, $username, $password, $options);
            return $pdo;
        } catch (PDOException $e) {
            // Log error
            log_message('error', 'Database connection failed: ' . $e->getMessage(), 'database');
            throw new PDOException('Database connection failed. Please check your configuration.');
        }
    }

    /**
     * Get PDO instance
     *
     * @return PDO
     */
    public function pdo(): PDO
    {
        return self::getInstance();
    }

    /**
     * Set the table for query
     *
     * @param string $table Table name
     * @return self
     */
    public function table(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Set columns to select
     *
     * @param array|string $columns Columns to select
     * @return self
     */
    public function select(array|string $columns = ['*']): self
    {
        if (is_string($columns)) {
            $columns = [$columns];
        }
        $this->selects = $columns;
        return $this;
    }

    /**
     * Add WHERE condition
     *
     * @param string $column Column name
     * @param mixed $operator Operator or value
     * @param mixed $value Value (if operator provided)
     * @param string $boolean AND/OR
     * @return self
     */
    public function where(string $column, mixed $operator = null, mixed $value = null, string $boolean = 'AND'): self
    {
        // Handle where('column', 'value') format
        if ($value === null && $operator !== null) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = "{$boolean} `{$column}` {$operator} ?";
        $this->whereBindings[] = $value;

        return $this;
    }

    /**
     * Add OR WHERE condition
     *
     * @param string $column Column name
     * @param mixed $operator Operator or value
     * @param mixed $value Value (if operator provided)
     * @return self
     */
    public function orWhere(string $column, mixed $operator = null, mixed $value = null): self
    {
        return $this->where($column, $operator, $value, 'OR');
    }

    /**
     * Add WHERE IN condition
     *
     * @param string $column Column name
     * @param array $values Values array
     * @return self
     */
    public function whereIn(string $column, array $values): self
    {
        if (empty($values)) {
            return $this->where('1', '=', '0');
        }

        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $this->wheres[] = "AND `{$column}` IN ({$placeholders})";
        $this->whereBindings = array_merge($this->whereBindings, $values);

        return $this;
    }

    /**
     * Add WHERE NOT IN condition
     *
     * @param string $column Column name
     * @param array $values Values array
     * @return self
     */
    public function whereNotIn(string $column, array $values): self
    {
        if (empty($values)) {
            return $this;
        }

        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $this->wheres[] = "AND `{$column}` NOT IN ({$placeholders})";
        $this->whereBindings = array_merge($this->whereBindings, $values);

        return $this;
    }

    /**
     * Add WHERE LIKE condition
     *
     * @param string $column Column name
     * @param string $value Search value
     * @param string $position Position of wildcard (both, start, end)
     * @return self
     */
    public function whereLike(string $column, string $value, string $position = 'both'): self
    {
        $pattern = match ($position) {
            'start' => "%{$value}",
            'end' => "{$value}%",
            default => "%{$value}%"
        };

        $this->wheres[] = "AND `{$column}` LIKE ?";
        $this->whereBindings[] = $pattern;

        return $this;
    }

    /**
     * Add WHERE BETWEEN condition
     *
     * @param string $column Column name
     * @param mixed $min Minimum value
     * @param mixed $max Maximum value
     * @return self
     */
    public function whereBetween(string $column, mixed $min, mixed $max): self
    {
        $this->wheres[] = "AND `{$column}` BETWEEN ? AND ?";
        $this->whereBindings[] = $min;
        $this->whereBindings[] = $max;

        return $this;
    }

    /**
     * Add WHERE NULL condition
     *
     * @param string $column Column name
     * @return self
     */
    public function whereNull(string $column): self
    {
        $this->wheres[] = "AND `{$column}` IS NULL";
        return $this;
    }

    /**
     * Add WHERE NOT NULL condition
     *
     * @param string $column Column name
     * @return self
     */
    public function whereNotNull(string $column): self
    {
        $this->wheres[] = "AND `{$column}` IS NOT NULL";
        return $this;
    }

    /**
     * Add JOIN clause
     *
     * @param string $table Table to join
     * @param string $first First column for join condition
     * @param string $operator Join operator
     * @param string $second Second column for join condition
     * @param string $type Join type (INNER, LEFT, RIGHT, etc.)
     * @return self
     */
    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): self
    {
        $this->joins[] = "{$type} JOIN `{$table}` ON `{$first}` {$operator} `{$second}`";
        return $this;
    }

    /**
     * Add LEFT JOIN clause
     *
     * @param string $table Table to join
     * @param string $first First column for join condition
     * @param string $operator Join operator
     * @param string $second Second column for join condition
     * @return self
     */
    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    /**
     * Add RIGHT JOIN clause
     *
     * @param string $table Table to join
     * @param string $first First column for join condition
     * @param string $operator Join operator
     * @param string $second Second column for join condition
     * @return self
     */
    public function rightJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->join($table, $first, $operator, $second, 'RIGHT');
    }

    /**
     * Add ORDER BY clause
     *
     * @param string $column Column to order by
     * @param string $direction Order direction (ASC or DESC)
     * @return self
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $direction = strtoupper($direction);
        if (!in_array($direction, ['ASC', 'DESC'])) {
            $direction = 'ASC';
        }
        $this->orders[] = "`{$column}` {$direction}";
        return $this;
    }

    /**
     * Add ORDER BY RAW clause
     *
     * @param string $expression Raw SQL expression
     * @return self
     */
    public function orderByRaw(string $expression): self
    {
        $this->orders[] = $expression;
        return $this;
    }

    /**
     * Set LIMIT clause
     *
     * @param int $limit Limit value
     * @return self
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Set OFFSET clause
     *
     * @param int $offset Offset value
     * @return self
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Add GROUP BY clause
     *
     * @param string|array $columns Columns to group by
     * @return self
     */
    public function groupBy(string|array $columns): self
    {
        if (is_string($columns)) {
            $columns = [$columns];
        }
        $this->groups = array_merge($this->groups, $columns);
        return $this;
    }

    /**
     * Add HAVING condition
     *
     * @param string $column Column name
     * @param string $operator Operator
     * @param mixed $value Value
     * @param string $boolean AND/OR
     * @return self
     */
    public function having(string $column, string $operator, mixed $value, string $boolean = 'AND'): self
    {
        $this->havings[] = "{$boolean} `{$column}` {$operator} ?";
        $this->havingBindings[] = $value;
        return $this;
    }

    /**
     * Execute SELECT query
     *
     * @return array Query results
     */
    public function get(): array
    {
        $sql = $this->buildSelectSql();
        $this->lastQuery = $sql;

        try {
            $stmt = $this->pdo()->prepare($sql);
            $stmt->execute($this->whereBindings);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            log_message('error', 'Query failed: ' . $e->getMessage(), 'database');
            throw $e;
        } finally {
            $this->reset();
        }
    }

    /**
     * Get single row
     *
     * @return array|null Single row or null if not found
     */
    public function first(): ?array
    {
        $this->limit = 1;
        $result = $this->get();
        return $result[0] ?? null;
    }

    /**
     * Get single value from first row
     *
     * @param string $column Column name
     * @return mixed Column value or null
     */
    public function value(string $column): mixed
    {
        $this->selects = [$column];
        $result = $this->first();
        return $result[$column] ?? null;
    }

    /**
     * Get count of rows
     *
     * @param string $column Column to count (default: *)
     * @return int Count
     */
    public function count(string $column = '*'): int
    {
        $this->selects = ["COUNT({$column}) as count"];
        $result = $this->first();
        return (int) ($result['count'] ?? 0);
    }

    /**
     * Get sum of column
     *
     * @param string $column Column to sum
     * @return float Sum
     */
    public function sum(string $column): float
    {
        $this->selects = ["SUM({$column}) as sum"];
        $result = $this->first();
        return (float) ($result['sum'] ?? 0);
    }

    /**
     * Get average of column
     *
     * @param string $column Column to average
     * @return float Average
     */
    public function avg(string $column): float
    {
        $this->selects = ["AVG({$column}) as avg"];
        $result = $this->first();
        return (float) ($result['avg'] ?? 0);
    }

    /**
     * Get maximum value of column
     *
     * @param string $column Column name
     * @return mixed Maximum value
     */
    public function max(string $column): mixed
    {
        $this->selects = ["MAX({$column}) as max"];
        $result = $this->first();
        return $result['max'] ?? null;
    }

    /**
     * Get minimum value of column
     *
     * @param string $column Column name
     * @return mixed Minimum value
     */
    public function min(string $column): mixed
    {
        $this->selects = ["MIN({$column}) as min"];
        $result = $this->first();
        return $result['min'] ?? null;
    }

    /**
     * Insert data into table
     *
     * @param array $data Data to insert
     * @return int Last insert ID
     */
    public function insert(array $data): int
    {
        $columns = implode(', ', array_map(fn($k) => "`{$k}`", array_keys($data)));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO `{$this->table}` ({$columns}) VALUES ({$placeholders})";
        $this->lastQuery = $sql;

        try {
            $stmt = $this->pdo()->prepare($sql);
            $stmt->execute(array_values($data));
            return (int) $this->pdo()->lastInsertId();
        } catch (PDOException $e) {
            log_message('error', 'Insert failed: ' . $e->getMessage(), 'database');
            throw $e;
        }
    }

    /**
     * Insert multiple rows
     *
     * @param array $data Array of data arrays
     * @return int Number of rows inserted
     */
    public function insertBatch(array $data): int
    {
        if (empty($data)) {
            return 0;
        }

        $columns = array_keys($data[0]);
        $columnsStr = implode(', ', array_map(fn($k) => "`{$k}`", $columns));
        
        $rowPlaceholders = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
        $placeholders = implode(', ', array_fill(0, count($data), $rowPlaceholders));
        
        $sql = "INSERT INTO `{$this->table}` ({$columnsStr}) VALUES {$placeholders}";
        $this->lastQuery = $sql;

        try {
            $stmt = $this->pdo()->prepare($sql);
            $values = [];
            foreach ($data as $row) {
                $values = array_merge($values, array_values($row));
            }
            $stmt->execute($values);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            log_message('error', 'Batch insert failed: ' . $e->getMessage(), 'database');
            throw $e;
        }
    }

    /**
     * Update data in table
     *
     * @param array $data Data to update
     * @return int Number of affected rows
     */
    public function update(array $data): int
    {
        $setParts = [];
        $bindings = array_values($data);

        foreach (array_keys($data) as $column) {
            $setParts[] = "`{$column}` = ?";
        }

        $setClause = implode(', ', $setParts);
        $whereClause = !empty($this->wheres) ? 'WHERE ' . implode(' ', $this->wheres) : '';
        $bindings = array_merge($bindings, $this->whereBindings);

        $sql = "UPDATE `{$this->table}` SET {$setClause} {$whereClause}";
        $this->lastQuery = $sql;

        try {
            $stmt = $this->pdo()->prepare($sql);
            $stmt->execute($bindings);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            log_message('error', 'Update failed: ' . $e->getMessage(), 'database');
            throw $e;
        } finally {
            $this->reset();
        }
    }

    /**
     * Delete rows from table
     *
     * @param array $where Where conditions (optional)
     * @return int Number of deleted rows
     */
    public function delete(array $where = []): int
    {
        foreach ($where as $column => $value) {
            $this->where($column, $value);
        }

        $whereClause = !empty($this->wheres) ? 'WHERE ' . implode(' ', $this->wheres) : '';
        $sql = "DELETE FROM `{$this->table}` {$whereClause}";
        $this->lastQuery = $sql;

        try {
            $stmt = $this->pdo()->prepare($sql);
            $stmt->execute($this->whereBindings);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            log_message('error', 'Delete failed: ' . $e->getMessage(), 'database');
            throw $e;
        } finally {
            $this->reset();
        }
    }

    /**
     * Execute raw SQL query
     *
     * @param string $sql SQL query
     * @param array $bindings Query bindings
     * @return array Query results
     */
    public function query(string $sql, array $bindings = []): array
    {
        $this->lastQuery = $sql;

        try {
            $stmt = $this->pdo()->prepare($sql);
            $stmt->execute($bindings);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            log_message('error', 'Raw query failed: ' . $e->getMessage(), 'database');
            throw $e;
        }
    }

    /**
     * Execute raw SQL query without returning results
     *
     * @param string $sql SQL query
     * @param array $bindings Query bindings
     * @return int Number of affected rows
     */
    public function execute(string $sql, array $bindings = []): int
    {
        $this->lastQuery = $sql;

        try {
            $stmt = $this->pdo()->prepare($sql);
            $stmt->execute($bindings);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            log_message('error', 'Execute failed: ' . $e->getMessage(), 'database');
            throw $e;
        }
    }

    /**
     * Begin transaction
     *
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return $this->pdo()->beginTransaction();
    }

    /**
     * Commit transaction
     *
     * @return bool
     */
    public function commit(): bool
    {
        return $this->pdo()->commit();
    }

    /**
     * Rollback transaction
     *
     * @return bool
     */
    public function rollback(): bool
    {
        return $this->pdo()->rollBack();
    }

    /**
     * Check if transaction is active
     *
     * @return bool
     */
    public function inTransaction(): bool
    {
        return $this->pdo()->inTransaction();
    }

    /**
     * Get last executed query
     *
     * @return string
     */
    public function getLastQuery(): string
    {
        return $this->lastQuery;
    }

    /**
     * Get last insert ID
     *
     * @return int
     */
    public function lastInsertId(): int
    {
        return (int) $this->pdo()->lastInsertId();
    }

    /**
     * Build SELECT SQL query
     *
     * @return string
     */
    private function buildSelectSql(): string
    {
        $selectClause = 'SELECT ' . implode(', ', $this->selects);
        $fromClause = "FROM `{$this->table}`";
        $joinClause = !empty($this->joins) ? implode(' ', $this->joins) : '';
        $whereClause = !empty($this->wheres) ? 'WHERE ' . implode(' ', $this->wheres) : '';
        $groupByClause = !empty($this->groups) ? 'GROUP BY ' . implode(', ', $this->groups) : '';
        $havingClause = !empty($this->havings) ? 'HAVING ' . implode(' ', $this->havings) : '';
        $orderByClause = !empty($this->orders) ? 'ORDER BY ' . implode(', ', $this->orders) : '';
        $limitClause = $this->limit !== null ? "LIMIT {$this->limit}" : '';
        $offsetClause = $this->offset !== null ? "OFFSET {$this->offset}" : '';

        return trim("{$selectClause} {$fromClause} {$joinClause} {$whereClause} {$groupByClause} {$havingClause} {$orderByClause} {$limitClause} {$offsetClause}");
    }

    /**
     * Reset query builder state
     *
     * @return void
     */
    private function reset(): void
    {
        $this->table = '';
        $this->wheres = [];
        $this->whereBindings = [];
        $this->selects = ['*'];
        $this->joins = [];
        $this->orders = [];
        $this->limit = null;
        $this->offset = null;
        $this->groups = [];
        $this->havings = [];
        $this->havingBindings = [];
    }
}
