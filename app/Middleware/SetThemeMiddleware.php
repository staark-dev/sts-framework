<?php
namespace App\Middleware;

use STS\core\Themes\ThemeManager;

class SetThemeMiddleware {
    protected $themeManager;

    public function __construct(ThemeManager $themeManager) {
        $this->themeManager = $themeManager;
    }

    public function handle($request, $next) {
        // Poți să alegi tema pe baza unei logici personalizate
        $this->themeManager->setActiveTheme('modern');

        return $next($request);
    }
}
