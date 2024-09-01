<?php
namespace STS\core\Security;

use Closure;
use STS\core\Security\ValidationResult;
use STS\core\Facades\Database;

class Validator {
    protected array $errors = [];

    protected array $customMessages = [];

    protected array $hooks = [
        'beforeValidation' => [],
        'afterValidation' => [],
    ];

    protected array $data = [];

    public function __construct() {}
    
    public function validate(array $data, array $rules): ValidationResult
    {
        // Apelează un hook înainte de validare, dacă există
        $this->triggerHook('beforeValidation', $data, $rules);
        
        // Efectuează validarea și obține rezultatul
        $this->performValidation($data, $rules);
        
        // Apelează un hook după validare, dacă există
        $this->triggerHook('afterValidation', $data, $rules, empty($this->errors));

        // Returnează un obiect de tip ValidationResult
        return new ValidationResult(empty($this->errors), $this->errors);
    }

    protected function performValidation(array $data, array $rules): void
    {
        foreach ($rules as $field => $ruleSet) {
            if (strpos($field, '.') !== false) {
                $keys = explode('.', $field);
                $value = $data;
                foreach ($keys as $key) {
                    $value = $value[$key] ?? null;
                }
                $this->applyRules($value, $ruleSet, $field);
            } else {
                $this->applyRules($data[$field] ?? null, $ruleSet, $field);
            }
        }
    }

    protected function applyRules($value, string $ruleSet, string $field): void {
        $rules = explode('|', $ruleSet);
        foreach ($rules as $rule) {
            [$ruleName, $ruleValue] = array_pad(explode(':', $rule), 2, null);
            if (!$this->applyRule($ruleName, $value, $ruleValue, $field)) {
                $this->errors[$field][] = $this->getErrorMessage($field, $ruleName, $ruleValue);
            }
        }
    }

    protected function applyRule(string $rule, $value, $param, string $field): bool {
        switch ($rule) {
            case 'required':
                return !empty($value);
            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
            case 'url':
                return filter_var($value, FILTER_VALIDATE_URL) !== false;
            case 'numeric':
                return is_numeric($value);
            case 'between':
                [$min, $max] = explode(',', $param);
                return $value >= $min && $value <= $max;
            case 'unique':
                return $this->isUnique($field, $value, $param);
            case 'required_if':
                [$otherField, $otherValue] = explode(',', $param);
                return ($this->data[$otherField] ?? null) == $otherValue ? !empty($value) : true;
            case 'greater_than':
                return $value > $this->data[$param];
            case 'less_than':
                return $value < $this->data[$param];
            // Adaugă alte reguli de validare aici
            default:
                return true;
        }
    }

    protected function isUnique(string $field, $value, string $param): bool {
        // Desparte parametrul pentru a obține numele tabelului și al coloanei
        [$table, $column] = explode(',', $param);

        // Verifică dacă valoarea există deja în baza de date
        $exists = Database::existsInDb($table, $column, $value);

        // Returnează true dacă valoarea nu există deja (este unică)
        return !$exists;
    }

    public function errors(): array {
        return $this->errors;
    }

    public function setCustomMessages(array $messages): self {
        $this->customMessages = $messages;
        return $this;
    }

    protected function getErrorMessage(string $field, string $rule, $param = null): string {
        $key = "{$field}.{$rule}";
        if (isset($this->customMessages[$key])) {
            return $this->customMessages[$key];
        }
        return sprintf('%s does not satisfy the %s condition.', ucfirst($field), $rule);
    }

    public function addHook(string $name, Closure $callback): void {
        $this->hooks[$name][] = $callback;
    }

    protected function triggerHook(string $name, ...$params): void {
        if (isset($this->hooks[$name])) {
            foreach ($this->hooks[$name] as $hook) {
                $hook(...$params);
            }
        }
    }
}