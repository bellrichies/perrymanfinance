<?php

declare(strict_types=1);

namespace PerrymanFinance\Validation;

use PerrymanFinance\Http\Exceptions\ValidationException;

final class Validator
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, list<string>> $rules
     * @return array<string, mixed>
     */
    public function validate(array $data, array $rules): array
    {
        $errors = [];
        $validated = [];
        foreach ($rules as $field => $fieldRules) {
            $exists = array_key_exists($field, $data);
            $value = $data[$field] ?? null;
            foreach ($fieldRules as $rule) {
                $parts = explode(':', $rule, 2);
                $name = $parts[0];
                $argument = $parts[1] ?? null;
                $message = $this->check($name, $argument, $exists, $value);
                if ($message !== null) {
                    $errors[$field][] = $message;
                }
            }
            if ($exists) {
                $validated[$field] = $value;
            }
        }
        if ($errors !== []) {
            throw new ValidationException($errors);
        }
        return $validated;
    }

    private function check(string $rule, ?string $argument, bool $exists, mixed $value): ?string
    {
        if ($rule === 'required') {
            return !$exists || $value === null || $value === '' ? 'This field is required.' : null;
        }
        if (!$exists || $value === null) {
            return null;
        }
        return match ($rule) {
            'string' => is_string($value) ? null : 'This field must be a string.',
            'integer' => is_int($value) ? null : 'This field must be an integer.',
            'boolean' => is_bool($value) ? null : 'This field must be a boolean.',
            'email' => $this->validEmail($value) ? null : 'This field must be a valid email address.',
            'url' => $this->validUrl($value) ? null : 'This field must be a valid URL.',
            'slug' => $this->matches($value, '/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                ? null : 'This field must be a valid slug.',
            'uuid' => $this->matches(
                $value,
                '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            ) ? null : 'This field must be a valid UUID.',
            'min' => $this->length($value) >= (int) $argument
                ? null : "This field must be at least {$argument} characters.",
            'max' => $this->length($value) <= (int) $argument
                ? null : "This field must not exceed {$argument} characters.",
            'in' => in_array((string) $value, explode(',', (string) $argument), true)
                ? null : 'This field contains an unsupported value.',
            default => throw new \InvalidArgumentException("Unknown validation rule: {$rule}"),
        };
    }

    private function length(mixed $value): int
    {
        return is_string($value) ? mb_strlen($value) : PHP_INT_MAX;
    }

    private function validEmail(mixed $value): bool
    {
        return is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function validUrl(mixed $value): bool
    {
        return is_string($value) && filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    private function matches(mixed $value, string $pattern): bool
    {
        return is_string($value) && preg_match($pattern, $value) === 1;
    }
}
