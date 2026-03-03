<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Validator Class
 * 
 * Provides data validation with customizable rules.
 */
class Validator
{
    /**
     * @var array Data to validate
     */
    private array $data;

    /**
     * @var array Validation rules
     */
    private array $rules;

    /**
     * @var array Custom error messages
     */
    private array $messages;

    /**
     * @var array Validation errors
     */
    private array $errors = [];

    /**
     * @var array Built-in validation rules
     */
    private static array $validators = [];

    /**
     * Constructor
     *
     * @param array $data Data to validate
     * @param array $rules Validation rules
     * @param array $messages Custom error messages
     */
    public function __construct(array $data, array $rules, array $messages = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->messages = $messages;
    }

    /**
     * Register custom validator
     *
     * @param string $name Validator name
     * @param callable $callback Validator callback
     * @return void
     */
    public static function extend(string $name, callable $callback): void
    {
        self::$validators[$name] = $callback;
    }

    /**
     * Run validation
     *
     * @return bool
     */
    public function validate(): bool
    {
        $this->errors = [];

        foreach ($this->rules as $field => $ruleString) {
            $rules = explode('|', $ruleString);
            $value = $this->data[$field] ?? null;
            $label = $field;

            // Extract label from rules if present (e.g., required|label:Email)
            foreach ($rules as $key => $rule) {
                if (str_starts_with($rule, 'label:')) {
                    $label = substr($rule, 6);
                    unset($rules[$key]);
                    $rules = array_values($rules);
                    break;
                }
            }

            foreach ($rules as $rule) {
                $params = [];
                
                // Parse rule with parameters (e.g., min:3)
                if (str_contains($rule, ':')) {
                    [$rule, $paramString] = explode(':', $rule, 2);
                    $params = explode(',', $paramString);
                }

                $result = $this->applyRule($rule, $field, $value, $params, $label);
                
                if ($result !== true) {
                    $this->errors[$field][] = $result;
                }
            }
        }

        return empty($this->errors);
    }

    /**
     * Check if validation fails
     *
     * @return bool
     */
    public function fails(): bool
    {
        return !$this->validate();
    }

    /**
     * Check if validation passes
     *
     * @return bool
     */
    public function passes(): bool
    {
        return $this->validate();
    }

    /**
     * Get validation errors
     *
     * @return array
     */
    public function errors(): array
    {
        $this->validate();
        return $this->errors;
    }

    /**
     * Get first error for a field
     *
     * @param string $field Field name
     * @return string|null
     */
    public function firstError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    /**
     * Get all error messages as flat array
     *
     * @return array
     */
    public function allErrors(): array
    {
        $this->validate();
        $errors = [];
        foreach ($this->errors as $fieldErrors) {
            $errors = array_merge($errors, $fieldErrors);
        }
        return $errors;
    }

