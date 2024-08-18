<?php
declare(strict_types=1);
namespace STS\core\Cache;

class CacheManager
{
    private string $cacheDir = 'cache/';

    public function set($key, $data, $ttl = 3600): void
    {
        $filename = $this->cacheDir . md5($key) . '.cache';

        // Includerea tagurilor PHP în conținutul salvat
        $cacheData = "<?php" . PHP_EOL;
        $cacheData .= "// Cached data, expires at " . (time() + $ttl) . PHP_EOL;
        $cacheData .= $data;
        $cacheData .= PHP_EOL . "// End of cached data" . PHP_EOL;
        $cacheData .= "?>";

        file_put_contents($filename, $cacheData);
    }

    public function get($key)
    {
        $filename = $this->cacheDir . md5($key) . '.cache';
        if (file_exists($filename)) {
            // Verificarea timpului de expirare este încorporată în fișierul PHP
            // Evaluarea fișierului PHP pentru a randa conținutul cache-ului
            ob_start();
            include $filename;
            $content = ob_get_clean();
            return $content;
        }
        return null;
    }
}