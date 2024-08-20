<?php

namespace STS\core\Auth;

use STS\core\Database\Connection;

class AuthService {
    protected Connection $connection;

    public function __construct(Connection $connection) {
        $this->connection = $connection;
    }

    public function attemptLogin(string $email, string $password): bool {
        $user = $this->connection->query("SELECT * FROM users WHERE email = ?", [$email]);

        if (!empty($user) && password_verify($password, $user[0]['password'])) {
            $_SESSION['user_id'] = $user[0]['id'];
            return true;
        }

        return false;
    }

    public function check(): bool {
        return isset($_SESSION['user_id']);
    }

    public function logout(): void {
        unset($_SESSION['user_id']);
    }
}