    /**
     * Apply validation rule
     *
     * @param string $rule Rule name
     * @param string $field Field name
     * @param mixed $value Field value
     * @param array $params Rule parameters
     * @param string $label Field label
     * @return string|true Error message or true if valid
     */
    private function applyRule(string $rule, string $field, mixed $value, array $params, string $label): string|true
    {
        // Skip validation if field is empty and not required
        if (($value === null || $value === '') && $rule !== 'required' && !str_starts_with($rule, 'required_')) {
            return true;
        }

        // Get custom message
        $messageKey = "{$field}.{$rule}";
        $message = $this->messages[$messageKey] ?? null;

        $result = match ($rule) {
            'required' => $this->validateRequired($value),
            'email' => $this->validateEmail($value),
            'numeric' => $this->validateNumeric($value),
            'integer' => $this->validateInteger($value),
            'string' => $this->validateString($value),
            'array' => $this->validateArray($value),
            'boolean' => $this->validateBoolean($value),
            'url' => $this->validateUrl($value),
            'date' => $this->validateDate($value),
            'time' => $this->validateTime($value),
            'alpha' => $this->validateAlpha($value),
            'alpha_num' => $this->validateAlphaNum($value),
            'alpha_dash' => $this->validateAlphaDash($value),
            'min' => $this->validateMin($value, (int) $params[0]),
            'max' => $this->validateMax($value, (int) $params[0]),
            'min_length' => $this->validateMinLength($value, (int) $params[0]),
            'max_length' => $this->validateMaxLength($value, (int) $params[0]),
            'length' => $this->validateLength($value, (int) $params[0]),
            'between' => $this->validateBetween($value, (int) $params[0], (int) $params[1]),
            'in' => $this->validateIn($value, $params),
            'not_in' => $this->validateNotIn($value, $params),
            'regex' => $this->validateRegex($value, $params[0]),
            'same' => $this->validateSame($value, $params[0]),
            'different' => $this->validateDifferent($value, $params[0]),
            'gt' => $this->validateGt($value, (int|float) $params[0]),
            'lt' => $this->validateLt($value, (int|float) $params[0]),
            'gte' => $this->validateGte($value, (int|float) $params[0]),
            'lte' => $this->validateLte($value, (int|float) $params[0]),
            'file' => $this->validateFile($field),
            'image' => $this->validateImage($field),
            'mimes' => $this->validateMimes($field, $params),
            'exists' => $this->validateExists($value, $params),
            'unique' => $this->validateUnique($value, $params, $field),
            'ip' => $this->validateIp($value),
            'ipv4' => $this->validateIpv4($value),
            'ipv6' => $this->validateIpv6($value),
            'timezone' => $this->validateTimezone($value),
            'uuid' => $this->validateUuid($value),
            default => $this->callCustomValidator($rule, $value, $params)
        };

        if ($result === true) {
            return true;
        }

        // Use custom message or generate default
        if ($message) {
            return $this->formatMessage($message, $field, $label, $value, $params);
        }

        return $this->formatMessage($this->getDefaultMessage($rule), $field, $label, $value, $params);
    }

    /**
     * Validate required field
     */
    private function validateRequired(mixed $value): bool
    {
        if (is_array($value)) {
            return !empty($value);
        }
        return $value !== null && $value !== '';
    }

