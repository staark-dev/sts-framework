<?php
namespace STS\core\Helpers;

use STS\core\Security\Validator;

class FormHelper
{
    protected Validator $validator;

    public function __construct()
    {
        $this->validator = new Validator();
    }

    /**
     * Generează un tag <form> de început.
     *
     * @param string $action
     * @param string $method
     * @param array $attributes
     * @return string
     */
    public function open(string $action = '', string $method = 'POST', array $attributes = []): string
    {
        $attrs = $this->attributes($attributes);
        return "<form action=\"{$action}\" method=\"{$method}\" {$attrs}>";
    }

    /**
     * Generează un tag <input>.
     *
     * @param string $type
     * @param string $name
     * @param string|null $value
     * @param array $attributes
     * @return string
     */
    public function input(string $type, string $name, ?string $value = null, array $attributes = []): string
    {
        $value = $value ?? $this->getValue($name);
        $attrs = $this->attributes($attributes);
        $valueAttr = $value ? "value=\"{$value}\"" : '';

        $attributes['type'] = $type;
        $attributes['name'] = $name;
        $attributes['value'] = htmlspecialchars((string)$value, ENT_QUOTES);
        $attributesString = $this->buildHtmlAttributes($attributes);
        
        $errorClass = $this->hasError($name) ? 'is-invalid' : '';
        $errorMessage = $this->getError($name);
        //  . ($errorMessage ? "<div class=\"invalid-feedback\">{$errorMessage}</div>" : '')
        
        //return sprintf('<input %s>', $attributesString);

        return "<input type=\"{$type}\" name=\"{$name}\" {$valueAttr} {$attrs} class=\"{$errorClass}\" />"
               . ($errorMessage ? "<div class=\"invalid-feedback\">{$errorMessage}</div>" : '');
    }

    protected function getValue(string $name)
    {
        return $_POST[$name] ?? $_GET[$name] ?? $_SESSION[$name] ?? null;
    }

    protected function hasError(string $name): bool
    {
        return isset($_SESSION['errors'][$name]);
    }
    
    public function label(string $name, ?string $text = null, array $attributes = []): string
    {
        $attrs = $this->attributes($attributes);
        $text = $text ? trans($text) : ucfirst(trans($name));
        return "<label for=\"{$name}\" {$attrs}>{$text}</label>";
    }
    
    protected function getError(string $name): ?string
    {
        return isset($_SESSION['errors'][$name]) ? trans($_SESSION['errors'][$name]) : null;
    }
    

    /**
     * Generează un tag <textarea>.
     *
     * @param string $name
     * @param string|null $content
     * @param array $attributes
     * @return string
     */
    public function textarea(string $name, ?string $content = '', array $attributes = []): string
    {
        $attrs = $this->attributes($attributes);
        return "<textarea name=\"{$name}\" {$attrs}>{$content}</textarea>";
    }

    /**
     * Generează un tag <select>.
     *
     * @param string $name
     * @param array $options
     * @param string|null $selected
     * @param array $attributes
     * @return string
     */
    public function select(string $name, array $options, ?string $selected = null, array $attributes = []): string
    {
        $attrs = $this->attributes($attributes);
        $optionsHtml = '';
    
        foreach ($options as $value => $label) {
            $selectedAttr = $value == $selected ? 'selected' : '';
            $optionsHtml .= "<option value=\"{$value}\" {$selectedAttr}>{$label}</option>";
        }
    
        return "<select name=\"{$name}\" {$attrs}>{$optionsHtml}</select>";
    }

    /**
     * Generează un tag <button>.
     *
     * @param string $text
     * @param array $attributes
     * @return string
     */
    public function button(string $text, array $attributes = []): string
    {
        $attrs = $this->attributes($attributes);
        return "<button {$attrs}>{$text}</button>";
    }

    /**
     * Generează un tag <form> de încheiere.
     *
     * @return string
     */
    public function close(): string
    {
        return '</form>';
    }

    /**
     * Convertește un array de atribute în string HTML.
     *
     * @param array $attributes
     * @return string
     */
    protected function attributes(array $attributes): string
    {
        $html = '';

        foreach ($attributes as $key => $value) {
            $html .= "{$key}=\"{$value}\" ";
        }

        return trim($html);
    }

    public function csrfToken(): string
    {
        $token = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        
        return "<input type=\"hidden\" name=\"csrf_token\" value=\"{$token}\">";
    }

    public function checkbox(string $name, string $value, ?bool $checked = false, array $attributes = []): string
    {
        $attrs = $this->attributes($attributes);
        $checkedAttr = $checked ? 'checked' : '';
        return "<input type=\"checkbox\" name=\"{$name}\" value=\"{$value}\" {$checkedAttr} {$attrs} />";
    }
    
    public function radio(string $name, string $value, ?bool $checked = false, array $attributes = []): string
    {
        $attrs = $this->attributes($attributes);
        $checkedAttr = $checked ? 'checked' : '';
        return "<input type=\"radio\" name=\"{$name}\" value=\"{$value}\" {$checkedAttr} {$attrs} />";
    }
    
    public function openMultiStep(string $action = '', string $method = 'POST', array $attributes = []): string
    {
        // Stocăm datele formularului în sesiune pentru fiecare pas
        $_SESSION['form_data'] = $_SESSION['form_data'] ?? [];
        
        $attrs = $this->attributes($attributes);
        return "<form action=\"{$action}\" method=\"{$method}\" {$attrs}>";
    }

    public function saveStepData(array $data): void
    {
        $_SESSION['form_data'] = array_merge($_SESSION['form_data'], $data);
    }

    public function getStepData(): array
    {
        return $_SESSION['form_data'] ?? [];
    }

    public function clearFormData(): void
    {
        unset($_SESSION['form_data']);
    }

    /*public function fillFromDatabase(string $table, string $primaryKey, $id): void
    {
        $orm = app()->make('orm');
        $data = $orm->table($table)->find($id);

        if ($data) {
            foreach ($data as $key => $value) {
                $this->variables[$key] = $value;
            }
        }
    }*/

    protected function buildHtmlAttributes(array $attributes): string {
        $html = '';
        foreach ($attributes as $key => $value) {
            $html .= sprintf('%s="%s" ', htmlspecialchars($key, ENT_QUOTES), htmlspecialchars($value, ENT_QUOTES));
        }
        return rtrim($html); // Elimină spațiul suplimentar de la final
    }
    
}