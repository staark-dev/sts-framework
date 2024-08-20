<?php
declare(strict_types=1);
namespace STS\core\Database\Migration;

use STS\core\Database\Connection;
use Closure;

final class Schema {
    protected Connection $connection;

    protected function __construct(Connection $connection) 
    {
        $this->connection = $connection;
    }

    public function create(string $table, Closure $callback): void 
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);
        $blueprint->build($this->connection);
    }

    public function addColumn(string $table, string $column, string $type): void 
    {
        $sql = "ALTER TABLE {$table} ADD COLUMN {$column} {$type}";
        $this->connection->execute($sql);
    }

    public function renameTable(string $oldName, string $newName): void 
    {
        $sql = "ALTER TABLE {$oldName} RENAME TO {$newName}";
        $this->connection->execute($sql);
    }
    
    public function drop(string $table): void 
    {
        $sql = "DROP TABLE IF EXISTS {$table}";
        $this->connection->execute($sql);
    }

    public function up(Closure $callback): void 
    {
        $callback($this);
    }
}