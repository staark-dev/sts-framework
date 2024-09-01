<?php
namespace STS\core\Providers\App;

use STS\core\Container;
use STS\core\Providers\ServiceProvider;
use STS\core\Database\Connection;
use STS\core\Routing\Router;
use STS\core\Http\HttpKernel;
use STS\core\Http\Request;
use STS\core\Themes\ThemeManager;
use STS\core\Translation\Translation;
use STS\core\Themes\GlobalVariables;
use STS\core\Helpers\FormHelper;
use STS\core\Facades\Theme;
use STS\core\Facades\Globals;
use STS\core\Facades\Translate;

final class AppServiceProvider extends ServiceProvider
{
    protected ?Container $container;
    protected $starttime;
    protected $endtime;

    public function __construct(Container $container) {
        parent::__construct($container);
    }

    public function register(): void
    {
        $this->starttime = microtime(true);

        // Înregistrează serviciile modularizate
        $this->registerConfig();
        $this->registerDatabase();
        $this->registerThemes();
        $this->registerSessions();

        // Înregistrează alte servicii necesare
        $this->registerRouter();
        $this->registerRequest();
        $this->registerResponse();
        $this->registerKernel();
    }

    private function registerConfig(): void
    {
        $this->container->singleton('config', function() {
            return require ROOT_PATH . '/config/app.php';
        });
    }

    private function registerDatabase(): void
    {
        $this->container->singleton('db.config', function() {
            return require ROOT_PATH . '/config/database.php';
        });

        // Inregistrează serviciul de baza de date in container
        $this->container->singleton('db.connection', function($container) {
            return \STS\core\Database\Connection::getInstance(
                $container->make('db.config')['connections']['mysql']
            );
        });

        // Definirea unui alias pentru un serviciu comun
        $this->container->alias('db', 'db.connection');
    }

    private function registerThemes(): void
    {
        // Inregistrează configuratiile in container
        $this->container->singleton('theme.config', function() {
            return require ROOT_PATH . '/config/theme.php';
        });

        $this->container->singleton('global.vars', function() {
            return new \STS\core\Themes\GlobalVariables();
        });

        $this->container->singleton('theme.trans', function() {
            return new \STS\core\Translation\Translation(Theme::getLocale());
        });

        // Inregistrează serviciul de baza de date in container
        $this->container->registerClass('STS\core\Themes\ThemeManager', 10);
        $this->container->registerClass('STS\core\Helpers\FormHelper', 25);

        // Definirea unui alias pentru un serviciu comun
        $this->container->alias('theme', 'STS\core\Themes\ThemeManager');
        $this->container->alias('form', 'STS\core\Helpers\FormHelper');
        $this->container->alias('global_vars', 'STS\core\Themes\GlobalVariables');
    }

    private function registerSessions(): void
    {
        // Inregistrează serviciul de baza de date in container
        //$this->container->registerClass('CustomSessionHandler', 5);
        $this->container->singleton('CustomSessionHandler', function($container) {
            return new \STS\core\Session\CustomSessionHandler(
                $container->make('db'),
                'sessions'
            );
        });

        // Definirea unui alias pentru Request pentru a accesa obiectul de request
        $this->container->alias('session.handler', 'CustomSessionHandler');
    }

    private function registerRouter(): void
    {
        $this->container->singleton('Router', function($container) {
            return \STS\core\Routing\Router::getInstance();
        });
    }

    private function registerRequest(): void
    {
        $this->container->singleton('Request', function($container) {
            return STS\core\Http\Request::collection();
        });
    }

    private function registerResponse(): void
    {
        // Inregistrează serviciul de baza de date in container
        $this->container->registerClass('STS\core\Http\Response', 5);

        // Definirea unui alias pentru Response pentru a accesa obiectul de response
        $this->container->alias('http.response', 'STS\core\Http\Response');
    }

    private function registerKernel(): void
    {
        $this->container->singleton('http.kernel', function($container) {
            return new \STS\core\Http\HttpKernel($container);
        });
    }

    public function boot(): void
    {
        $config = $this->container->make('config');

        if(is_array($config['providers']) && !empty($config['providers'])) {
            // Incarcare provideri din aplicatie !
            // Încărcarea providerilor definiți în `config/app.php`

            foreach ($config['providers'] as $providerClass) {
                try {
                    if (class_exists($providerClass) && method_exists($providerClass, 'register')) {
                        $providerClass::register($this->container);
                    }
                } catch (\Exception $e) {
                    // Loghează eroarea sau aruncă o excepție
                    throw new \Exception("Eroare la înregistrarea providerului: " . $providerClass);
                    error_log("Eroare la înregistrarea providerului: " . $providerClass);
                }
            }
        }

        if($config['env'] === "dev" && $config['debug'] !== false && $config['url'] !== "localhost")
        {
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(E_ALL);
        }

        if($config['env'] === "production")
        {
            // TODO: Production
        }

        // Incarcare variabile si date din .env
        load_env();

        // Start sessiune si handler
        @session_set_save_handler($this->container->make('session.handler'), true);
        @session_start();
        @date_default_timezone_set($config['default_timezone']);
        @locale_set_default($config['locale']);

        $this->endtime = microtime(true);

        // Setări pentru Themes
        // Verifică dacă theme.config este înregistrat în container
        if ($this->container->has('theme.config')) {
            $themeConfig = $this->container->make('theme.config');

            // Setări pentru Themes
            Theme::setPath(ROOT_PATH . '/themes/' . ($themeConfig['active_theme'] ?? 'default')); // fallback la 'default'
            Theme::setUrl(env('APP_URL', 'http://localhost:8000'));
            Theme::setTitle(env('APP_NAME', 'STS Framework'));
            Theme::setMetaDescription(env('APP_DESCRIPTION', 'A simple PHP framework'));
            Theme::setKeywords(env('APP_KEYWORDS', 'PHP, framework, STS'));
            Theme::setCopyright(env('APP_COPYRIGHT', '2022 STS Framework'));
            Theme::setThemeLayout(env('APP_THEME_LAYOUT', 'default'));
            Theme::setThemeLayoutPath(ROOT_PATH . '/themes/' . ($themeConfig['active_theme'] ?? 'default') . '/layouts');
            Theme::setLocale(env('APP_LOCALE', 'en_US'));
            Theme::setActiveTheme($themeConfig['active_theme'] ?? 'default');
            
            Theme::assign('app_url', env('APP_URL', 'http://localhost:8000'));
            Globals::set('app_name', env('APP_NAME', 'STS Framework'));

            Globals::set('footer_copyright', sprintf("<p class=\"text-center text-body-secondary copyright\">%s | <a href='%s'>%s</a> | <a href='%s'>%s</a></p>", 
                Translate::trans('app_copyright'), Translate::trans('app_powered_by_link'), Translate::trans('app_powered_by', ['name' => 'STS Solutions']), Translate::trans('app_bugs_link'), Translate::trans('Report an Bug')
            ));

            Globals::set('loading_app', sprintf("<p class='text-center load_app_time'>Running %s | Version: %s | %s</p>", 
                Translate::trans('app'), Translate::trans('app_version'), Translate::trans('app_loading_time', ['numbers' => number_format(($this->endtime - $this->starttime), 3)])
            ));
        } else {
            // Tratament pentru cazul în care 'theme.config' nu este disponibil
            throw new \Exception("Theme configuration not found in the container.");
        }
    }
}