    /**
     * Validate email format
     */
    private function validateEmail(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate numeric value
     */
    private function validateNumeric(mixed $value): bool
    {
        return is_numeric($value);
    }

    /**
     * Validate integer value
     */
    private function validateInteger(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * Validate string type
     */
    private function validateString(mixed $value): bool
    {
        return is_string($value);
    }

    /**
     * Validate array type
     */
    private function validateArray(mixed $value): bool
    {
        return is_array($value);
    }

    /**
     * Validate boolean value
     */
    private function validateBoolean(mixed $value): bool
    {
        return is_bool($value) || in_array($value, [0, 1, '0', '1', true, false], true);
    }

    /**
     * Validate URL format
     */
    private function validateUrl(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Validate date format
     */
    private function validateDate(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }
        $timestamp = strtotime($value);
        return $timestamp !== false;
    }

    /**
     * Validate time format (HH:MM:SS)
     */
    private function validateTime(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }
        return preg_match('/^([01]\d|2[0-3]):([0-5]\d):([0-5]\d)$/', $value) === 1;
    }

    /**
     * Validate alphabetic characters only
     */
    private function validateAlpha(mixed $value): bool
    {
        return is_string($value) && ctype_alpha($value);
    }

    /**
     * Validate alphanumeric characters only
     */
    private function validateAlphaNum(mixed $value): bool
    {
        return is_string($value) && ctype_alnum($value);
    }

    /**
     * Validate alphabetic, dash, and underscore characters only
     */
    private function validateAlphaDash(mixed $value): bool
    {
        return is_string($value) && preg_match('/^[a-zA-Z0-9_-]+$/', $value) === 1;
    }

    /**
     * Validate minimum value
     */
    private function validateMin(mixed $value, int|float $min): bool
    {
        if (!is_numeric($value)) {
            return false;
        }
        return $value >= $min;
    }

    /**
     * Validate maximum value
     */
    private function validateMax(mixed $value, int|float $max): bool
    {
        if (!is_numeric($value)) {
            return false;
        }
        return $value <= $max;
    }

    /**
     * Validate minimum string length
     */
    private function validateMinLength(mixed $value, int $min): bool
    {
        if (!is_string($value)) {
            return false;
        }
        return mb_strlen($value) >= $min;
    }

    /**
     * Validate maximum string length
     */
    private function validateMaxLength(mixed $value, int $max): bool
    {
        if (!is_string($value)) {
            return false;
        }
        return mb_strlen($value) <= $max;
    }

    /**
     * Validate exact string length
     */
    private function validateLength(mixed $value, int $length): bool
    {
        if (!is_string($value)) {
            return false;
        }
        return mb_strlen($value) === $length;
    }

    /**
     * Validate value between range
     */
    private function validateBetween(mixed $value, int|float $min, int|float $max): bool
    {
        if (!is_numeric($value)) {
            return false;
        }
        return $value >= $min && $value <= $max;
    }

    /**
     * Validate value in list
     */
    private function validateIn(mixed $value, array $params): bool
    {
        return in_array($value, $params, true);
    }

    /**
     * Validate value not in list
     */
    private function validateNotIn(mixed $value, array $params): bool
    {
        return !in_array($value, $params, true);
    }

    /**
     * Validate against regex pattern
     */
    private function validateRegex(mixed $value, string $pattern): bool
    {
        if (!is_string($value)) {
            return false;
        }
        return preg_match($pattern, $value) === 1;
    }

    /**
     * Validate field matches another field
     */
    private function validateSame(mixed $value, string $field): bool
    {
        return $value === ($this->data[$field] ?? null);
    }

    /**
     * Validate field differs from another field
     */
    private function validateDifferent(mixed $value, string $field): bool
    {
        return $value !== ($this->data[$field] ?? null);
    }

    /**
     * Validate value greater than
     */
    private function validateGt(mixed $value, int|float $compare): bool
    {
        if (!is_numeric($value)) {
            return false;
        }
        return $value > $compare;
    }

    /**
     * Validate value less than
     */
    private function validateLt(mixed $value, int|float $compare): bool
    {
        if (!is_numeric($value)) {
            return false;
        }
        return $value < $compare;
    }

    /**
     * Validate value greater than or equal
     */
    private function validateGte(mixed $value, int|float $compare): bool
    {
        if (!is_numeric($value)) {
            return false;
        }
        return $value >= $compare;
    }

    /**
     * Validate value less than or equal
     */
    private function validateLte(mixed $value, int|float $compare): bool
    {
        if (!is_numeric($value)) {
            return false;
        }
        return $value <= $compare;
    }

    /**
     * Validate file upload
     */
    private function validateFile(string $field): bool
    {
        return isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK;
    }

    /**
     * Validate image upload
     */
    private function validateImage(string $field): bool
    {
        if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        return in_array($_FILES[$field]['type'], $allowedTypes, true);
    }

    /**
     * Validate file MIME types
     */
    private function validateMimes(string $field, array $types): bool
    {
        if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        
        $fileType = mime_content_type($_FILES[$field]['tmp_name']);
        return in_array($fileType, $types, true);
    }

    /**
     * Validate value exists in database table
     */
    private function validateExists(mixed $value, array $params): bool
    {
        if (count($params) < 2) {
            return false;
        }
        
        [$table, $column] = $params;
        $db = new Database();
        $result = $db->table($table)->where($column, $value)->first();
        
        return $result !== null;
    }

    /**
     * Validate value is unique in database table
     */
    private function validateUnique(mixed $value, array $params, string $field): bool
    {
        if (count($params) < 2) {
            return false;
        }
        
        [$table, $column] = $params;
        $excludeId = $params[2] ?? null;
        
        $db = new Database();
        $query = $db->table($table)->where($column, $value);
        
        if ($excludeId !== null) {
            $query->where('id !=', (int) $excludeId);
        }
        
        $result = $query->first();
        
        return $result === null;
    }

    /**
     * Validate IP address
     */
    private function validateIp(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Validate IPv4 address
     */
    private function validateIpv4(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    /**
     * Validate IPv6 address
     */
    private function validateIpv6(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    /**
     * Validate timezone
     */
    private function validateTimezone(mixed $value): bool
    {
        return in_array($value, timezone_identifiers_list(), true);
    }

    /**
     * Validate UUID format
     */
    private function validateUuid(mixed $value): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value) === 1;
    }

    /**
     * Call custom validator
     */
    private function callCustomValidator(string $rule, mixed $value, array $params): string|true
    {
        if (isset(self::$validators[$rule])) {
            $callback = self::$validators[$rule];
            $result = call_user_func($callback, $value, $params);
            return $result === true ? true : (string) $result;
        }
        
        // Unknown rule, pass validation
        return true;
    }

    /**
     * Get default error message for rule
     */
    private function getDefaultMessage(string $rule): string
    {
        $messages = [
            'required' => 'The :field field is required.',
            'email' => 'The :field must be a valid email address.',
            'numeric' => 'The :field must be a number.',
            'integer' => 'The :field must be an integer.',
            'string' => 'The :field must be a string.',
            'array' => 'The :field must be an array.',
            'boolean' => 'The :field must be a boolean value.',
            'url' => 'The :field must be a valid URL.',
            'date' => 'The :field must be a valid date.',
            'time' => 'The :field must be a valid time (HH:MM:SS).',
            'alpha' => 'The :field may only contain letters.',
            'alpha_num' => 'The :field may only contain letters and numbers.',
            'alpha_dash' => 'The :field may only contain letters, numbers, dashes and underscores.',
            'min' => 'The :field must be at least :param.',
            'max' => 'The :field must not be greater than :param.',
            'min_length' => 'The :field must be at least :param characters.',
            'max_length' => 'The :field must not be greater than :param characters.',
            'length' => 'The :field must be exactly :param characters.',
            'between' => 'The :field must be between :param0 and :param1.',
            'in' => 'The :field field is invalid.',
            'not_in' => 'The :field field is invalid.',
            'regex' => 'The :field format is invalid.',
            'same' => 'The :field field must match :param.',
            'different' => 'The :field field must be different from :param.',
            'gt' => 'The :field must be greater than :param.',
            'lt' => 'The :field must be less than :param.',
            'gte' => 'The :field must be greater than or equal to :param.',
            'lte' => 'The :field must be less than or equal to :param.',
            'file' => 'The :field must be a valid file.',
            'image' => 'The :field must be a valid image.',
            'mimes' => 'The :field must be a file of type: :param.',
            'exists' => 'The selected :field is invalid.',
            'unique' => 'The :field has already been taken.',
            'ip' => 'The :field must be a valid IP address.',
            'ipv4' => 'The :field must be a valid IPv4 address.',
            'ipv6' => 'The :field must be a valid IPv6 address.',
            'timezone' => 'The :field must be a valid timezone.',
            'uuid' => 'The :field must be a valid UUID.',
        ];

        return $messages[$rule] ?? 'The :field field is invalid.';
    }

    /**
     * Format error message with placeholders
     */
    private function formatMessage(string $message, string $field, string $label, mixed $value, array $params): string
    {
        $replacements = [
            ':field' => $label,
            ':value' => (string) $value,
            ':param' => $params[0] ?? '',
            ':param0' => $params[0] ?? '',
            ':param1' => $params[1] ?? '',
            ':param2' => $params[2] ?? '',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $message);
    }
}
