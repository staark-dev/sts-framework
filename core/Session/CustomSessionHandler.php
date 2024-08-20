<?php
namespace STS\core\Session;

use SessionHandlerInterface;
use STS\core\Database\Connection;

class CustomSessionHandler implements SessionHandlerInterface
{
    protected Connection $connection;
    protected string $table;
    protected int $lifetime;

    public function __construct(Connection $connection, string $table = 'sessions')
    {
        $this->connection = $connection;
        $this->table = $table;
        $this->lifetime = (int) ini_get('session.gc_maxlifetime');
    }

    public function open(string $savePath, string $sessionName): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $sessionId): string
    {
        $sql = "SELECT data FROM {$this->table} WHERE id = :id AND last_activity > :time";
        $stmt = $this->connection->getPdo()->prepare($sql);
        $stmt->execute([
            ':id' => $sessionId,
            ':time' => time() - $this->lifetime,
        ]);

        $result = $stmt->fetchColumn();

        return $result !== false ? $result : '';
    }

    public function write(string $sessionId, string $data): bool
    {
        $sql = "REPLACE INTO {$this->table} (id, data, last_activity) VALUES (:id, :data, :time)";
        $stmt = $this->connection->getPdo()->prepare($sql);

        return $stmt->execute([
            ':id' => $sessionId,
            ':data' => $data,
            ':time' => time(),
        ]);
    }

    public function destroy(string $sessionId): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->connection->getPdo()->prepare($sql);

        return $stmt->execute([
            ':id' => $sessionId,
        ]);
    }

    public function gc(int $maxLifetime): int|false
    {
        $sql = "DELETE FROM {$this->table} WHERE last_activity < :time";
        $stmt = $this->connection->getPdo()->prepare($sql);
        $stmt->execute([
            ':time' => time() - $maxLifetime,
        ]);

        return $stmt->rowCount();
    }
}