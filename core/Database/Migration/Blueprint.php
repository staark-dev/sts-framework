<?php
declare(strict_types=1);
namespace STS\core\Database\Migration;

use STS\core\Database\Connection;

final class Blueprint {
    protected string $table;
    protected array $columns = [];
    protected array $primaryKey = [];
    protected array $foreignKeys = [];

    public function __construct(string $table) {
        $this->table = $table;
    }

    public function increments(string $column): self {
        $this->columns[] = "{$column} INT AUTO_INCREMENT PRIMARY KEY";
        return $this;
    }

    public function string(string $column, int $length = 255): self {
        $this->columns[] = "{$column} VARCHAR({$length})";
        return $this;
    }

    public function integer(string $column): self {
        $this->columns[] = "{$column} INT";
        return $this;
    }

    public function foreign(string $column): self {
        $this->foreignKeys[] = $column;
        return $this;
    }

    public function references(string $column): self {
        $this->foreignKeys[] = "REFERENCES {$column}";
        return $this;
    }

    public function on(string $table): self {
        $this->foreignKeys[] = "ON {$table}";
        return $this;
    }

    public function build(Connection $connection): void {
        $sql = "CREATE TABLE {$this->table} (" . implode(', ', $this->columns);
        if ($this->primaryKey) {
            $sql .= ", PRIMARY KEY(" . implode(', ', $this->primaryKey) . ")";
        }
        if ($this->foreignKeys) {
            $sql .= ", " . implode(', ', $this->foreignKeys);
        }
        $sql .= ")";
        $connection->execute($sql);
    }
}