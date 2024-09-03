<?php
declare(strict_types=1);
namespace STS\core\Routing;

use STS\core\Http\Response as HttpResponse;
use STS\core\Facades\Theme;
use STS\core\Container;
use STS\core\Facades\ResponseFacade as Response;
use STS\core\Exceptions\ControllerNotFoundException;
use STS\core\Exceptions\MethodNotFoundException;
use STS\core\Http\MiddlewareRegistry;
use STS\core\Http\Request;

class Router {
    protected static ?Router $instance = null;
    protected array $routes = [];
    protected array $middleware = []; // Definirea proprietății middleware pentru a preveni erorile
    public array $namedRoutes = [];
    protected string $currentGroupPrefix = '';
    protected array $currentGroupMiddleware = [];
    protected array $cacheMiddleware = [];

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

    public function middleware(string|array $middleware): self
    {
        if (is_array($middleware)) {
            foreach ($middleware as $mw) {
                $this->addMiddleware($mw);
            }
        } else {
            $this->addMiddleware($middleware);
        }

        return $this;
    }

    protected function addRoute(string $method, string $uri, $action): Route {
        // Adaugă prefixul de grup la URI
        $uri = $this->currentGroupPrefix . $uri;
        
        $route = new Route($method, $uri, $action);
    
        // Aplică middleware-urile definite la nivel de rută și de grup
        if (!empty($this->middleware)) {
            $route->middleware(...$this->middleware);
        }
    
        if (!empty($this->currentGroupMiddleware)) {
            $route->middleware(...$this->currentGroupMiddleware);
        }
    
        $this->routes[$method][] = $route;
        
        // Resetează middleware-urile după adăugarea rutei
        $this->middleware = [];
    
        return $route;
    }
    

    /*protected function addRoute(string $method, string $uri, $action): Route {
        // Adaugă prefixul de grup la URI
        $uri = $this->currentGroupPrefix . $uri;
        
        $route = new Route($method, $uri, $action);
        $route->middleware(...$this->middleware);
        $route->middleware(...$this->currentGroupMiddleware);
        $this->routes[$method][] = $route;
    
        // Resetează middleware-urile după adăugarea rutei
        $this->middleware = [];

        return $route;
    }*/

    public function dispatch(Request $request) {
        $method = $request->server('REQUEST_METHOD');
        $uri = strtok($request->server('REQUEST_URI'), '?'); // Extrage URI-ul fără query string
    
        // Detectează și servește resurse statice
        if (preg_match('/([\w\-\/]+)(\.min)?\.(css|js|jpg|jpeg|png|gif|svg|woff|woff2|ttf|eot)$/i', $uri, $matches)) {
            $resourceName = $matches[1];
            $isMinified = !empty($matches[2]); // Verifică dacă este minificat
            $extension = $matches[3];
    
            if ($extension === 'js' && preg_match('/bootstrap\.(.*?)\.js$/i', $uri, $files)) {
                $this->handleJSResource(str_replace(".js", "", $files[0]), $isMinified);
                return;
            }
    
            switch ($extension) {
                case 'css':
                    $this->handleCSSResource($resourceName, $isMinified);
                    break;
                case 'js':
                    $this->handleJSResource($resourceName, $isMinified);
                    break;
                case 'woff':
                case 'woff2':
                case 'ttf':
                case 'eot':
                    $this->handleFontResource($resourceName);
                    break;
                default:
                    $this->serveStaticResource($resourceName . '.' . $extension);
                    break;
            }
            return;
        }

        // Parcurge toate rutele pentru metoda specificată
        foreach ($this->routes[$method] as $route) {
            $params = $this->match($route->getUri(), $uri);
            if (!$params) continue;
    
            // Obține controllerul și metoda din acțiune
            list($controllerClass, $method) = explode('@', $route->getAction());
            
            // Utilizare corectă a containerului pentru a crea instanța controllerului
            $controller = "STS\\app\\Controllers\\$controllerClass";
            $controllerInstance = Container::getInstance()->make($controller);
    
            // Aplică middleware-urile definite în controller
            $this->applyControllerMiddleware($controllerInstance, $method);
    
            // Execută middleware-urile asociate rutei
            $response = $this->handleMiddlewares($route, $request);
    
            if ($response instanceof Response) {
                add_log('Route matched: '. $route->getUri(), 'route_match');
                return $response; // Dacă middleware-ul a returnat un răspuns, încheie execuția
            }
    
            // Execută acțiunea controllerului
            if ($route->getAction() instanceof \Closure) {
                return call_user_func_array($route->getAction(), $params ?? []);
            }
    
            if (is_string($route->getAction())) {
                return $this->resolveControllerAction($route->getAction(), is_array($params) ? $params : []);
            }
        }

        /**
         * Trebuie să implementezi criteriul de căutare ��
         * Varianta Buna...
         */
        /*foreach ($this->routes[$method] as $route) {
            $params = $this->match($route->getUri(), $uri);
            if (!$params) continue;

            // Aplică middleware-urile asociate rutei
            $response = $this->handleMiddlewares($route, $request);

            if ($response instanceof Response) {
                return $response; // Dacă middleware-ul a returnat un răspuns, încheie execuția
            }

            // Verifică tipul acțiunii și execută acțiunea controlerului
            if ($route->getAction() instanceof \Closure) {
                return call_user_func_array($route->getAction(), $params ?? []);
            }

            if (is_string($route->getAction())) {
                // Dacă `$params` este `true`, înlocuiește-l cu un array gol
                return $this->resolveControllerAction($route->getAction(), is_array($params) ? $params : []);
            }
        }*/

        // Nu s-a găsit nicio rută; returnează 404
        return $this->handleNotFound();
    }

