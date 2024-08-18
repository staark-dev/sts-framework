<?php
declare(strict_types=1);
namespace STS\core\Themes;

   /**
     * New functions
     * {{ trans('welcome.message', {'name': 'John'}) }}
     * {{ route('home') }}
     * {{ url('/about') }}
     * {{ session('user_id') }}
     * {{ auth() ? 'Logged in' : 'Guest' }}
     */

class ThemeManager {
    protected array $themes = [];
    protected string $activeTheme;
    protected string $templatePath;
    protected ?string $cachePath;
    protected array $variables = [];
    protected array $sections = [];
    protected ?string $extends = null;

    public function __construct() {
        $config = app()->make('config')->get('theme');
        $this->loadThemes($config['theme_path']);
        $this->setActiveTheme($config['active_theme']);
        $this->cachePath = null; //$config['cache_path'] ?? null;
    }

    protected function loadThemes(string $themePath): void {
        foreach (glob($themePath . '/*', GLOB_ONLYDIR) as $dir) {
            $name = basename($dir);
            $configFile = $dir . '/theme.json';
            if (file_exists($configFile)) {
                $config = json_decode(file_get_contents($configFile), true);
                $config['path'] = $dir;
                $this->themes[$name] = $config;
            }
        }
    }

    public function setActiveTheme(string $themeName): void {
        if (!isset($this->themes[$themeName])) {
            throw new \Exception("Tema {$themeName} nu există.");
        }

        $this->activeTheme = $themeName;
        $this->templatePath = $this->themes[$themeName]['path'] . '/views';
    }

    public function assign(string $key, $value): void {
        $this->variables[$key] = $value;
    }

    public function render(string $template): string {
        if(!empty($this->variables))
            extract($this->variables);
        
        $templateFile = $this->findTemplateFile($template);

        if (!file_exists($templateFile)) {
            throw new \Exception("Template-ul {$template} nu a fost găsit.");
        }
    
        // Cache handling
        $cachedFile = $this->getCachedFile($template);
    
        if ($this->cachePath && $this->isCacheValid($templateFile, $cachedFile)) {
            ob_start();
            include $cachedFile; // Evaluăm cache-ul
            return ob_get_clean();
        }

        $content = file_get_contents($templateFile);
        $content = $this->parseExtends($content);
        $content = $this->parseSections($content);

        if ($this->extends) {
            $layoutFile = $this->templatePath . '/' . $this->extends . '.html';
            $layoutContent = file_get_contents($layoutFile);
            $layoutContent = $this->injectSections($layoutContent);
            $content = $layoutContent;
        }

        $content = $this->parseVariables($content);
        $content = $this->parseIncludes($content);
        $content = $this->parseDirectives($content);
        $content = $this->parseTranslations($content);
        $content = $this->parseRoutes($content);
        $content = $this->parseUrls($content);
        $content = $this->parseSessions($content);
        $content = $this->parseAuth($content);
    
        // Save the processed content in cache with PHP tags
        if ($this->cachePath) {
            $cacheContent = "<?php" . PHP_EOL;
            $cacheContent .= "/* Cached on " . date('Y-m-d H:i:s') . " */" . PHP_EOL;
            $cacheContent .= "?>" . PHP_EOL;
            $cacheContent .= $content;
            file_put_contents($cachedFile, '<?php declare(strict_types=1); class_exists(\'' . __CLASS__ . '\') or exit; ?>' . PHP_EOL . $cacheContent);
    
            return $cacheContent;
        }
    
        // Return the final rendered content
        ob_start();
        eval('?>' . $content);
        return ob_get_clean();
    }
    
    public function display(string $template): void {
        echo $this->render($template);
    }

    protected function findTemplateFile(string $template): ?string {
        $extensions = ['.php', '.html'];
        foreach ($extensions as $extension) {
            $filePath = $this->templatePath . '/' . $template . $extension;
            if (file_exists($filePath)) {
                return $filePath;
            }
        }
        return null;
    }

    protected function getCachedFile(string $template): string {
        return $this->cachePath . '/' . md5($template) . '.cache';
    }

    protected function isCacheValid(string $templateFile, string $cachedFile): bool {
        if (!file_exists($cachedFile)) {
            return false;
        }

        $templateModifiedTime = filemtime($templateFile);
        $cacheModifiedTime = filemtime($cachedFile);

        return $cacheModifiedTime >= $templateModifiedTime;
    }

    public function clearCache(?string $template = null): void {
        if ($template) {
            $cachedFile = $this->getCachedFile($template);
            if (file_exists($cachedFile)) {
                unlink($cachedFile);
            }
        } elseif ($this->cachePath) {
            $files = glob($this->cachePath . '/*.cache');
            foreach ($files as $file) {
                unlink($file);
            }
        }
    }

    public function getAssetPath(string $asset): string {
        return "http://" . $_SERVER['HTTP_HOST'] . '/themes/' . $this->activeTheme . '/assets/' . ltrim($asset, '/');
    }

    protected function parseExtends(string $content): string {
        return preg_replace_callback('/@extends\s*\(\s*[\'"](.+?)[\'"]\s*\)/', function ($matches) {
            $this->extends = $matches[1];
            return ''; // Nu redă imediat conținutul extins
        }, $content);
    }

