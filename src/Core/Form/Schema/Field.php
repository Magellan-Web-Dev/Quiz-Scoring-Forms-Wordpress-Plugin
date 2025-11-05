<?php

declare(strict_types=1);

namespace QuizScoringForms\Core\Form\Schema;

/** 
 * Prevent direct access from sources other than the Wordpress environment
 */
if (!defined('ABSPATH')) exit;

/**
 * Class Field
 *
 * Represents a form field with associated value and score.
 *
 * Responsibilities:
 * - Validates a value against the field's constraints.
 * - Casts a value to the expected data type.
 * - Sanitizes a value based on the expected data type.
 * - Stores the value associated with the form field in the question.
 * - Stores the score associated with the form field in the question.
 *
 * This class is a simple data container for form fields and their associated values and scores.
 */
final class Field
{
    /**
     * The value associated with the form field in the question.
     *
     * @var mixed
     */
    private mixed $value = null;

    /**
     * The score associated with the form field in the question.
     *
     * @var int|string|null
     */
    private int|string|null $score = null;

    /**
     * Constructor
     * 
     * @param int|string $id The ID of the form field.
     * @param string $section The section of the form field.
     * @param int $order The order of the form field.
     * @param string $htmlType The HTML type of the form field (UI use).
     * @param string $label The label of the form field.
     * @param string $placeholder The placeholder of the form field.
     * @param string $dataType The expected data type ("string", "int", "float", "number", "bool", "email", "phone", "name").
     * @param int|null $minLength Minimum allowed length or numeric value.
     * @param int|null $maxLength Maximum allowed length or numeric value.
     * @param string $message The error message of the form field.
     * @param bool $required Whether the form field is required.
     * @param bool $isQuestion Whether the form field is a question.
     */
    public function __construct(
        public readonly string|int $id,
        public readonly string $section,
        public readonly int $order,
        public readonly string $htmlType,
        public readonly string $label,
        public readonly string $placeholder,
        public readonly string $dataType,
        public readonly ?int $minLength,
        public readonly ?int $maxLength,
        public readonly string $message,
        public readonly bool $required,
        public readonly bool $isQuestion
    ) {}

    /**
     * Validate a value against the field's constraints.
     *
     * Performs required checks, type casting, min/max constraints, 
     * and data-type specific validation (e.g., email, phone, name).
     * If validated, the value will be stored.
     *
     * @param mixed $value The value to validate.
     * @return bool True if valid, false if not.
     */
    public function validate(mixed $value): bool
    {
        // Required check
        if ($this->required && ($value === null || $value === '')) {
            return false;
        }

        // Type + casting check
        $castValue = $this->castValue($value);
        if ($castValue === null && $value !== null) {
            return false;
        }

        // Min/max checks
        if (!$this->validateConstraints($castValue)) {
            return false;
        }

        // Data-type specific validation (email, phone, name)
        if (!$this->validateDataType($castValue)) {
            return false;
        }

        $this->value = $castValue;

        return true;
    }

    /**
     * Try to cast string numbers into int/float depending on $dataType.
     * 
     * @param mixed $value The value to cast.
     * @return mixed The casted value or null if invalid.
     */
    private function castValue(mixed $value): mixed
    {
        switch ($this->dataType) {
            case 'string':
            case 'email':
            case 'phone':
            case 'name':
                return is_string($value) ? $value : null;

            case 'int':
                if (is_int($value)) {
                    return $value;
                }
                if (is_string($value) && ctype_digit($value)) {
                    return (int) $value;
                }
                return null;

            case 'float':
                if (is_float($value)) {
                    return $value;
                }
                if (is_int($value)) {
                    return (float) $value;
                }
                if (is_string($value) && is_numeric($value)) {
                    return (float) $value;
                }
                return null;

            case 'number': // accepts int OR float
                if (is_int($value) || is_float($value)) {
                    return $value;
                }
                if (is_string($value) && is_numeric($value)) {
                    return strpos($value, '.') !== false ? (float) $value : (int) $value;
                }
                return null;

            case 'bool':
                if (is_bool($value)) {
                    return $value;
                }
                if (is_string($value)) {
                    $lower = strtolower($value);
                    if (in_array($lower, ['true', '1'], true)) return true;
                    if (in_array($lower, ['false', '0'], true)) return false;
                }
                if (is_int($value)) {
                    return $value === 1;
                }
                return null;

            default:
                return null;
        }
    }

    /**
     * Validate constraints (min/max length or numeric bounds).
     * 
     * @param mixed $value The value to check.
     * @return bool True if valid, false if not.
     */
    private function validateConstraints(mixed $value): bool
    {
        if ($value === null) {
            return true; // nothing to check
        }

        // String length constraints
        if (is_string($value)) {
            $len = mb_strlen($value);

            if ($this->minLength !== null && $len < $this->minLength) {
                return false;
            }

            if ($this->maxLength !== null && $len > $this->maxLength) {
                return false;
            }
        }

        // Numeric constraints
        if (is_int($value) || is_float($value)) {
            if ($this->minLength !== null && $value < $this->minLength) {
                return false;
            }

            if ($this->maxLength !== null && $value > $this->maxLength) {
                return false;
            }
        }

        return true;
    }

    /**
     * Perform data-type specific validation rules.  Only English characters are allowed.
     * 
     * - Email: must be a valid email format.
     * - Phone: must contain only digits, spaces, dashes, parentheses, or a leading plus sign.
     * - Name: must contain only letters, spaces, commas, and periods.
     *
     * @param mixed $value The value to check.
     * @return bool True if valid, false if not.
     */
    private function validateDataType(mixed $value): bool
    {
        if ($value === null) {
            return true; // nothing to check
        }

        $stringVal = (string) $value;

        // 🚫 Reject if contains non-ASCII (non-English) characters
        if (preg_match('/[^\x00-\x7F]/', $stringVal)) {
            return false;
        }

        switch ($this->dataType) {
            case 'email':
                return (bool) filter_var($stringVal, FILTER_VALIDATE_EMAIL);

            case 'phone':
                return (bool) preg_match('/^\+?[0-9\s\-\(\)]+$/', $stringVal);

            case 'name':
                return (bool) preg_match('/^[a-zA-Z\s\.,]+$/', $stringVal);

            default:
                return true; // no special validation
        }
    }

    /**
     * Set the value of the form field. Only allows being set once.
     * 
     * @param mixed $value The value to assign.
     * @return mixed The stored value.
     */
    public function setValue(mixed $value): mixed
    {
        if ($this->value !== null) {
            return $this->value;
        }
        $this->value = $this->castValue($value);
        return $this->value;
    }

    /**
     * Set the score of the form field. Only allows being set once.
     * 
     * @param mixed $score The score to assign.
     * @return mixed The stored score.
     */
    public function setScore(mixed $score): mixed
    {
        if ($this->score !== null) {
            throw new \LogicException("Score value for form field {$this->id} has already been set and cannot be changed.");
        }
        $this->score = $score;
        return $this->score;
    }

    /**
     * Get the value of the form field.
     * 
     * @return mixed The field value.
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Get the score of the form field.
     * 
     * @return mixed The field score.
     */
    public function getScore(): mixed
    {
        return $this->score;
    }
}