    public function addMiddleware(string $middleware): self
    {
        // Verifică dacă middleware-ul este deja adăugat
        if (!in_array($middleware, $this->middleware, true)) {
            $this->middleware[] = $middleware; // Adaugă middleware-ul la lista de middleware-uri pentru ruta curentă
        }

        return $this;
    }
    
    protected function applyControllerMiddleware($controllerInstance, string $method) {
        // Obține middleware-urile definite la nivel de controller
        $globalMiddlewares = $controllerInstance->getMiddleware();
        
        // Obține middleware-urile pentru acțiunea specifică
        $actionMiddlewares = $controllerInstance->getMiddlewareForActions()[$method] ?? [];
    
        // Combină middleware-urile globale și cele pentru acțiunea specifică
        $middlewares = array_merge($globalMiddlewares, $actionMiddlewares);

        //add_log('... [Middleware executed !]', 'Controller Middleware');
        // Aplică middleware-urile combinate
        foreach ($middlewares as $middleware) {
            if (is_string($middleware)) {
                $this->addMiddleware($middleware);
            } elseif (is_array($middleware)) {
                foreach ($middleware as $singleMiddleware) {
                    $this->addMiddleware($singleMiddleware);
                }
            }
        }
    }

    private function handleMiddlewares($route, $request) {
        if( is_array($this->middleware) && !empty($this->middleware) ) {
            $route->middleware(...$this->middleware);
        }

        $middlewares = $route->getMiddleware();
    
        if (empty($middlewares)) {
            return null;
        }

        $handler = function($request) use (&$middlewares, $route, &$handler) {
            if (empty($middlewares)) {
                return $this->executeRouteAction($route->getAction(), []);
            }

            // Obține primul middleware fără a-l șterge din array
            $middlewareDefinition = $middlewares[0]; 

            // Verifică dacă middleware-ul are un argument suplimentar (de ex., "check_permission:permission:view_home")
            if (strpos($middlewareDefinition, ':') !== false) {
                list($middlewareClass, $argument) = explode(':', $middlewareDefinition, 2);
            } else {
                $middlewareClass = $middlewareDefinition;
                $argument = null;
            }
    
            // Verifică și obține middleware-ul din registru
            if (!MiddlewareRegistry::exists($middlewareClass)) {
                throw new \Exception("Middleware $middlewareClass not found in registry.");
            }
    
            $middlewareInstance = MiddlewareRegistry::getMiddlewareInstance($middlewareClass);
    
            if (!is_object($middlewareInstance) || !method_exists($middlewareInstance, 'handle')) {
                throw new \Exception("Middleware $middlewareClass is not a valid object or does not have a handle method.");
            }
    
            // Elimină middleware-ul procesat din listă după ce este determinat
            array_shift($middlewares);
    
            // Dacă există un argument suplimentar, execută cu el
            if ($argument !== null) {
                return $middlewareInstance->handle($request, function($request) use ($handler) {
                    return $handler($request);
                }, $argument);
            }
    
            // Execută middleware-ul fără argumente suplimentare
            return $middlewareInstance->handle($request, function($request) use ($handler) {
                return $handler($request);
            });
        };
    
        return $handler($request);
    }
    
