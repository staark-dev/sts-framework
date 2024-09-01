<?php
namespace STS\core\Security;

class ValidationResult
{
    protected $passed;
    protected $errors;

    public function __construct(bool $passed, array $errors = [])
    {
        $this->passed = $passed;
        $this->errors = $errors;
    }

    public function passes(): bool
    {
        return $this->passed;
    }

    public function errors(): array
    {
        return $this->errors;
    }
}