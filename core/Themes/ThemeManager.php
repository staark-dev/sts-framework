<?php
declare(strict_types=1);
namespace STS\core\Themes;

use STS\core\Themes\GlobalVariables;
use STS\core\Helpers\FormHelper;

class ThemeManager {
    protected array $themes = [];
    protected string $activeTheme;
    protected string $templatePath;
    protected ?string $cachePath;
    protected array $variables = [];
    protected array $sections = [];
    protected ?string $extends = null;
    protected array $translations = [];
    protected ?string $locale = 'en';

    public function __construct() {
        // Gaseste si incarca datele pentru teme !
        $config = app('theme.config');

        // Incarca configuratia pentru teme
        $this->loadThemes($config['theme_path']);
        $this->setActiveTheme($config['active_theme']);
        $this->cachePath = $config['cache_path'];

        // Încarcă traducerile pentru limba activă
        $this->loadTranslations($this->locale);

        // Inițializează variabilele globale
        $this->variables = array_merge($this->variables, app()->make('globals')->all());
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

    protected function loadTranslations(string $locale): void
    {
        $translationFile = ROOT_PATH . "/resources/lang/{$locale}/messages.php";
        if (file_exists($translationFile)) {
            $this->translations = include $translationFile;
        } else {
            throw new \Exception("Fișierul de traducere pentru limba {$locale} nu a fost găsit.");
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

        // Replace {{ variable }} or {{ function_name(arguments) }} with the corresponding PHP code
        $content = preg_replace_callback('/\{\{\s*(.+?)\s*\}\}/', function ($matches) {
            $variable = trim($matches[1]);

            // Verifică dacă variabila este definită în array-ul de variabile
            if (array_key_exists($variable, $this->variables)) {
                return '<?=$this->variables[\'' . $variable . '\'];?>';
            }

            // Verifică dacă este o variabilă globală
            $globals = app('globals');
            if ($value = $globals->get($variable)) {
                //var_dump($globals->get($variable) ?? $value);
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
            '/@if\s*\((.+?)\)/' => '<?php if ($1): ?>',
            '/@elseif\s*\((.+?)\)/' => '<?php elseif($1): ?>',
            '/@else/' => '<?php else: ?>',
            '/@endif/' => '<?php endif; ?>',
            '/@foreach\s*\((.+?)\)/' => '<?php foreach ($1 as $key => $value): ?>',
            '/@endforeach/' => '<?php endforeach; ?>',
            '/@while\s*\((.+?)\)/' => '<?php while ($1): ?>',
            '/@endwhile/' => '<?php endwhile; ?>',
            '/@for\s*\((.+?)\)/' => '<?php for ($1): ?>',
            '/@endfor/' => '<?php endfor; ?>',
            '/@switch\s*\((.+?)\)/' => '<?php switch($1): ?>',
            '/@case\s*\((.+?)\)/' => '<?php case $1: ?>',
            '/@default/' => '<?php default: ?>',
            '/@endswitch/' => '<?php endswitch; ?>',
            '/@auth/' => '<?php if($this->variables[\'auth\']()): ?>',
            '/@endauth/' => '<?php endif; ?>',
            '/@guest/' => '<?php if(!$this->variables[\'auth\']()): ?>',
            '/@endguest/' => '<?php endif; ?>',
            '/@csrf/' => '<?= \'csrf_token:\'.$this->variables[\'csrf_token\'](); ?>',
        ];

        foreach ($directives as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        return $content;
    }

    public function url(string $path = ''): string {
        return "http://" . $_SERVER['HTTP_HOST'] . '/' . ltrim($path, '/');
    }
}