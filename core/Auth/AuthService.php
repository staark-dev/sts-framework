<?php

namespace STS\core\Auth;

use STS\core\Facades\Database;

class AuthService {
    public function __construct() {}

    public function loginUser() {
        $user = Database::table('users')
            ->where('remember_token', '=', session_id())
            ->limit(1)
            ->get();

        if($user) {
            $_SESSION['user_id'] = $user[0]['id'];
            $_SESSION['logged_in'] = true;
            $_SESSION['user'] = $user[0]['username'];
            $_SESSION['user_data'] = $user[0];
        }
    }

    public function attemptLogin(string $email, string $password): bool {
        $user = $this->connection->query("SELECT * FROM users WHERE email = ?", [$email]);

        if (!empty($user) && password_verify($password, $user[0]['password'])) {
            $_SESSION['user_id'] = $user[0]['id'];
            return true;
        }

        return false;
    }

    public function get(string $key): string {
        return $_SESSION['user_data'][$key] ?? '';
    }

    public function name() {
        return $_SESSION['user'] ?? null;
    }

    public function check(): bool {
        return isset($_SESSION['logged_in']);
    }

    public function logout(): void {
        unset($_SESSION);
        session_destroy();
    }
}
