<?php
namespace STS\core\Auth;

use STS\core\Facades\Database;

class AuthService {
    public array $userData = [];
    protected $permissions = [];

    public function __construct() {
        $this->userData = $this->getUserData() ?? [];
    }

    public function user(): self {
        return $this;
    }

    public function name(): string {
        return $this->userData['username'] ?? '';
    }

    public function email(): string {
        return $this->userData['email'] ?? '';
    }

    public function role(): string {
        return $this->userData['role'] ?? '';
    }

    public function status(): string {
        if(isset($this->userData['status'])) {
            switch($this->userData['status']) {
                case 'active':
                    return '<span style="font-size: 12; color: green;">Active</span>';
                case 'inactive':
                    return '<span style="font-size: 12; color: red;">Inactive</span>';
                case 'banned':
                    return 'Banned';
                default:
                    return 'Unknown';
            }
        }

        return '';
    }

    public function remember_token(): string {
        return isset($this->userData['remember_token']) ? $this->userData['remember_token'] : '';
    }

    public function attemptLogin(): self
    {
        if(!isset($_POST['username'], $_POST['password'])) return false;
        
        $user = Database::table('users')
        ->where('username', '=', $_POST['username'])
        ->where('password', '=', password_hash($_POST['password'], PASSWORD_DEFAULT))
        ->limit(1)
        ->get();

        if($user) {
            $_SESSION['user_id'] = $user[0]['id'];
            $this->userData = $user[0];
            return true;
        }

        return $this;
    }

    public function getUserData(): array|null {
        // Check if cookie exists and is not empty
        if(!isset($_COOKIE['PHPSESSID']) && empty($_COOKIE['PHPSESSID'])) return null;

        // Check if session exists and loading user data from database
        $userFoundData = Database::table('users')
            ->where('remember_token', '=', $_COOKIE['PHPSESSID'] ?? session_id())
            ->limit(1)
            ->get();

        if(!empty($userFoundData)) {
            // If user data is found in database or in session, update user data and session data
            $this->userData = $userFoundData[0];
        }

        return $this->userData ?? [];
    }

    public function rememberMe() {
        $_SESSION['remember_token'] = session_id();

        return Database::table('users')
            ->where('id', '=', $_SESSION['user_id'])
            ->update(['remember_token' => session_id()]);
    }


    public function forgetMe() {
        $_SESSION['remember_token'] = null;

        return Database::table('users')
            ->where('id', '=', $_SESSION['user_id'])
            ->update(['remember_token' => null]);
    }

    public function getUserRole(): string {
        return $this->userData['role'] ?? '';
    }

    public function hasRole(string $role): bool {
        return $this->getUserRole() === $role;
    }

    public function isUser(): bool {
        return $this->getUserRole() === 'user';
    }

    public function isModerator(): bool {
        return $this->hasRole('moderator');
    }

    public function isSuperAdmin(): bool {
        return $this->hasRole('superadmin');
    }

    public function isAdminOrSuperAdmin(): bool {
        return $this->isSuperAdmin() || $this->isAdmin();
    }

    public function hasPermission(string $permission): bool {
        return in_array($permission, $this->userData['permissions']);
    }

    public function isAdmin(): bool {
        return $this->hasRole('admin');
    }

    public function hasPermissionOrIsAdmin(string $permission): bool {
        return $this->hasPermission($permission) || $this->isAdmin();
    }

