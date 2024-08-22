<?php
declare(strict_types=1);
namespace STS\core\Translation;

class Translation {
    private $locale = 'en';
    private array $translations = [];

    public function __construct($locale) {
        $this->locale = $locale ?? 'en';
        $this->loadTranslations();
    }

    private function loadTranslations(): void
    {
        var_dump(dirname(__DIR__, 2));
        $this->translations = require rtrim(dirname(__DIR__, 2) . '/lang/' . $this->locale . '.php', '\\/');
    }

    public function translate($key) {
        var_dump($key);
        return $this->translations[$key] ?? $key;
    }
}