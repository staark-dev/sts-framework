<?php
declare(strict_types=1);
namespace STS\core\Database;

use PDO;
use PDOException;
use Exception;
use STS\core\Container;

class ORM {
    protected static ?PDO $db = null;
    protected string $table;
    protected string $primaryKey = 'id';

    public function __construct(string $table, ?PDO $db = null) {
        $this->table = $table;
        if ($db) {
            self::$db = $db;
        }
        if (self::$db === null) {
            self::connect();
        }
    }

    public function newQuery(): QueryBuilder {
        return new QueryBuilder(self::$db, $this->table);
    }

    public function find(int $id): ?array {
        return $this->newQuery()->where($this->primaryKey, (string)$id)->get()[0] ?? null;
    }

    public function where(string $field, string $value): QueryBuilder {
        return $this->newQuery()->where($field, $value);
    }

    public function all(): array {
        return $this->newQuery()->get();
    }

    public function create(array $data): bool {
        $this->beforeCreate($data);
        $fields = implode(',', array_keys($data));
        $placeholders = ':' . implode(',:', array_keys($data));
        $sql = "INSERT INTO {$this->table} ({$fields}) VALUES ({$placeholders})";
        $stmt = self::$db->prepare($sql);
        $result = $stmt->execute($data);
        $this->afterCreate($data);
        return $result;
    }

    public function update(int $id, array $data): bool {
        $fields = implode(' = ?, ', array_keys($data)) . ' = ?';
        $sql = "UPDATE {$this->table} SET {$fields} WHERE {$this->primaryKey} = ?";
        $stmt = self::$db->prepare($sql);
        return $stmt->execute(array_merge(array_values($data), [$id]));
    }

    /*public function delete(int $id): bool {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $stmt = self::$db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }*/

        /**
     * Metoda delete pentru a șterge înregistrări fie pe baza id-ului, fie pe baza unei condiții
     */
    public function delete($condition = null): bool {
        if (is_null($condition)) {
            throw new Exception("Condition or ID is required for delete operation.");
        }

        if (is_int($condition)) {
            // Dacă este furnizat un ID
            $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
            $stmt = self::$db->prepare($sql);
            return $stmt->execute(['id' => $condition]);
        }

        if (is_array($condition)) {
            // Dacă este furnizată o condiție
            $whereClause = '';
            $bindings = [];
            foreach ($condition as $field => $value) {
                $whereClause .= "{$field} = :{$field} AND ";
                $bindings[$field] = $value;
            }
            $whereClause = rtrim($whereClause, ' AND ');

            $sql = "DELETE FROM {$this->table} WHERE {$whereClause}";
            $stmt = self::$db->prepare($sql);
            return $stmt->execute($bindings);
        }

        throw new Exception("Invalid condition provided for delete operation.");
    }

    // Gestionarea conexiunii la baza de date
    public static function connect(array $config = []): void {
        $config = $config ?: Container::getInstance()->make('config')->get('database.connections.mysql');
        
        $dsn = sprintf('%s:host=%s;dbname=%s;charset=%s',
            $config['driver'],
            $config['host'],
            $config['database'],
            $config['charset']
        );

        try {
            self::$db = new PDO($dsn, $config['username'], $config['password']);
            self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new Exception("Connection failed: " . $e->getMessage());
        }
    }

    // Gestionarea relațiilor
    public function hasMany(string $related, string $foreignKey, string $localKey = 'id'): array {
        $relatedModel = new ORM($related);
        return $relatedModel->where($foreignKey, $this->$localKey)->get();
    }

    public function belongsTo(string $related, string $foreignKey, string $ownerKey = 'id'): ?array {
        $relatedModel = new ORM($related);
        return $relatedModel->where($ownerKey, $this->$foreignKey)->get()[0] ?? null;
    }

    // Metode pentru evenimente
    protected function beforeCreate(array &$data): void {
        // Logică înainte de crearea unei înregistrări
    }

    protected function afterCreate(array $data): void {
        // Logică după crearea unei înregistrări
    }

    // Gestionarea tranzacțiilor
    public static function beginTransaction(): void {
        self::$db->beginTransaction();
    }

    public static function commit(): void {
        self::$db->commit();
    }

    public static function rollBack(): void {
        self::$db->rollBack();
    }

    // Funcționalități de Autentificare și Gestionare a Utilizatorilor

    public function findByEmail(string $email): ?array {
        $user = $this->newQuery()->where('email', $email)->get();
        return $user[0] ?? null;
    }

    public function authenticate(string $email, string $password): ?array {
        $user = $this->findByEmail($email);

        if ($user && password_verify($password, $user['password'])) {
            // Setează variabilele de sesiune
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role_id'];
            $_SESSION['is_admin'] = $user['is_admin'] ?? false;
            return $user;
        }

        return null;
    }

    public function register(array $data): bool {
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        return $this->create($data);
    }

    public function isAuthenticated(): bool {
        return isset($_SESSION['user_id']);
    }

    public function isAdmin(): bool {
        return isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
    }

    public function getRole(int $userId): ?array {
        $sql = "SELECT roles.* FROM users
                JOIN roles ON users.role_id = roles.id
                WHERE users.id = :userId";
        $stmt = self::$db->prepare($sql);
        $stmt->execute(['userId' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getPermissions(int $roleId): array {
        $sql = "SELECT permissions.* FROM role_permission
                JOIN permissions ON role_permission.permission_id = permissions.id
                WHERE role_permission.role_id = :roleId";
        $stmt = self::$db->prepare($sql);
        $stmt->execute(['roleId' => $roleId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function hasPermission(string $permission): bool {
        $roleId = $_SESSION['user_role'] ?? null;
        if (!$roleId) return false;

        $permissions = $this->getPermissions($roleId);
        foreach ($permissions as $perm) {
            if ($perm['name'] === $permission) {
                return true;
            }
        }

        return false;
    }

    public function logout(): void {
        session_unset();
        session_destroy();
    }
}