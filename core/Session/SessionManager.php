<?php
declare(strict_types=1);
namespace STS\core\Session;

class SessionManager
{
    public function __construct() {}
    
    public function set(string $key, string $value): void {
        $_COOKIE[$key] = $value;
    }

    public function setGroup(string $key, array $value): void {
        $_COOKIE[$key] = $value;
    }

    public function get(string $key, $default = null) {
        return $_COOKIE[$key] ?? $default;
    }

    public function remove(string $key): void {
        unset($_COOKIE[$key]);
    }

    public function flash(string $key, $value): void {
        $_COOKIE['flash'][$key] = $value;
    }

    public function getFlash(string $key) {
        $value = $_COOKIE['flash'][$key] ?? null;
        unset($_COOKIE['flash'][$key]);
        return $value;
    }
}