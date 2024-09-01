<?php

namespace STS\core\Database;

class QueryBuilder {
    protected ?Connection $connection;
    protected string $table;
    protected array $fields = ['*'];
    protected array $conditions = [];
    protected array $params = [];
    protected array $joins = [];
    protected array $relations = [];
    protected ?array $orderBy = null;
    protected ?int $limit = null;

    public function __construct(Connection $connection, string $table) {
        if (!$connection) {
            throw new \InvalidArgumentException('A valid database connection is required.');
        }

        $this->connection = $connection;
        $this->table = $table;
    }

    public function select(...$fields): self {
        $this->fields = $fields;
        return $this;
    }

    public function where(string $field, string $operator, $value, string $boolean = 'AND'): self {
        // Construiește condiția SQL
        $condition = (count($this->conditions) > 0 ? " {$boolean} " : '') . "{$field} {$operator} ?";
    
        $this->conditions[] = $condition;
        $this->params[] = $value;
        
        return $this;
    }
    
    public function orWhere(string $field, string $operator, $value): self {
        // Apelează metoda where cu booleanul 'OR'
        return $this->where($field, $operator, $value, 'OR');
    }
    
    public function orderBy(string $field, string $direction = 'ASC'): self {
        $this->orderBy = [$field, $direction];
        return $this;
    }

    public function limit(int $limit): self {
        $this->limit = $limit;
        return $this;
    }

    public function whereIn(string $field, array $values): self {
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $this->conditions[] = "{$field} IN ({$placeholders})";
        $this->params = array_merge($this->params, $values);
        return $this;
    }

    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): self {
        $this->joins[] = "{$type} JOIN {$table} ON {$first} {$operator} {$second}";
        return $this;
    }

    public function with(string $relation): self {
        $this->relations[] = $relation;
        return $this;
    }

    protected function buildWhereClause(): string {
        $whereClause = !empty($this->conditions) ? 'WHERE ' . implode(' ', $this->conditions) : '';
        return $whereClause;
    }

    public function toSql(): string {
        $sql = sprintf(
            "SELECT %s FROM %s %s %s %s %s",
            implode(', ', $this->fields),
            $this->table,
            implode(' ', $this->joins),
            $this->buildWhereClause(),
            $this->buildOrderByClause(),
            $this->buildLimitClause()
        );
    
        return $sql;
    }

    public function get(): array {
        $sql = sprintf(
            "SELECT %s FROM %s %s %s %s %s",
            implode(', ', $this->fields),
            $this->table,
            implode(' ', $this->joins),
            $this->buildWhereClause(),
            $this->buildOrderByClause(),
            $this->buildLimitClause()
        );
        
        try {
            $results = $this->connection->query($sql, $this->params);
        } catch (\Exception $e) {
            // Loghează eroarea sau gestionează excepția
            return []; // Returnează un array gol în caz de eroare
        }
    
        if (empty($results)) {
            return $results; // Întoarce un array gol dacă nu există rezultate
        }
    
        foreach ($results as &$result) {
            foreach ($this->relations as $relation) {
                $relationMethod = 'load' . ucfirst($relation);
                if (method_exists($this, $relationMethod)) {
                    $result[$relation] = $this->$relationMethod($result);
                } else {
                    // Poți adăuga un mesaj de eroare sau un log dacă metoda relației nu este găsită
                }
            }
        }
    
        return $results;
    }

    public function whereGroup(callable $callback, string $boolean = 'AND'): self {
        // Deschide un grup de condiții
        $this->conditions[] = count($this->conditions) > 0 ? " {$boolean} (" : '(';
        
        // Apelează callback-ul, permițând adăugarea de condiții în grup
        $callback($this);
    
        // Închide grupul de condiții
        $this->conditions[] = ')';
        
        return $this;
    }
    
    protected function buildOrderByClause(): string {
        return $this->orderBy ? 'ORDER BY ' . implode(' ', $this->orderBy) : '';
    }
    
    protected function buildLimitClause(): string {
        return $this->limit ? 'LIMIT ' . $this->limit : '';
    }
    
    public function insert(array $data): bool {
        $fields = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = sprintf("INSERT INTO %s (%s) VALUES (%s)", $this->table, $fields, $placeholders);
        return $this->connection->execute($sql, array_values($data)) > 0;
    }

    public function update(array $data): bool {
        $set = implode(', ', array_map(fn($key) => "{$key} = ?", array_keys($data)));
        $sql = sprintf("UPDATE %s SET %s %s", $this->table, $set, $this->buildWhereClause());
        $params = array_merge(array_values($data), $this->params);
        return $this->connection->execute($sql, $params) > 0;
    }

    public function delete(): bool {
        $sql = sprintf("DELETE FROM %s %s", $this->table, $this->buildWhereClause());
        return $this->connection->execute($sql, $this->params) > 0;
    }

    public function paginate(int $perPage, int $currentPage): array {
        $offset = ($currentPage - 1) * $perPage;
        $sql = sprintf(
            "SELECT %s FROM %s %s LIMIT %d OFFSET %d",
            implode(', ', $this->fields),
            $this->table,
            $this->buildWhereClause(),
            $perPage,
            $offset
        );
        return $this->connection->query($sql, $this->params);
    }

    protected function loadHasOne(array $result, string $relatedTable, string $foreignKey, string $localKey): array {
        $foreignValue = $result[$localKey];
        return $this->connection->table($relatedTable)->where($foreignKey, '=', $foreignValue)->get()[0] ?? [];
    }

    protected function loadHasMany(array $result, string $relatedTable, string $foreignKey, string $localKey): array {
        $foreignValue = $result[$localKey];
        return $this->connection->table($relatedTable)->where($foreignKey, '=', $foreignValue)->get();
    }
}