    public function isLoggedInWithRememberMe(): bool {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] && isset($_SESSION['user_id']) && $_SESSION['user_id'];
    }

    public function isLoggedIn(): bool {
        return isset($this->userData['logged_in']) ? true : false;
    }

    public function logout() {
        Database::table('users')
            ->where('id', '=', $_SESSION['user_id'])
            ->orWhere('id', '=', $this->userData['id'])
            ->update(['remember_token' => null]);


        // Logout the user and clear the session data
        session_unset();

        // Clear cookies
        unset($_SESSION['user_id'], 
            $_SESSION['logged_in'], 
            $_SESSION['user'], 
            $_SESSION['user_data']
        );

        // Delete the session cookie of the client
        setcookie('PHPSESSID', '', time()-3600, '/');
        
        // Destroy the user data of the client
        $this->userData = [];

        // Destroy the session cookie of the server
        session_destroy();
    }

    public function get(string $key): string {
        return isset($this->userData[$key]) ? $this->userData[$key] : null;
    }

    public function check(): bool {
        return isset($_SESSION['logged_in']);
    }
    
    public function getUserPermitions(int $id): array|null {
        $permissions = Database::table('permissions p')
            ->select('p.name AS permission_name')
            ->join('user_permissions up', 'p.id', '=', 'up.permission_id')
            ->where('up.user_id', '=', 1)
            ->orderBy('id', 'ASC')
            ->get();

        if(!empty($permissions)) {
            // If user data is found in database or in session, update user data and session data
            foreach($permissions as $permission) {
                $this->permissions[] = $permission['permission_name'];
            }
        }

        return $this->permissions ?? [];
    }

    public function loginUser(array $userData): bool {
        if(empty($userData) && !is_array($userData)) return false;

        // Check if user data is already in the database
        $userFoundData = Database::table('users')
            ->where('username', '=', $userData['username'])
            ->limit(1)
            ->get();

        if(!empty($userFoundData)) {
            // If user data is found in database or in session, update user data and session data
            $this->userData = $userFoundData[0];
        }

        // Update user data in session
        $_SESSION['user_data'] = $this->userData;

        // Update sesion USER id in session
        $_SESSION['user_id'] = $this->userData['id'];

        // Update status in session
        $_SESSION['logged_in'] = true;

        // Update remember_token in cookie
        setcookie('PHPSESSID', session_id(), time() + (86400 * 30), "/"); // 30 days
        
        // Update remember_token in session
        $_SESSION['remember_token'] = session_id();

        // Update remember_token in database
        $this->rememberMe();


        $setData = array(
            'logged_in' => true,
            'permissions' => $this->getUserPermitions($this->userData['id'] ?? -1),
            'last_activity' => time(),
        );
        
        // Load any additional user data from your database here
        $this->userData = array_merge($this->userData, $setData);
        return false;
    }

    public function checkUserSession(): void {
        // Check if cookie exists and is not empty
        if(!isset($_COOKIE['PHPSESSID']) && empty($_COOKIE['PHPSESSID'])) return;

        // Check if user data is still valid in database or in session
        if(isset($this->userData) && !empty($this->userData) && ($this->userData['remember_token'] !== $_COOKIE['PHPSESSID'] || $this->userData['remember_token'] !== session_id() || $this->userData['remember_token'] === null)) {
            $this->logoutUser();
            // Redirect to login page
            ResponseFacede::redirect('/auth/login');
            exit;
        }

        // Check if user data is still valid in session
        if(isset($this->userData) && !empty($this->userData) && ($this->userData['remember_token'] === $_COOKIE['PHPSESSID']) ) {
            // Update user data in session
            $_SESSION['user_data'] = $this->userData;

            // Update sesion USER id in session
            $_SESSION['user_id'] = $this->userData['id'];

            // Update status in session
            $_SESSION['logged_in'] = true;

            // Update remember_token in cookie
            setcookie('PHPSESSID', session_id(), time() + (86400 * 30), "/"); // 30 days
            
            // Update remember_token in session
            $_SESSION['remember_token'] = session_id();

            // Update remember_token in database
            $this->rememberMe();


            $setData = array(
                'logged_in' => true,
                'permissions' => $this->getUserPermitions($this->userData['id'] ?? -1),
                'last_activity' => time(),
            );
            
            // Load any additional user data from your database here
            $this->userData = array_merge($this->userData, $setData);
            return;
        }

        return;
    }
}
