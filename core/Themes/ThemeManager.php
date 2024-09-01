<?php
declare(strict_types=1);
namespace STS\core\Themes;

use STS\core\Themes\GlobalVariables;
use STS\core\Helpers\FormHelper;
use STS\core\Facades\Theme;
use STS\core\Facades\Globals;
use STS\core\Facades\Translate;
use STS\core\Facades\Auth;

class ThemeManager {
    # Theme Path
    protected string $pathinfo;
    protected array $themes = [];
    protected string $activeTheme;
    protected string $templatePath;
    protected ?string $cachePath;
    protected array $variables = [];
    protected array $sections = [];
    protected ?string $extends = null;
    protected array $blocks = [];
    protected array $translations = [];
    protected ?string $themeLayoutPath;
    protected ?string $themeLayout;
    protected ?string $locale = 'en';

    public function __construct() {
        // Gaseste si incarca datele pentru teme !
        $config = app('theme.config');

        // Incarca configuratia pentru teme
        $this->pathinfo = $config['theme_path'];
        $this->loadThemes($config['theme_path']);
        $this->setActiveTheme($config['active_theme']);
        $this->cachePath = $config['cache_path'];

        // Încarcă traducerile pentru limba activă
        $this->loadTranslations($this->locale);

        // Inițializează variabilele globale
        $this->variables = array_merge($this->variables, Globals::all());
    }


    public function assign(string $key, $value): void {
        $this->variables[$key] = $value;
    }

    public function render(string $template): string {
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

        // Generate content if not in cache
        extract($this->variables);

        // Read and process the template content
        $content = file_get_contents($templateFile);
    
        // Parse the content: first extends, then includes, sections, variables, and directives
        $content = $this->parseExtends($content);
        $content = $this->parseSections($content);

        // If the template extends a layout, render the layout
        if ($this->extends) {
            $layoutFile = $this->templatePath . '/' . $this->extends . '.html';
            $layoutContent = file_get_contents($layoutFile);
            $layoutContent = $this->injectSections($layoutContent);
            $content = $layoutContent;
            $content = $this->parseIncludes($content); // Ensure this is only called once
        }

        // Parse Directive and Variables
        $content = $this->parseVariables($content);
        $content = $this->parseDirectives($content);

        // Save the processed content in cache with PHP tags
        if ($this->cachePath) {
            $cacheContent = "<?php" . PHP_EOL;
            $cacheContent .= "/* Cached on " . date('Y-m-d H:i:s') . " */" . PHP_EOL;
            $cacheContent .= "?>" . PHP_EOL;
            $cacheContent .= $content;
            file_put_contents($cachedFile, '<?php declare(strict_types=1); class_exists(\'' . __CLASS__ . '\') or exit; ?>' . PHP_EOL . $cacheContent);
        }
    
        // Return the final rendered content
        ob_start();
        eval('?>' . $content);
        return ob_get_clean();
    }
    

    public function setLocale(string $locale): void
    {
        if($locale === "") $this->locale = 'en';
        $this->locale = $locale;
    }

    public function getLocale(): string {
        return $this->locale;
    }

    protected function loadTranslations(string $locale): void
    {
        $this->translations = [];  // Reset the translations array;
        $this->locale = $locale;  // Setează limba activă

        $translationFile = ltrim(resources_path("/lang/{$locale}/messages.php"), '\\/');
        
        if (file_exists($translationFile)) {
            $this->translations = array_merge($this->translations, include $translationFile);
        } else {
            throw new \Exception("Fisierul de traducere pentru limba {$locale} nu a fost găsit.");
        }
    }

    public function loadThemesTranslations()
    {
        $translationFile = ltrim(resources_path("/lang/{$this->locale}/messages.php"), '\\/');

        if (file_exists($translationFile)) {
            return include $translationFile;
        } else {
            throw new \Exception("Fisierul de traducere pentru limba {$this->locale} nu a fost găsit.");
        }
    }

    public function trans(string $key, array $params = []): string
    {
        $translation = $this->translations[$key] ?? $key;

        // Înlocuiește parametrii din traducere
        foreach ($params as $key => $value) {
            $translation = str_replace('{' . $key . '}', $value, $translation);
        }

        return $translation;
    }

    function __(string $key, array $params = []): string {
        // Obține traducerile din sistem
        $translations = $this->loadThemesTranslations();

        // Verifică dacă traducerile sunt un array valid
        if (!is_array($translations)) {
            $translations = [];
        }

        // Obține mesajul din traduceri sau folosește cheia ca fallback
        $message = $translations[$key] ?? $key;

        // Traduce fiecare parametru recursiv
        foreach ($params as $paramKey => $paramValue) {
            // Verifică dacă paramValue este o cheie de traducere și traduce-l
            if (is_string($paramValue) && isset($translations[$paramValue])) {
                var_dump($paramValue = $translations[$paramValue]);
                $paramValue = $translations[$paramValue];
            }

            // Înlocuiește parametrii în mesaj
            $message = str_replace($paramKey, $paramValue, $message);
        }

        return $message;
    }    