    private function executeRouteAction($action, $params) {
        if (is_callable($action))
            return call_user_func_array($action, $params);
    
        if (is_string($action))
            return $this->resolveControllerAction($action, $params);

        throw new Exception("Invalid action format: $action");
        return $this->handleNotFound();
    }
    

    // Funcție pentru a gestiona resurse CSS
    protected function handleCSSResource(string $resourceName, bool $isMinified): void {
        echo "/*\n";
        echo " * CSS Resource: " . str_replace(".css", "", $resourceName) . "\n";
        echo " * Minified: " . ($isMinified ? 'Yes' : 'No') . "\n";
        echo " */\n\r\n";

        // Elimină ".min" dacă există, pentru a obține numele de bază al fișierului CSS
        $resourceName = str_replace(".min", "", $resourceName);
        $this->serveStaticResource($resourceName . '.css');
    }

    // Funcție pentru a gestiona resurse JS
    protected function handleJSResource(string $resourceName, bool $isMinified): void {
        echo "/*\n";
        echo " * JS Resource: " . str_replace(".js", "", $resourceName) . "\n";
        echo " * Minified: " . ($isMinified ? 'Yes' : 'No') . "\n";
        echo " */\n\r\n";
    
        // Elimină ".min" dacă există, pentru a obține numele de bază al fișierului JS
        $resourceName = str_replace(".min", "", $resourceName);
    
        // Asigură-te că `getThemePath` returnează calea corectă
        $themePath = $this->getThemePath();
        $fullPath = $themePath . $resourceName . '.js';

        // Verifică dacă fișierul există și încarcăl
        $this->serveStaticResource($fullPath);
    }
    
    // Funcție pentru a gestiona resurse de fonturi
    protected function handleFontResource(string $resourceName): void {
        $extension = pathinfo($resourceName, PATHINFO_EXTENSION);

        // Asigură-te că ai setat corect tipul MIME pentru font
        $mimeTypes = [
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'otf' => 'font/otf',
            'eot' => 'application/vnd.ms-fontobject',
            'svg' => 'image/svg+xml'
        ];

        $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream'; // Tip generic în caz de necunoscut
        header('Content-Type: ' . $mimeType);
        readfile($resourceName);
        exit;
    }

    protected function getThemePath(): string {
        // Obține configurația temei din container și construiește calea corectă
        $themeConfig = app('theme.config');
        return '/themes/' . $themeConfig['active_theme'] . '/assets/bootstrap/js/';
    }

    protected function cacheContent(string $content, string $cachedFile): void {
        file_put_contents($cachedFile, $content);
    }
    
    protected function loadFromCache(string $cachedFile): string {
        return file_get_contents($cachedFile);
    }

    protected function match(string $routeUri, string $requestUri): mixed {
        // Normalizează ambele căi pentru a elimina slash-urile de la sfârșit
        $normalizedRouteUri = rtrim($routeUri, '/');
        $normalizedRequestUri = rtrim($requestUri, '/');
    
        // Compară calea rutei și cererea
        if ($normalizedRouteUri === $normalizedRequestUri) {
            return true; // Returnează un array gol pentru o potrivire exactă fără parametri
        }
    
        // Implementare suplimentară pentru potrivirea cu parametrii dinamici (de ex., /user/{id})
        $pattern = preg_replace('/\{[a-zA-Z]+\}/', '([^/]+)', $normalizedRouteUri);
        if (preg_match('#^' . $pattern . '$#', $normalizedRequestUri, $matches)) {
            return $matches;
        }
    
        // Dacă nu există potrivire, returnează fals
        return false;
    }
    
    protected function resolveControllerAction(string $action, array $params = []) {
        // Desparte acțiunea în numele controllerului și al metodei
        list($controller, $method) = explode('@', $action);
    
        // Creează numele complet al clasei controllerului cu namespace
        $controllerClass = "STS\\app\\Controllers\\$controller";
    
        // Verifică dacă clasa controllerului există
        if (!class_exists($controllerClass)) {
            throw new ControllerNotFoundException("Controller not found: $controllerClass");
        }
    
        // Obține instanța controllerului din container (pentru a gestiona dependențele)
        $controllerInstance = Container::getInstance()->make($controllerClass);
    
        // Verifică dacă metoda există în instanța controllerului
        if (!method_exists($controllerInstance, $method)) {
            throw new MethodNotFoundException("Method not found: $controllerClass@$method");
        }
    
        // Aplică middleware-urile definite în controler
        $this->applyControllerMiddleware($controllerInstance, $method);
    
        // Creează instanța de Request
        $request = Container::getInstance()->make('STS\\core\\Http\\Request');
    
        // Dacă nu sunt parametri suplimentari, trimite obiectul Request ca parametru
        if (empty($params)) {
            $params = [$request];
        }
    
        // Apelează metoda controllerului cu parametrii specificați
        return call_user_func_array([$controllerInstance, $method], $params);
    }

