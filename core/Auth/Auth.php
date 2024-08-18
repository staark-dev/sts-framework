<?php
declare(strict_types=1);

namespace STS\core\Auth;

class Auth
{
    public static function login(int $userId)
    {
        $_SESSION['user_id'] = $userId;
        // Poți salva și alte informații utile în sesiune.
    }

    public static function logout()
    {
        session_destroy();
    }

    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function user()
    {
        if (self::check()) {
            $userOrm = app()->make(Orm::class, ['table' => 'users']);
            return $userOrm->find($_SESSION['user_id']);
        }

        return null;
    }

    public static function role()
    {
        if (self::check()) {
            $user = self::user();
            $userOrm = app()->make(Orm::class, ['table' => 'users']);
            return $userOrm->belongsToMany('roles', 'user_role', 'user_id', 'role_id');
        }

        return [];
    }

    public static function hasPermission(string $permission)
    {
        $roles = self::role();
        foreach ($roles as $role) {
            $roleOrm = app()->make(Orm::class, ['table' => 'roles']);
            $permissions = $roleOrm->belongsToMany('permissions', 'role_permission', 'role_id', 'permission_id');
            foreach ($permissions as $perm) {
                if ($perm['name'] === $permission) {
                    return true;
                }
            }
        }
        return false;
    }
}