    protected function loadThemes(string $themePath): void 
    {
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

    public function setActiveTheme(string $themeName): void 
    {
        if (!isset($this->themes[$themeName])) {
            throw new \Exception("Tema {$themeName} nu există.");
        }

        $this->activeTheme = $themeName;
        $this->templatePath = $this->themes[$themeName]['path'] . '/template';
        $this->setThemeLayout($this->themes[$themeName]['layouts']);
    }

    public function display(string $template, array $data = []): void {
        extract($data);
        $this->variables = array_merge($this->variables, $data);
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
        // Verificăm dacă există o directivă @extends în conținut
        if (preg_match('/@extends\s*\(\s*[\'"](.+?)[\'"]\s*\)/', $content, $matches)) {
            // Înregistrăm layout-ul care este extins
            $this->extends = $matches[1];

            // Eliminăm directiva @extends din conținut
            return preg_replace('/@extends\s*\(\s*[\'"](.+?)[\'"]\s*\)/', '', $content);
        }

        // Dacă nu există @extends, returnăm conținutul nemodificat
        return $content;
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
            if (!$includeFile || !file_exists($includeFile)) {
                throw new \Exception("Fișierul de include ". $matches[1] ." nu a fost găsit.");
            }
    
            $includeContent = file_get_contents($includeFile);
    
            return $includeContent;
        }, $content);
        
