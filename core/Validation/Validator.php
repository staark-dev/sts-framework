<?php
namespace STS\core\Validation;

use STS\core\Database\Orm;

class Validator
{
    protected array $data;
    protected array $rules;
    protected array $errors = [];

    public function __construct(array $data, array $rules)
    {
        $this->data = $data;
        $this->rules = $rules;
    }

    /**
     * Validate the data against the rules.
     *
     * @return bool
     */
    public function validate(): bool
    {
        foreach ($this->rules as $field => $rules) {
            foreach ($rules as $rule) {
                if (!$this->validateRule($field, $rule)) {
                    $this->errors[$field][] = $rule;
                }
            }
        }

        return empty($this->errors);
    }

    /**
     * Validate a single rule against a field.
     *
     * @param string $field
     * @param string $rule
     * @return bool
     */
    protected function validateRule(string $field, string $rule): bool
    {
        $value = $this->data[$field] ?? null;

        // Rule: required
        if ($rule === 'required' && is_null($value)) {
            return false;
        }

        // Rule: email
        if ($rule === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        // Rule: min:<length>
        if (preg_match('/min:(\d+)/', $rule, $matches)) {
            $min = (int)$matches[1];
            if (strlen($value) < $min) {
                return false;
            }
        }

        // Rule: max:<length>
        if (preg_match('/max:(\d+)/', $rule, $matches)) {
            $max = (int)$matches[1];
            if (strlen($value) > $max) {
                return false;
            }
        }

        // Rule: unique:<table>,<column>
        if (preg_match('/unique:([a-z_]+),([a-z_]+)/', $rule, $matches)) {
            $table = $matches[1];
            $column = $matches[2];
            if ($this->isValueExistsInDatabase($table, $column, $value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if a value already exists in the database (for unique rule).
     *
     * @param string $table
     * @param string $column
     * @param string $value
     * @return bool
     */
    protected function isValueExistsInDatabase(string $table, string $column, string $value): bool
    {
        $orm = new Orm($table);
        return $orm->table($table)->where($column, '=', $value)->exists();
    }

    /**
     * Get all validation errors.
     *
     * @return array
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Check if the validation passed.
     *
     * @return bool
     */
    public function passes(): bool
    {
        return $this->validate();
    }

    /**
     * Check if the validation failed.
     *
     * @return bool
     */
    public function fails(): bool
    {
        return !$this->passes();
    }
}