<?php
namespace STS\core\Themes;

use STS\core\Routing\Router;

class GlobalVariables
{
    protected array $variables = [];

    public function __construct()
    {
        // Inițializează variabilele globale
        $this->variables = [
            'page_title' => 'STS Framework',
            'descriptions' => '',
            
            // Functions usefull
            'csrf_token' => fn() => csrf_token(),
            'auth_user' => fn() => $_SESSION['user'] ?? null,
            'base_url' => fn() => rtrim((isset($_SERVER['HTTPS']) ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}", '/'),
            'session' => fn($key = null) => $key ? $_SESSION[$key] ?? null : $_SESSION,
            'auth' => fn() => isset($_SESSION['user_id']),
            'trans' => fn($key, $params = []) => app('translation')->get($key, $params),
            //'url' => fn($path = '') => app()->make(Router::class)->url($path),
            'route' => fn($name, $params = []) => app('Router')->route($name, $params),
            // Adaugă alte variabile globale aici...
            'url' => fn($path = '') => url($path),
        ];

        return $this->variables;
    }

    public function get($key)
    {
        return $this->variables[$key] ?? null;
    }

    public function all()
    {
        return $this->variables;
    }

    public function set($key, $value)
    {
        $this->variables[$key] = $value;
    }
}
