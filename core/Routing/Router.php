<?php
declare(strict_types=1);
namespace STS\core\Routing;

use STS\core\Http\Response;

class Router {
    protected static ?Router $instance = null;
    protected array $routes = [];
    public array $namedRoutes = [];
    protected string $currentGroupPrefix = '';
    protected array $currentGroupMiddleware = [];

    // Constructor privat pentru a preveni instanțierea directă
    private function __construct() {}

    public static function getInstance(): Router {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function get(string $uri, $action): Route {
        return self::getInstance()->addRoute('GET', $uri, $action);
    }

    public static function post(string $uri, $action): Route {
        return self::getInstance()->addRoute('POST', $uri, $action);
    }

    public static function put(string $uri, $action): Route {
        return self::getInstance()->addRoute('PUT', $uri, $action);
    }

    public static function delete(string $uri, $action): Route {
        return self::getInstance()->addRoute('DELETE', $uri, $action);
    }

    public static function resource(string $uri, string $controller): void
    {
        self::get("$uri", "$controller@index");
        self::get("$uri/create", "$controller@create");
        self::post("$uri", "$controller@store");
        self::get("$uri/{id}", "$controller@show");
        self::get("$uri/{id}/edit", "$controller@edit");
        self::put("$uri/{id}", "$controller@update");
        self::delete("$uri/{id}", "$controller@destroy");
    }

    public function addNamedRoute(string $name, Route $route): void {
        $this->namedRoutes[$name] = $route->getUri();
    }

    public static function group(array $options, callable $callback): void
    {
        $instance = self::getInstance();

        // Salvează prefixul și middleware-urile curente
        $previousGroupPrefix = $instance->currentGroupPrefix;
        $previousGroupMiddleware = $instance->currentGroupMiddleware;

        // Actualizează prefixul și middleware-urile curente pentru grupul curent
        $instance->currentGroupPrefix = $previousGroupPrefix . ($options['prefix'] ?? '');
        $instance->currentGroupMiddleware = array_merge($instance->currentGroupMiddleware, $options['middleware'] ?? []);

        // Execută callback-ul pentru a defini rutele din grup
        call_user_func($callback);

        // Resetează prefixul și middleware-urile la valorile anterioare
        $instance->currentGroupPrefix = $previousGroupPrefix;
        $instance->currentGroupMiddleware = $previousGroupMiddleware;
    }

    protected function addRoute(string $method, string $uri, $action): Route {
        // Adaugă prefixul de grup la URI
        $uri = $this->currentGroupPrefix . $uri;

        $route = new Route($method, $uri, $action);
        $route->middleware(...$this->currentGroupMiddleware);
        $this->routes[$method][] = $route;

        return $route;
    }

    /**
     * @throws \Exception
     */
    public function dispatch($request) {
        $method = $request->server('REQUEST_METHOD');
        $uri = $request->server('REQUEST_URI');
        $uri = strtok($uri, '?');
        
        if (preg_match('/([\w\-\/]+)(\.min)?\.(css|js|jpg|jpeg|png|gif|svg|woff|woff2|ttf|eot)$/i', $uri, $matches)) {
            $resourceName = $matches[1];
            $isMinified = $matches[2] ?? '';
            $extension = $matches[3];
            
            echo "/*!\n";
            echo " * Resource: " . $resourceName . "\n";
            echo " * Minified: " . ($isMinified ? 'Yes' : 'No') . "\n";
            echo " * Extension: " . $extension . "\n";
            echo " */\n\r\n";

            $this->serveStaticResource($resourceName . $isMinified . '.' . $extension);
            return;
        }

        foreach ($this->routes[$method] as $route) {
            if ($params = $this->match($route->getUri(), $uri)) {
                // Execută middleware-urile
                foreach ($route->getMiddleware() as $middleware) {
                    $middlewareInstance = app()->make($middleware);

                    // Apelează middleware-ul cu $request și un callback care trimite cererea către următorul middleware sau controller
                    $response = $middlewareInstance->handle($request, function($request) use ($route) {
                        return $this->resolveControllerAction($route->getAction(), $request);
                    });
                
                    // Dacă un middleware returnează un răspuns, oprește execuția aici
                    if ($response instanceof Response) {
                        return $response;
                    }
                }

                // Execută acțiunea rutei cu parametrii extrași
                $action = $route->getAction();

                if (is_callable($action)) {
                    return call_user_func_array($action, $params);
                }

                if (is_string($action)) {
                    return $this->resolveControllerAction($action, $params);
                }
            }

            if(strcasecmp($route->getUri(), $uri) === 0) {
                // Codul din Router.php care gestionează middlewares

                foreach ($route->getMiddleware() as $middleware) {
                    $middlewareInstance = app()->make($middleware);

                    // Apelează middleware-ul cu $request și un callback care trimite cererea către următorul middleware sau controller
                    $response = $middlewareInstance->handle($request, function($request) use ($route) {
                        return $this->resolveControllerAction($route->getAction(), $request);
                    });
                
                    // Dacă un middleware returnează un răspuns, oprește execuția aici
                    if ($response instanceof Response) {
                        return $response;
                    }
                }

                // Execută acțiunea rutei cu parametrii extrași
                $action = $route->getAction();

                if (is_callable($action)) {
                    return call_user_func_array($action, []);
                }

                if (is_string($action)) {
                    return $this->resolveControllerAction($action, []);
                }
            }
        }

        return $this->handleNotFound();
    }

    protected function match(string $routeUri, string $requestUri): ?array {
        $routeUri = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[a-zA-Z0-9_\-]+)', $routeUri);
        $routeUri = '#^' . $routeUri . '$#';

        if (preg_match($routeUri, $requestUri, $matches)) {
            return array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
        }

        return null;
    }