    protected function parseSections(string $content): string {
        // Capturarea secțiunilor
        return preg_replace_callback('/@section\s*\(\s*[\'"](.+?)[\'"]\s*\)(.*?)@endsection/s', function ($matches) {
            $this->sections[$matches[1]] = $matches[2];
            return ''; // Nu redă imediat conținutul secțiunilor
        }, $content);
    }

    protected function injectSections(string $content): string {
        foreach ($this->sections as $section => $value) {
            $content = preg_replace("/@yield\s*\(\s*[\'\"]{$section}[\'\"]\s*\)/", $value, $content);
        }

        return $content;
    }

    protected function parseIncludes(string $content): string {
        return preg_replace_callback('/@include\s*\(\s*[\'"](.+?)[\'"]\s*\)/', function ($matches) {
            $includeFile = $this->findTemplateFile($matches[1]);
    
            if (!file_exists($includeFile)) {
                throw new \Exception("Fișierul de include {$matches[1]} nu a fost găsit.");
            }
    
            // Evaluate the included content
            ob_start();
            include $includeFile;
            return ob_get_clean();
        }, $content);
    }
    
    protected function parseVariables(string $content): string {
        // Replace {{ theme_assets('path/to/asset') }} with actual asset path
        $content = preg_replace_callback('/\{\{\s*theme_assets\(\s*[\'"](.+?)[\'"]\s*\)\s*\}\}/', function ($matches) {
            return '<?=$this->getAssetPath(\'' . $matches[1] . '\'); ?>';
        }, $content);
    
        // Other variable replacements
        $content = preg_replace('/\{\{\s*(.+?)\s*\}\}/', '<?=$$1;?>', $content);
    
        return $content;
    }    

    protected function parseDirectives(string $content): string {
        $directives = [
            '/@if\s*\((.+?)\)/' => '<?php if ($1): ?>',
            '/@elseif\s*\((.+?)\)/' => '<?php elseif ($1): ?>',
            '/@else/' => '<?php else: ?>',
            '/@endif/' => '<?php endif; ?>',
            '/@foreach\s*\((.+?)\)/' => '<?php foreach ($1): ?>',
            '/@endforeach/' => '<?php endforeach; ?>',
            '/@while\s*\((.+?)\)/' => '<?php while ($1): ?>',
            '/@endwhile/' => '<?php endwhile; ?>',
            '/@for\s*\((.+?)\)/' => '<?php for ($1): ?>',
            '/@endfor/' => '<?php endfor; ?>',
            '/@switch\s*\((.+?)\)/' => '<?php switch($1): ?>',
            '/@case\s*\((.+?)\)/' => '<?php case $1: ?>',
            '/@default/' => '<?php default: ?>',
            '/@endswitch/' => '<?php endswitch; ?>',
        ];

        foreach ($directives as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        return $content;
    }

    protected function extractAssignedVariables(): void {
        extract($this->variables);
    }

    protected function translate(string $key, array $params = []): string {
        $translator = app()->make(\STS\core\Translation\Translation::class);
        return $translator->translate($key, $params);
    }
    
    protected function parseTranslations(string $content): string {
        return preg_replace_callback('/\{\{\s*trans\(\s*[\'"](.+?)[\'"],\s*(.*?)\s*\)\s*\}\}/', function ($matches) {
            $params = json_decode($matches[2], true);
            return '<?=$this->translate(\'' . $matches[1] . '\', ' . var_export($params, true) . '); ?>';
        }, $content);
    }
    

    protected function route(string $name, array $params = []): string {
        $router = app()->make(\STS\core\Routing\Router::class);
        return $router->route($name, $params);
    }
    
    protected function parseRoutes(string $content): string {
        return preg_replace_callback('/\{\{\s*route\(\s*[\'"](.+?)[\'"],\s*(.*?)\s*\)\s*\}\}/', function ($matches) {
            $params = json_decode($matches[2], true);
            return '<?=$this->route(\'' . $matches[1] . '\', ' . var_export($params, true) . '); ?>';
        }, $content);
    }
    

    protected function url(string $path = ''): string {
        return "http://" . $_SERVER['HTTP_HOST'] . '/' . ltrim($path, '/');
    }
    
    protected function parseUrls(string $content): string {
        return preg_replace_callback('/\{\{\s*url\(\s*[\'"](.+?)[\'"]\s*\)\s*\}\}/', function ($matches) {
            return '<?=$this->url(\'' . $matches[1] . '\'); ?>';
        }, $content);
    }
    
    protected function session(string $key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }
    
    protected function parseSessions(string $content): string {
        return preg_replace_callback('/\{\{\s*session\(\s*[\'"](.+?)[\'"]\s*\)\s*\}\}/', function ($matches) {
            return '<?=$this->session(\'' . $matches[1] . '\'); ?>';
        }, $content);
    }
    

    protected function isAuthenticated(): bool {
        $auth = app()->make(\STS\core\Auth\Auth::class);
        return $auth->check();
    }
    
    protected function parseAuth(string $content): string {
        return preg_replace_callback('/\{\{\s*auth\(\)\s*\}\}/', function () {
            return '<?=$this->isAuthenticated(); ?>';
        }, $content);
    }
    
    protected function currentUser(): ?array {
        $auth = app()->make(\STS\core\Auth\Auth::class);
        return $auth->user();
    }    
}