    /**
     * 
     * Varianta buna
     */
    /*protected function applyControllerMiddleware($controllerInstance, string $method) {
        // Obține middleware-urile definite la nivel de controler
        $globalMiddlewares = $controllerInstance->getMiddleware(); // Folosește metoda getMiddleware()
        
        // Obține middleware-urile pentru acțiunea specifică
        $actionMiddlewares = $controllerInstance->getMiddlewareForActions()[$method] ?? [];

        // Combină middleware-urile globale și cele pentru acțiunea specifică
        $middlewares = array_merge($globalMiddlewares, $actionMiddlewares);

        // Aplică middleware-urile combinate
        foreach ($middlewares as $middleware) {
            $this->middleware($middleware);
        }
    }*/

    /*
    protected function resolveControllerAction(string $action, array $params = []) {
        // Desparte acțiunea în numele controllerului și al metodei
        list($controller, $method) = explode('@', $action);
        
        // Creează numele complet al clasei controllerului cu namespace
        $controllerClass = "STS\\app\\Controllers\\$controller";
        
        // Verifică dacă clasa controllerului există
        if (!class_exists($controllerClass)) {
            throw new ControllerNotFoundException("Controller not found: $controllerClass");
        }

        // Obține instanța controllerului din container (pentru a gestiona dependențele)
        $controllerInstance = Container::getInstance()->make($controllerClass);

        // Verifică dacă metoda există în instanța controllerului
        if (!method_exists($controllerInstance, $method)) {
            throw new MethodNotFoundException("Method not found: $controllerClass@$method");
        }

        // Obține instanța Request-ului
        $request = Container::getInstance()->make('STS\\core\\Http\\Request');

        // Dacă nu sunt parametri, trimite obiectul Request ca parametru
        if (empty($params)) {
            $params = [$request];
        }

        // Apelează metoda controllerului cu parametrii specificați
        return call_user_func_array([$controllerInstance, $method], [$params]);
    }*/
    
    protected function handleNotFound(): void
    {
        echo Theme::render('errors/404');
        Response::setBody("404 Not Found");
        Response::setStatusCode(404);
        exit;
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
            $uri = str_replace("{{$key}}", (string) $value, $uri); // Convertește $value la string
        }
    
        // Returnăm URI-ul ca string
        return $uri;
    }
    
    protected function serveStaticResource($filePath, $ext = []) {
        // Construiește calea completă către resursă
        $resourcePath = dirname(__DIR__, 2) . '/resources/' . $filePath;

        // Verifică dacă fișierul există și este un fișier valid
        if (file_exists($resourcePath) && is_file($resourcePath)) {
            $mimeType = mime_content_type($resourcePath);
    
            // Asigură-te că tipul MIME este corect
            if ($mimeType === 'text/plain') {
                $extension = pathinfo($resourcePath, PATHINFO_EXTENSION);
                switch ($extension) {
                    case 'css':
                        $mimeType = 'text/css';
                        break;
                    case 'js':
                        $mimeType = 'application/javascript';
                        break;
                    case 'esm.js': // Neobișnuit, dar îl putem păstra dacă este necesar
                        $mimeType = 'application/javascript'; // Folosește MIME corect pentru JavaScript
                        break;
                    default:
                        break; // Nu face nimic pentru alte extensii
                }
            }
    
            // Setează antetul de tip MIME
            header('Content-Type: ' . $mimeType);
    
            // Adaugă antetul de cache pentru performanță
            header("Cache-Control: public, max-age=31536000"); // Cache pentru 1 an
            header("Expires: " . gmdate("D, d M Y H:i:s", time() + 31536000) . " GMT");
    
            // Servește fișierul
            readfile($resourcePath);
            exit;
        }
    
        // Gestionează cazul în care resursa nu este găsită
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