        return $content;
    }
    
    protected function parseVariables(string $content): string {
        // Replace {{ theme_assets('path/to/asset') }} with the actual asset path
        $content = preg_replace_callback('/\{\{\s*theme_assets\(\s*[\'"](.+?)[\'"]\s*\)\s*\}\}/', function ($matches) {
            return "<?php echo htmlspecialchars('{$this->getAssetPath($matches[1])}', ENT_QUOTES, 'UTF-8'); ?>";
        }, $content);

        // Replace {{ trans('key') }} with the actual translation
        $content = preg_replace_callback('/\{\{\s*trans\(\s*[\'"](.+?)[\'"]\s*(?:,\s*(\{.*?\}))?\s*\)\s*\}\}/', function ($matches) {
            $key = $matches[1];
            $params = isset($matches[2]) ? json_decode($matches[2], true) : [];
            return '<?= $this->trans(\'' . $key . '\', ' . var_export($params, true) . '); ?>';
        }, $content);

        // Înlocuiește {{ __('key', [parametri]) }} cu traducerea reală folosind callback-ul preg_replace
        $content = preg_replace_callback(
            '/\{\{\s*__\(\s*[\'"](.+?)[\'"]\s*(?:,\s*(\[(?:[^\[\]]|(?2))*\])\s*)?\)\s*\}\}/',
            function ($matches) {
                $key = $matches[1];

                // Convertește parametrii într-un array PHP valid
                $paramsString = $matches[2] ?? '[]';
                eval('$params = ' . $paramsString . ';');

                // Verifică dacă parametrii sunt un array valid
                if (!is_array($params)) {
                    $params = [];
                }

                // Construiește apelul metodei trans
                return '<?= $this->__(\'' . addslashes($key) . '\', ' . var_export($params, true) . '); ?>';
            }, $content);

        $content = preg_replace_callback('/\{\{\s*([\w\\\]+)::([\w]+)\((.*?)\)\s*\}\}/', function ($matches) {
            $class = $matches[1]; // Extragerea numelui clasei, ex: 'Auth'
            $method = $matches[2]; // Extragerea numelui metodei, ex: 'check'
            $params = $matches[3]; // Extragerea parametrilor funcției

            // Construiește apelul funcției folosind call_user_func_array
            return '<?= call_user_func_array([\STS\core\Facades\\' . $class . '::class, \'' . $method . '\'], [' . $params . ']); ?>';
        }, $content);

        // Replace {{ variable }} or {{ function_name(arguments) }} with the corresponding PHP code
        $content = preg_replace_callback('/\{\{\s*(.+?)\s*\}\}/', function ($matches) {
            $variable = trim($matches[1]);

            // Verifică dacă variabila este definită în array-ul de variabile
            if (array_key_exists($variable, $this->variables)) {
                return '<?=$this->variables[\'' . $variable . '\'];?>';
            }

            // Verifică dacă este o variabilă globală
            $globals = app('global_vars');
            if ($value = $globals->get($variable)) {
                return $value ?? '';
            }

            // Verificăm dacă expresia este o funcție definită în variabile
            if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\s*\((.*)\)$/', $variable, $funcMatches)) {
                $funcName = $funcMatches[1];
                $arguments = $funcMatches[2];
            
                // Asigurăm că funcția este validă și definită în variabile
                if(is_callable($this->variables[$funcName]) && array_key_exists($funcName, $this->variables)) {
                    return '<?= call_user_func($this->variables[\'' . $funcName . '\'], ' . $arguments . '); ?>';
                } else {
                    return "<?=$variable; ?>";
                }
            }
    
            // Verifică dacă este o simplă variabilă sau o expresie complexă
            if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $variable)) {
                return '<?=htmlspecialchars($' . $variable . ', ENT_QUOTES, "UTF-8"); ?>';
            } else {
                // Este o expresie complexă
                return "<?=$variable; ?>";
            }

            // Tratarea variabilelor simple
            if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $expression)) {
                //var_dump($this->variables[$expression]);
                return "<?=$this->variables[$expression]; />";
            }
    
            // Dacă ajungem aici, ceva nu este în regulă, returnăm expresia brută
            return "<?=$expression; ?>";
        }, $content);

        // Other variable replacements...
        $content = preg_replace('/\{\{\s*(.+?)\s*\}\}/', '<?=$$1;?>', $content);

        return $content;
    }
    
    protected function parseDirectives(string $content): string {
        $directives = [
            // Regex pentru @if, @elseif, @else și @endif
            '/@if\s*\((.+)\)/' => '<?php if ($1): ?>',
            '/@elseif\s*\((.+)\)/' => '<?php elseif ($1): ?>',
            '/@else/' => '<?php else: ?>',
            '/@endif/' => '<?php endif; ?>',
    
            // Folosește un callback pentru @foreach și @endforeach
            '/@foreach\s*\((\$\w+)\s+as\s*(\$\w+)(?:\s*=>\s*(\$\w+))?\)/' => function ($matches) {
                return '<?php foreach (' . $matches[1] . ' as ' . $matches[2] . (isset($matches[3]) ? ' => ' . $matches[3] : '') . '): ?>';
            },
            '/@endforeach/' => '<?php endforeach; ?>',
        
            // Regex pentru @while și @endwhile
            '/@while\s*\((.+?)\)/' => '<?php while ($1): ?>',
            '/@endwhile/' => '<?php endwhile; ?>',
    
            // Regex pentru @for și @endfor
            '/@for\s*\((.+?)\)/' => '<?php for ($1): ?>',
            '/@endfor/' => '<?php endfor; ?>',
    
            // Regex pentru @switch, @case, @default, @endswitch
            '/@switch\s*\((.+?)\)/' => '<?php switch($1): ?>',
            '/@case\s*\((.+?)\)/' => '<?php case $1: ?>',
            '/@default/' => '<?php default: ?>',
            '/@endswitch/' => '<?php endswitch; ?>',
    
            // Regex pentru @break și @continue
            '/@break/' => '<?php break; ?>',
            '/@continue/' => '<?php continue; ?>',
    
            // Regex pentru @auth și @endauth
            '/@auth/' => '<?php if($this->variables[\'auth\']()): ?>',
            '/@endauth/' => '<?php endif; ?>',
    
            // Regex pentru @guest și @endguest
            '/@guest/' => '<?php if(!$this->variables[\'auth\']()): ?>',
            '/@endguest/' => '<?php endif; ?>',
    
            // Regex pentru @csrf
            '/@csrf/' => '<?= \'csrf_token:\'.$this->variables[\'csrf_token\'](); ?>',
        ];
    
        foreach ($directives as $pattern => $replacement) {
            // Verifică dacă înlocuirea este un callback
            if (is_callable($replacement)) {
                $content = preg_replace_callback($pattern, $replacement, $content);
            } else {
                $content = preg_replace($pattern, $replacement, $content);
            }
        }
    
        return $content;
    }
    

    public function url(string $path = ''): string {
        return "http://" . $_SERVER['HTTP_HOST'] . '/' . ltrim($path, '/');
    }

    public function setPath(string $path): void {}
    public function setUrl(string $url): void {}
    public function setTitle(string $title): void {}
    public function setMetaDescription(string $description): void {}
    public function setAssetPath(string $path): void {}

    public function setThemeLayout(string $layout): void {
        $this->themeLayout = $layout;
    }
    public function setThemeLayoutPath(string $path): void {
        $this->themeLayoutPath = $path;
    }

    public function setKeywords(string $keywords): void {}
    public function setCopyright(string $copyright): void {}
    /*
    public function setVariable(string $name, $value): void {}
    public function setGlobalVariable(string $name, $value): void {}
    public function setVariables(array $variables): void {}
    public function setGlobalVariables(array $globalVariables): void {}
    public function setThemeViewsPath(string $path): void {}
    public function setThemeAssetsPath(string $path): void {}
    public function setThemeLayoutVars(array $vars): void {}
    public function setThemeViewVars(array $vars): void {}
    public function setThemeAssetVars(array $vars): void {}
    public function setThemeViewSections(array $sections): void {}
    public function setThemeAssetSections(array $sections): void {}
    public function setThemeViewFiles(array $files): void {}
    public function setThemeAssetFiles(array $files): void {}
    public function setThemeViewPaths(array $paths): void {}
    public function setThemeAssetPaths(array $paths): void {}
    public function setThemeViewExtensions(array $extensions): void {}
    public function setThemeAssetExtensions(array $extensions): void {}
    public function setThemeViewFilters(array $filters): void {}
    public function setThemeAssetFilters(array $filters): void {}
    public function setThemeViewComposers(array $composers): void {}
    public function setThemeAssetComposers(array $composers): void {}
    public function setThemeViewMiddleware(array $middleware): void {}
    public function setThemeAssetMiddleware(array $middleware): void {}
    public function setThemeViewVariables(array $variables): void {}
    public function setThemeAssetVariables(array $variables): void {}
    */
}