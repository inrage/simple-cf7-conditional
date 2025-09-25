<?php

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Conditions handling for Simple Conditional Fields for CF7
 */
class SimpleCF7Conditional_Conditions
{
    /**
     * Available operators
     */
    const OPERATORS = [
        'equals' => '=',
        'not_equals' => '≠',
        'contains' => 'contains',
        'not_contains' => 'does not contain',
        'is_empty' => 'is empty',
        'not_empty' => 'is not empty',
        'greater_than' => '>',
        'less_than' => '<'
    ];

    /**
     * Parse conditions from text format
     */
    public static function parse_text_conditions($text)
    {
        $conditions = [];
        $lines = explode("\n", trim($text));

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Parse format: show [field1] if [field2] equals "value"
            $pattern = '/show\s*\[([^\]]+)\]\s*if\s*\[([^\]]+)\]\s*(\w+(?:\s+\w+)*)\s*["\']([^"\']*)["\']?/i';

            if (preg_match($pattern, $line, $matches)) {
                $conditions[] = [
                    'show_field' => trim($matches[1]),
                    'if_field' => trim($matches[2]),
                    'operator' => trim($matches[3]),
                    'if_value' => trim($matches[4])
                ];
            }
        }

        return $conditions;
    }

    /**
     * Validate condition
     */
    public static function validate_condition($condition)
    {
        return !empty($condition['show_field']) &&
               !empty($condition['if_field']) &&
               !empty($condition['operator']) &&
               array_key_exists($condition['operator'], self::OPERATORS);
    }

    /**
     * Check if condition is met
     */
    public static function is_condition_met($field_value, $operator, $condition_value)
    {
        switch ($operator) {
            case 'equals':
                return $field_value === $condition_value;

            case 'not_equals':
                return $field_value !== $condition_value;

            case 'contains':
                return strpos($field_value, $condition_value) !== false;

            case 'not_contains':
                return strpos($field_value, $condition_value) === false;

            case 'is_empty':
                return empty($field_value);

            case 'not_empty':
                return !empty($field_value);

            case 'greater_than':
                return is_numeric($field_value) && is_numeric($condition_value) &&
                       floatval($field_value) > floatval($condition_value);

            case 'less_than':
                return is_numeric($field_value) && is_numeric($condition_value) &&
                       floatval($field_value) < floatval($condition_value);

            default:
                return false;
        }
    }

    /**
     * Get available operators for select
     */
    public static function get_operators_for_select()
    {
        $options = [];
        foreach (self::OPERATORS as $key => $label) {
            $options[] = [
                'value' => $key,
                'label' => $label
            ];
        }
        return $options;
    }
}