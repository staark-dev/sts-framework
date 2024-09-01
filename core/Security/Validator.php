<?php
namespace STS\core\Security;

use Closure;
use STS\core\Security\ValidationResult;
use STS\core\Facades\Database;
use STS\core\Http\Request;

class Validator {
    protected array $errors = [];

    protected array $customMessages = [];

    protected array $hooks = [
        'beforeValidation' => [],
        'afterValidation' => [],
    ];

    protected array $data = [];

    public function __construct(?Request $request) {
        $this->data = $request->post() ?? [];
    }
    
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
                $value = $this->data;
                foreach ($keys as $key) {
                    $value = $value[$key] ?? null;
                }
                $this->applyRules($value, $ruleSet, $field);
            } else {
                $this->applyRules($this->data[$field] ?? null, $ruleSet, $field);
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
            case 'string':
                return is_string($value);
            case 'between':
                [$min, $max] = explode(',', $param);
                return $value >= $min && $value <= $max;
            case 'unique':
                return $this->isUnique($field, $value, $param);
            case 'required_if':
                return $this->validateRequiredIf($value, $param, $field);
            case 'greater_than':
                return $value > $this->data[$param];
            case 'less_than':
                return $value < $this->data[$param];
            // Adaugă alte reguli de validare aici
            case 'same':
                return $this->validateSame($value, $param, $field);
            case 'accepted':
                return $this->validateAccepted($value, $field);
            case 'csrf_token':
                return $this->validateCsrfToken($value);
            default:
                return true;
        }
    }

    private function validateSame($value, $param, string $field): bool|string {
        // Verifică dacă parametrul $param (numele celuilalt câmp) există în datele furnizate
        if (!isset($this->data[$param])) {
            return "The field $param does not exist in the provided data.";
        }
   
        return ($value === $this->data[$param]);
    }

    
    private function validateRequiredIf($value, $param, string $field): bool|string {
        if (strpos($param, ',') === false) {
            return "Invalid parameter format for required_if rule. Expected format: 'otherField,otherValue'.";
        }
    
        // Împarte parametrul în două părți
        [$otherField, $otherValue] = explode(',', $param, 2);
    
        // Verifică dacă câmpul `otherField` are valoarea `otherValue` și dacă câmpul curent `$field` nu este gol
        if (($this->data[$otherField] ?? null) == $otherValue && empty($value)) {
            return "The $field field is required when $otherField is $otherValue.";
        }
    
        return true;
    }    

    private function validateAccepted($value, string $field): bool|string {
        return in_array($value, [true, '1', 1, 'on', 'yes'], true) ? true : "You must accept the $field.";
    }

    private function validateCsrfToken($value): bool|string {
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        return hash_equals($sessionToken, $value) ? true : "Invalid CSRF token.";
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