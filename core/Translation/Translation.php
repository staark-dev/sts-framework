<?php
declare(strict_types=1);
namespace STS\core\Translation;

use STS\core\Facades\Theme;

/**
 * Class Translation
 *
 * Handles translations for the application.
 * 
 */
class Translation {
    private $locale = 'en';
    private array $translations = [];

    public function __construct($locale) {
        $this->locale = $locale ?? 'en';
        $this->loadTranslations();
    }

    private function loadTranslations(): void
    {
        // TODO: Load translations from the specified locale file. For example, using require_once or include_once.
        $this->translations = Theme::loadThemesTranslations($this->locale) ?? [];
    }

    public function translate($key) {
        return $this->translations[$key] ?? $key;
    }

    public function trans(string $key, string|array $value = []): string {
        // Obține traducerea pentru cheia dată, sau folosește cheia ca fallback
        $translation = $this->translations[$key] ?? $key;
    
        // Dacă $value este un array, înlocuiește placeholderii în traducere
        if (is_array($value) && !empty($value)) {
            foreach ($value as $placeholder => $replacement) {
                $translation = str_replace(':' . $placeholder, $replacement, $translation);
            }
        }
    
        return $translation;
    }    
}