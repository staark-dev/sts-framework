<?php
declare(strict_types=1);
namespace STS\core\Database;

use PDO;

class QueryBuilder {
    protected PDO $db;
    protected string $table;
    protected array $select = ['*'];
    protected array $where = [];
    protected ?array $orderBy = null;
    protected ?int $limit = null;

    public function __construct(PDO $db, string $table) {
        $this->db = $db;
        $this->table = $table;
    }

    public function select(array $columns = ['*']): self {
        $this->select = $columns;
        return $this;
    }

    public function where(string $field, string $value): self {
        $this->where[] = [$field, $value];
        return $this;
    }

    public function orderBy(string $field, string $direction = 'ASC'): self {
        $this->orderBy = [$field, $direction];
        return $this;
    }

    public function limit(int $limit): self {
        $this->limit = $limit;
        return $this;
    }

    public function get(): array {
        $sql = $this->buildSelectQuery();
        $stmt = $this->db->prepare($sql);

        foreach ($this->where as $index => [$field, $value]) {
            $stmt->bindValue(":where_{$index}", $value);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function buildSelectQuery(): string {
        $select = implode(',', $this->select);
        $whereClauses = $this->buildWhereClauses();
        $orderBy = $this->orderBy ? 'ORDER BY ' . implode(' ', $this->orderBy) : '';
        $limit = $this->limit ? 'LIMIT ' . $this->limit : '';

        return "SELECT {$select} FROM {$this->table} {$whereClauses} {$orderBy} {$limit}";
    }

    protected function buildWhereClauses(): string {
        if (empty($this->where)) return '';
        
        $clauses = [];
        foreach ($this->where as $index => [$field, $value]) {
            $clauses[] = "{$field} = :where_{$index}";
        }
        return 'WHERE ' . implode(' AND ', $clauses);
    }
}