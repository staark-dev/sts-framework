<?php

namespace STS\core\Database;

use PDO;
use PDOException;
use Exception;
use Closure;
use STS\core\Database\Migration\Blueprint;
use STS\core\Database\Migration\Schema;

final class Connection {
    protected ?PDO $pdo = null;

    protected array $config;

    protected static ?self $instance = null;

    protected array $hooks = [
        'beforeQuery' => [],
        'afterQuery' => [],
        'beforeTransaction' => [],
        'afterTransaction' => [],
    ];

    protected array $cache = [];

    protected function __construct(array $config) {
        $this->config = $config;
        $this->connect();
    }

    protected function connect(): void {
        $dsn = sprintf('%s:host=%s;dbname=%s;charset=%s',
            $this->config['driver'],
            $this->config['host'],
            $this->config['database'],
            $this->config['charset']
        );

        try {
            $this->pdo = new PDO($dsn, $this->config['username'], $this->config['password']);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new Exception('Database connection failed: ' . $e->getMessage());
        }
    }

    public static function getInstance(array $config): self {
        if (self::$instance === null || self::$instance->config !== $config) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }    

    public function addHook(string $name, Closure $callback): void {
        $this->hooks[$name][] = $callback;
    }

    public function triggerHook(string $name, ...$params): void {
        if (isset($this->hooks[$name])) {
            foreach ($this->hooks[$name] as $hook) {
                $hook(...$params);
            }
        }
    }

    public function query(string $sql, array $params = [], int $cacheTime = 0): array {
        $this->triggerHook('beforeQuery', $sql, $params);

        if ($cacheTime > 0) {
            $cacheKey = md5($sql . serialize($params));
            if (isset($this->cache[$cacheKey])) {
                $this->triggerHook('afterQuery', $sql, $params, $this->cache[$cacheKey]);
                return $this->cache[$cacheKey];
            }
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($cacheTime > 0) {
            $this->cache[$cacheKey] = $result;
        }

        $this->triggerHook('afterQuery', $sql, $params, $result);

        return $result;
    }

    public function execute(string $sql, array $params = []): int {
        $this->triggerHook('beforeExecute', $sql, $params);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rowCount = $stmt->rowCount();

        $this->triggerHook('afterExecute', $sql, $params, $rowCount);

        return $rowCount;
    }

    public function lastInsertId(string $name = null): string {
        return $this->pdo->lastInsertId($name);
    }

    public function beginTransaction(): void {
        $this->pdo->beginTransaction();
    }

    public function commit(): void {
        $this->pdo->commit();
    }

    public function rollBack(): void {
        $this->pdo->rollBack();
    }

    public function createTrigger(string $name, string $table, string $time, string $event, string $body): void {
        $sql = "CREATE TRIGGER :name {$time} {$event} ON {$table} FOR EACH ROW :body";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':body', $body);
        $stmt->execute();
    }
    
    public function performTransaction(Closure $callback): void {
        try {
            $this->beginTransaction();
            $callback($this);
            $this->commit();
        } catch (Exception $e) {
            $this->rollBack();
            throw $e; // Sau loghează eroarea pentru analiză ulterioară
        }
    }
    

    public function table(string $table): QueryBuilder {
        return new QueryBuilder($this, $table);
    }

    public function paginate(string $table, int $perPage, int $currentPage): array {
        $offset = ($currentPage - 1) * $perPage;
        $sql = "SELECT * FROM {$table} LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function migrate(Schema $schema): void {
        $schema->up($this);
    }

    public function blueprint(string $table, Closure $callback): Blueprint {
        $blueprint = new Blueprint($table);
        $callback($blueprint);
        return $blueprint;
    }

    public function existsInDb(string $table, string $column, $value): bool {
        // Construiește interogarea SQL pentru a verifica existența valorii
        $sql = "SELECT COUNT(*) as count FROM {$table} WHERE {$column} = :value";
        
        // Pregătește interogarea
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':value', $value);

        // Execută interogarea
        $stmt->execute();

        // Obține rezultatul
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Returnează true dacă există cel puțin o înregistrare
        return $result['count'] > 0;
    }

    public function getPdo(): PDO {
        return $this->pdo;
    }
}