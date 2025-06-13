<?php

namespace Mariojgt\Builder\Enums;

/**
 * Advanced Styling Operators Enum
 *
 * This enum contains all available operators for conditional styling
 * in both field-level and row-level styling configurations.
 */
enum StylingOperator: string
{
    // Existence checks
    case EXISTS = 'exists';
    case NOT_EXISTS = 'not_exists';

    // Equality checks
    case EQUALS = 'equals';
    case NOT_EQUALS = 'not_equals';

    // Numerical comparisons
    case GREATER_THAN = 'greater_than';
    case GREATER_THAN_EQUAL = 'greater_than_equal';
    case LESS_THAN = 'less_than';
    case LESS_THAN_EQUAL = 'less_than_equal';
    case BETWEEN = 'between';

    // String operations
    case CONTAINS = 'contains';
    case NOT_CONTAINS = 'not_contains';
    case STARTS_WITH = 'starts_with';
    case ENDS_WITH = 'ends_with';

    // Array operations
    case IN_ARRAY = 'in_array';
    case NOT_IN_ARRAY = 'not_in_array';

    // Type checks
    case IS_NUMERIC = 'is_numeric';
    case IS_NOT_NUMERIC = 'is_not_numeric';
    case IS_EMPTY_ARRAY = 'is_empty_array';
    case HAS_ITEMS = 'has_items';

    // Length checks
    case LENGTH_EQUALS = 'length_equals';
    case LENGTH_GREATER_THAN = 'length_greater_than';
    case LENGTH_LESS_THAN = 'length_less_than';

    // Date operations
    case DATE_EQUALS = 'date_equals';
    case DATE_BEFORE = 'date_before';
    case DATE_AFTER = 'date_after';
    case DATE_BETWEEN = 'date_between';
}