    /**
     * @throws \Exception
     */
    protected function resolveControllerAction(string $action, array $params = []) {
        list($controller, $method) = explode('@', $action);

        $controller = "STS\\app\\Controllers\\$controller";

        if (class_exists($controller)) {
            $controllerInstance = app()->make($controller);
            if (method_exists($controllerInstance, $method)) {
                return call_user_func_array([$controllerInstance, $method], $params);
            }
        }

        throw new \Exception("Controller or method not found: $controller@$method");
    }

    protected function handleNotFound(): void
    {
        http_response_code(404);
        echo "404 Not Found";
    }

    protected function handleNotUrl(): void
    {
        http_response_code(200);
        echo file_get_contents(ROOT_PATH . $_SERVER['REQUEST_URI']);
    }

    /**
     * Găsește o rută după nume și returnează URL-ul, înlocuind parametrii.
     *
     * @param string $name
     * @param array $params
     * @return string
     * @throws \Exception
     */
    public function route(string $name, array $params = []): string {
        if (!isset($this->namedRoutes[$name])) {
            throw new \Exception("No route found with the name '$name'");
        }
    
        $route = $this->namedRoutes[$name];
        $uri = $route->getUri();
    
        // Înlocuim parametrii în URI dacă este cazul
        foreach ($params as $key => $value) {
            $uri = str_replace("{{$key}}", $value, $uri);
        }
    
        // Returnăm URI-ul ca string
        return $uri;
    }
    

    // Funcție pentru a servi resurse statice
    protected function serveStaticResource($filePath) {
        $resourcePath = realpath(__DIR__ . '/../../resources/' . $filePath);
    
        if (file_exists($resourcePath) && is_file($resourcePath)) {
            $mimeType = mime_content_type($resourcePath);
    
            // Asigură-te că tipul MIME este corect
            if ($mimeType === 'text/plain') {
                if (pathinfo($resourcePath, PATHINFO_EXTENSION) === 'css') {
                    $mimeType = 'text/css';
                } elseif (pathinfo($resourcePath, PATHINFO_EXTENSION) === 'js') {
                    $mimeType = 'application/javascript';
                }
            }
    
            header('Content-Type: ' . $mimeType);
            readfile($resourcePath);
            exit;
        }
    
        $this->handleNotFound();
    }

    public function url(string $name, array $params = []): string {
        $uri = $this->route($name, $params);
    
        // Construim URL-ul complet adăugând schema și domeniul
        $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        
        return $scheme . '://' . $host . $uri;
    }    
}