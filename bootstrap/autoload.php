<?php
use STS\core\Facades\Theme;

if (!function_exists('storage_path')) {
    function storage_path($path = '') {
        return ROOT_PATH . '/storage' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('resources_path')) {
    function resources_path($path = '') {
        return ROOT_PATH . '/resources' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('theme_assets')) {
    function theme_assets(string $assetPath): string {
        $themeManager = app()->make(\STS\core\Themes\ThemeManager::class);
        return $themeManager->getAssetPath($assetPath);
    }
}

if (!function_exists('assets_path')) {
    function assets_path() {
        $server = "http://" . $_SERVER['HTTP_HOST'] . "/";
        return $server . 'assets';
    }
}

if (!function_exists('app')) {
    /**
     * @throws Exception
     */
    function app($make = null) {
        // Accesarea containerului global
        $container = \STS\core\Container::getInstance();

        if (is_null($make)) {
            return $container;
        }

        return $container->make($make);
    }
}

if (!function_exists('database_path')) {
    function database_path($path = '') {
        return ROOT_PATH . '/database' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('env')) {
    function env($key, $default = null) {
        $value = getenv($key);

        if ($value === false) {
            return $default;
        }

        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return null;
        }

        return $value;
    }
}

if (!function_exists('load_env')) {
    function load_env($path = __DIR__ . '/../.env') {
        if (!file_exists($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
                putenv(sprintf('%s=%s', $key, $value));
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

if (!function_exists('base_path')) {
    function base_path($path = '') {
        return ROOT_PATH . ltrim($path, '/') . DIRECTORY_SEPARATOR;
    }
}

if (!function_exists('route')) {
    function route(string $name, array $params = []): string {
        return STS\core\Routing\Router::getInstance()->route($name, $params) ?? '';
    }
}

if (!function_exists('url')) {
    function url(string $path = '', array $params = []): string {
        // Construim URL-ul complet adăugând schema și domeniul
        $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        
        // Construim calea completă
        $uri = $path;

        // Dacă există parametri, îi adăugăm la URL
        if (!empty($params)) {
            $query = http_build_query($params, "", "/");
            //$query = str_replace(["=", 'id'], "", $query);
            $uri .= '/' . $query;
        }
        
        return $scheme . '://' . $host . '/' . ltrim($uri, '/');
    }
}

function formTrans(string $key, array $params = []): string {
    // Încarcă traducerile folosind metoda temei
    $translations = Theme::loadThemesTranslations();

    // Verifică dacă traducerile au fost încărcate corect
    if (!is_array($translations)) {
        // Dacă traducerile nu au fost încărcate corect, returnează cheia sau un mesaj de eroare
        return "Translation array is not valid.";
    }

    // Obține mesajul tradus sau folosește cheia ca fallback
    $message = $translations[$key] ?? $key;

    // Înlocuiește parametrii în mesajul tradus
    foreach ($params as $paramKey => $paramValue) {
        $message = str_replace(':' . $paramKey, $paramValue, $message);
    }

    return $message;
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('auth')) {
    function auth() {
        // Logica funcției auth
        return isset($_SESSION['user']);
    }
}


if (!function_exists('session')) {
    function session($key = null) {
        if ($key === null) {
            return $_SESSION; // Returnează întregul array $_SESSION dacă nu este specificat niciun key
        }

        return $_SESSION[$key] ?? null; // Returnează valoarea cheii specificate sau null dacă nu există
    }
}
