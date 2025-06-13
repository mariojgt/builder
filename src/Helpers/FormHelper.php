<?php

namespace Mariojgt\Builder\Helpers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Mariojgt\Builder\Enums\FieldTypes;
use Mariojgt\Builder\Enums\StylingOperator;
use Illuminate\Contracts\Validation\ValidationRule;

class FormHelper
{
    /**
     * Store the form columns/fields
     *
     * @var array<int, array>
     */
    private array $columns = [];

    /**
     * Current active tab
     */
    private ?string $currentTab = null;

    /**
     * Store the form configuration
     *
     * @var array<string, mixed>
     */
    private array $config = [];

    /**
     * Default sort key
     * @var string|null
     */
    private ?string $defaultIdKey = null;

    /**
     * Row-level styling configuration
     * @var array<string, mixed>
     */
    private array $rowStyling = [];

    /**
     * Set the current tab for subsequent fields
     */
    public function tab(string $tabName): self
    {
        $this->currentTab = $tabName;
        return $this;
    }

    /**
     * ✨ SIMPLE: Add link to the last added field
     *
     * @param string $url URL pattern with {placeholders} OR field key to use as URL
     * @param bool $newTab Open in new tab
     * @param string|null $style Link style: 'default', 'button', 'button-primary', 'button-secondary', 'badge', 'underline', 'none'
     * @return self
     */
    public function withLink(string $url, bool $newTab = false, ?string $style = null): self
    {
        $lastIndex = count($this->columns) - 1;
        if ($lastIndex < 0) {
            throw new \InvalidArgumentException('No fields exist to add link to');
        }

        $this->columns[$lastIndex]['link'] = [
            'url' => $url,
            'target' => $newTab ? '_blank' : '_self',
            'style' => $style ?? 'default'
        ];

        return $this;
    }

    /**
     * ✨ NEW: Use another field's value as the link URL
     *
     * @param string $fieldKey The field key to use as URL (e.g., 'reportedData.link')
     * @param bool $newTab Open in new tab
     * @param string|null $style Link style: 'default', 'button', 'button-primary', 'button-secondary', 'badge', 'underline', 'none'
     * @return self
     */
    public function withLinkFromField(string $fieldKey, bool $newTab = false, ?string $style = null): self
    {
        $lastIndex = count($this->columns) - 1;
        if ($lastIndex < 0) {
            throw new \InvalidArgumentException('No fields exist to add link to');
        }

        $this->columns[$lastIndex]['link'] = [
            'url_field' => $fieldKey, // Mark this as a field reference
            'target' => $newTab ? '_blank' : '_self',
            'style' => $style ?? 'default'
        ];

        return $this;
    }

    /**
     * ✨ SIMPLE: Add external link (opens in new tab by default)
     *
     * @param string $url
     * @param string|null $style Link style
     * @return self
     */
    public function withExternalLink(string $url, ?string $style = null): self
    {
        return $this->withLink($url, true, $style);
    }

    /**
     * ✨ SIMPLE: Add edit link
     *
     * @param string $baseUrl
     * @param string|null $style Link style
     * @return self
     */
    public function withEditLink(string $baseUrl = '/edit', ?string $style = null): self
    {
        return $this->withLink($baseUrl . '/{id}', false, $style);
    }

    /**
     * Encrypt a validator class name
     *
     * @throws \RuntimeException When encryption fails
     */
    private function encryptValidatorClass(string $validatorClass): string
    {
        try {
            return Crypt::encrypt($validatorClass);
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to encrypt validator class: {$e->getMessage()}");
        }
    }

    /**
     * Process a validation rule
     *
     * @param mixed $rule The rule to process
     * @param array<string, mixed> $params Additional parameters for the rule
     * @return array{type: string, class?: string, value?: mixed, params?: array}
     */
    private function processRule(mixed $rule, array $params = []): array
    {
        // Handle ValidationRule objects
        if (is_object($rule)) {
            $class = get_class($rule);
            if ($rule instanceof ValidationRule) {
                return [
                    'type' => 'validator',
                    'class' => $this->encryptValidatorClass($class),
                    'params' => $params
                ];
            }
        }

        // Handle ValidationRule class names
        if (is_string($rule) && class_exists($rule)) {
            $reflection = new \ReflectionClass($rule);
            if ($reflection->implementsInterface(ValidationRule::class)) {
                return [
                    'type' => 'validator',
                    'class' => $this->encryptValidatorClass($rule),
                    'params' => $params
                ];
            }
        }

        return [
            'type' => 'rule',
            'value' => $rule
        ];
    }

    /**
     * Add validation rules and messages to the last added field
     */
    public function withRules(mixed $rules, array $messages = []): self
    {
        $lastIndex = count($this->columns) - 1;
        if ($lastIndex < 0) {
            throw new \InvalidArgumentException('No fields exist to add rules to');
        }

        $processedRules = [];
        $formattedMessages = [];
        $fieldKey = $this->columns[$lastIndex]['key'];

        if (is_array($rules)) {
            foreach ($rules as $rule) {
                if (is_array($rule) && isset($rule[0])) {
                    $processedRules[] = $this->processRule($rule[0], $rule[1] ?? []);
                } else {
                    $processedRules[] = $this->processRule($rule);
                }
            }
        } elseif (is_string($rules)) {
            foreach (explode('|', $rules) as $rule) {
                $processedRules[] = $this->processRule(trim($rule));
            }
        } elseif ($rules instanceof ValidationRule) {
            $processedRules[] = $this->processRule($rules);
        } else {
            throw new \InvalidArgumentException('Invalid rules format provided');
        }

        // Format messages with field key
        if (!empty($messages)) {
            foreach ($messages as $rule => $message) {
                $formattedMessages["$fieldKey.$rule"] = $message;
            }
        }

        $this->columns[$lastIndex]['rules'] = $processedRules;
        if (!empty($formattedMessages)) {
            $this->columns[$lastIndex]['messages'] = $formattedMessages;
        }

        return $this;
    }

    /**
     * Add conditional styling to the last added field (simple value => class mapping)
     *
     * @param array<string, string> $styleConditions Array of value => class mappings
     * @param string $defaultStyle Default style when no condition matches
     * @return self
     */
    public function withConditionalStyling(array $styleConditions, string $defaultStyle = ''): self
    {
        $lastIndex = count($this->columns) - 1;
        if ($lastIndex < 0) {
            throw new \InvalidArgumentException('No fields exist to add conditional styling to');
        }

        $this->columns[$lastIndex]['conditionalStyling'] = [
            'conditions' => $styleConditions,
            'default' => $defaultStyle
        ];

        return $this;
    }

    /**
     * Create an advanced styling condition array
     *
     * @param StylingOperator $operator The comparison operator
     * @param string $classes The CSS classes to apply
     * @param mixed $value The value to compare against (optional for some operators)
     * @param array<string, mixed> $params Additional parameters (min, max for between, array for in_array, etc.)
     * @return array{operator: string, value?: mixed, classes: string, min?: mixed, max?: mixed, array?: array, date_format?: string}
     */
    public static function createCondition(
        StylingOperator $operator,
        string $classes,
        mixed $value = null,
        array $params = []
    ): array {
        $condition = [
            'operator' => $operator->value,
            'classes' => $classes
        ];

        // Add value for operators that need it
        if (!in_array($operator, [
            StylingOperator::EXISTS,
            StylingOperator::NOT_EXISTS,
            StylingOperator::IS_NUMERIC,
            StylingOperator::IS_NOT_NUMERIC,
            StylingOperator::IS_EMPTY_ARRAY,
            StylingOperator::HAS_ITEMS
        ])) {
            $condition['value'] = $value;
        }

        // Add additional parameters
        foreach ($params as $key => $paramValue) {
            $condition[$key] = $paramValue;
        }

        return $condition;
    }

    /**
     * Create a row styling condition with field key
     *
     * @param string $fieldKey The field key to check
     * @param StylingOperator $operator The comparison operator
     * @param string $classes The CSS classes to apply
     * @param mixed $value The value to compare against (optional for some operators)
     * @param array<string, mixed> $params Additional parameters
     * @return array{field: string, operator: string, value?: mixed, classes: string, min?: mixed, max?: mixed, array?: array, date_format?: string}
     */
    public static function createRowCondition(
        string $fieldKey,
        StylingOperator $operator,
        string $classes,
        mixed $value = null,
        array $params = []
    ): array {
        $condition = self::createCondition($operator, $classes, $value, $params);
        $condition['field'] = $fieldKey;

        return $condition;
    }

    /**
     * Add advanced conditional styling with operators
     *
     * @param array<array{operator: string, value?: mixed, classes: string, min?: mixed, max?: mixed, array?: array}> $conditions Array of condition arrays
     * @param string $defaultStyle Default style when no condition matches
     * @return self
     */
    public function withAdvancedStyling(array $conditions, string $defaultStyle = ''): self
    {
        $lastIndex = count($this->columns) - 1;
        if ($lastIndex < 0) {
            throw new \InvalidArgumentException('No fields exist to add advanced styling to');
        }

        $this->columns[$lastIndex]['advancedStyling'] = [
            'conditions' => $conditions,
            'default' => $defaultStyle
        ];

        return $this;
    }

    /**
     * Add row-level conditional styling (simple value => class mapping)
     *
     * @param string $fieldKey The field key to check for styling conditions
     * @param array<string, string> $styleConditions Array of value => class mappings
     * @param string $defaultStyle Default style when no condition matches
     * @return self
     */
    public function withRowConditionalStyling(string $fieldKey, array $styleConditions, string $defaultStyle = ''): self
    {
        $this->rowStyling['conditionalStyling'] = [
            'field' => $fieldKey,
            'conditions' => $styleConditions,
            'default' => $defaultStyle
        ];

        return $this;
    }

    /**
     * Add advanced row-level styling with operators
     *
     * @param array<array{field: string, operator: string, value?: mixed, classes: string, min?: mixed, max?: mixed, array?: array}> $conditions Array of condition arrays with field keys
     * @param string $defaultStyle Default style when no condition matches
     * @return self
     */
    public function withAdvancedRowStyling(array $conditions, string $defaultStyle = ''): self
    {
        $this->rowStyling['advancedStyling'] = [
            'conditions' => $conditions,
            'default' => $defaultStyle
        ];

        return $this;
    }

    /**
     * Helper method for row existence checks
     */
    public function withRowExistenceStyling(
        string $fieldKey,
        string $existsClasses = 'bg-green-50 border-green-200',
        string $notExistsClasses = 'bg-red-50 border-red-200'
    ): self {
        return $this->withAdvancedRowStyling([
            self::createRowCondition($fieldKey, StylingOperator::EXISTS, $existsClasses),
            self::createRowCondition($fieldKey, StylingOperator::NOT_EXISTS, $notExistsClasses)
        ]);
    }

    /**
     * Helper method for row status styling
     */
    public function withRowStatusStyling(
        string $statusField = 'status',
        array $customStyles = []
    ): self {
        $defaultStatusStyles = [
            // Critical/Error States
            'duplicate' => 'bg-red-50 border-red-200 hover:bg-red-100',
            'error' => 'bg-red-50 border-red-200 hover:bg-red-100',
            'failed' => 'bg-red-50 border-red-200 hover:bg-red-100',
            'rejected' => 'bg-red-50 border-red-200 hover:bg-red-100',
            'cancelled' => 'bg-red-50 border-red-200 hover:bg-red-100',

            // Success States
            'finished' => 'bg-green-50 border-green-200 hover:bg-green-100',
            'completed' => 'bg-green-50 border-green-200 hover:bg-green-100',
            'active' => 'bg-green-50 border-green-200 hover:bg-green-100',
            'validated' => 'bg-green-50 border-green-200 hover:bg-green-100',
            'patched' => 'bg-green-50 border-green-200 hover:bg-green-100',

            // Warning States
            'unvalidated' => 'bg-yellow-50 border-yellow-200 hover:bg-yellow-100',
            'pending' => 'bg-yellow-50 border-yellow-200 hover:bg-yellow-100',
            'in_progress' => 'bg-yellow-50 border-yellow-200 hover:bg-yellow-100',
            'under_review' => 'bg-blue-50 border-blue-200 hover:bg-blue-100',

            // Neutral States
            'draft' => 'bg-gray-50 border-gray-200 hover:bg-gray-100',
            'inactive' => 'bg-gray-50 border-gray-200 hover:bg-gray-100',
        ];

        $mergedStyles = array_merge($defaultStatusStyles, $customStyles);

        return $this->withRowConditionalStyling($statusField, $mergedStyles, 'bg-white border-gray-200 hover:bg-gray-50');
    }

    /**
     * Helper method for CVSS-based row styling
     */
    public function withRowCVSSStyling(string $cvssField = 'cvss_score'): self
    {
        return $this->withAdvancedRowStyling([
            self::createRowCondition($cvssField, StylingOperator::GREATER_THAN_EQUAL, 'bg-red-50 border-red-300 hover:bg-red-100', 9.0),
            self::createRowCondition($cvssField, StylingOperator::GREATER_THAN_EQUAL, 'bg-orange-50 border-orange-300 hover:bg-orange-100', 7.0),
            self::createRowCondition($cvssField, StylingOperator::GREATER_THAN_EQUAL, 'bg-yellow-50 border-yellow-300 hover:bg-yellow-100', 4.0),
            self::createRowCondition($cvssField, StylingOperator::GREATER_THAN, 'bg-green-50 border-green-300 hover:bg-green-100', 0),
        ], 'bg-white border-gray-200 hover:bg-gray-50');
    }

    /**
     * Helper method for priority-based row styling
     */
    public function withRowPriorityStyling(string $priorityField = 'priority'): self
    {
        return $this->withAdvancedRowStyling([
            self::createRowCondition($priorityField, StylingOperator::EQUALS, 'bg-red-50 border-red-300 hover:bg-red-100', 'critical'),
            self::createRowCondition($priorityField, StylingOperator::EQUALS, 'bg-red-50 border-red-300 hover:bg-red-100', 'high'),
            self::createRowCondition($priorityField, StylingOperator::EQUALS, 'bg-red-50 border-red-300 hover:bg-red-100', '1'),
            self::createRowCondition($priorityField, StylingOperator::EQUALS, 'bg-orange-50 border-orange-300 hover:bg-orange-100', 'medium'),
            self::createRowCondition($priorityField, StylingOperator::EQUALS, 'bg-orange-50 border-orange-300 hover:bg-orange-100', '2'),
            self::createRowCondition($priorityField, StylingOperator::EQUALS, 'bg-green-50 border-green-300 hover:bg-green-100', 'low'),
            self::createRowCondition($priorityField, StylingOperator::EQUALS, 'bg-green-50 border-green-300 hover:bg-green-100', '3'),
        ], 'bg-white border-gray-200 hover:bg-gray-50');
    }

    /**
     * Helper method for date-based row styling
     */
    public function withRowDateStyling(
        string $dateField = 'created_at',
        int $oldDays = 30,
        int $recentDays = 7
    ): self {
        $now = new \DateTime();
        $recent = clone $now;
        $recent->modify("-{$recentDays} days");
        $old = clone $now;
        $old->modify("-{$oldDays} days");

        return $this->withAdvancedRowStyling([
            self::createRowCondition($dateField, StylingOperator::DATE_AFTER, 'bg-green-50 border-green-200 hover:bg-green-100', $recent->format('Y-m-d'), ['date_format' => 'Y-m-d']),
            self::createRowCondition($dateField, StylingOperator::DATE_BEFORE, 'bg-gray-50 border-gray-300 hover:bg-gray-100', $old->format('Y-m-d'), ['date_format' => 'Y-m-d']),
        ], 'bg-white border-gray-200 hover:bg-gray-50');
    }

    /**
     * Helper method for existence checks
     */
    public function withExistenceStyling(
        string $existsClasses = 'bg-green-100 text-green-800 border border-green-300',
        string $notExistsClasses = 'bg-red-100 text-red-800 border border-red-300'
    ): self {
        return $this->withAdvancedStyling([
            self::createCondition(StylingOperator::EXISTS, $existsClasses),
            self::createCondition(StylingOperator::NOT_EXISTS, $notExistsClasses)
        ]);
    }

    /**
     * Helper method for numeric range styling
     *
     * @param array<array{min?: float, max?: float, classes: string}> $ranges
     * @param string $defaultStyle
     * @return self
     */
    public function withNumericRangeStyling(array $ranges, string $defaultStyle = ''): self
    {
        $conditions = [];
        foreach ($ranges as $range) {
            if (isset($range['min']) && isset($range['max'])) {
                $conditions[] = self::createCondition(
                    StylingOperator::BETWEEN,
                    $range['classes'],
                    null,
                    ['min' => $range['min'], 'max' => $range['max']]
                );
            } elseif (isset($range['min'])) {
                $conditions[] = self::createCondition(
                    StylingOperator::GREATER_THAN_EQUAL,
                    $range['classes'],
                    $range['min']
                );
            } elseif (isset($range['max'])) {
                $conditions[] = self::createCondition(
                    StylingOperator::LESS_THAN_EQUAL,
                    $range['classes'],
                    $range['max']
                );
            }
        }

        return $this->withAdvancedStyling($conditions, $defaultStyle);
    }

    /**
     * Helper method for array-based styling
     *
     * @param array<mixed> $inArrayValues
     * @param string $inClasses
     * @param string $outClasses
     * @return self
     */
    public function withArrayStyling(array $inArrayValues, string $inClasses, string $outClasses = ''): self
    {
        return $this->withAdvancedStyling([
            self::createCondition(
                StylingOperator::IN_ARRAY,
                $inClasses,
                null,
                ['array' => $inArrayValues]
            ),
            self::createCondition(StylingOperator::NOT_IN_ARRAY, $outClasses)
        ]);
    }

    /**
     * Helper method for string pattern styling
     */
    public function withPatternStyling(
        ?string $contains = null,
        string $containsClasses = '',
        ?string $startsWith = null,
        string $startsWithClasses = '',
        ?string $endsWith = null,
        string $endsWithClasses = '',
        string $defaultStyle = ''
    ): self {
        $conditions = [];

        if ($contains) {
            $conditions[] = self::createCondition(StylingOperator::CONTAINS, $containsClasses, $contains);
        }
        if ($startsWith) {
            $conditions[] = self::createCondition(StylingOperator::STARTS_WITH, $startsWithClasses, $startsWith);
        }
        if ($endsWith) {
            $conditions[] = self::createCondition(StylingOperator::ENDS_WITH, $endsWithClasses, $endsWith);
        }

        return $this->withAdvancedStyling($conditions, $defaultStyle);
    }

    /**
     * Add status badge styling (common use case)
     *
     * @param array<string, string> $statusStyles Optional custom status styles
     * @return self
     */
    public function withStatusStyling(array $statusStyles = []): self
    {
        $defaultStatusStyles = [
            // Patch Status
            'unpatched' => 'bg-red-500 text-white border-red-600',
            'patched' => 'bg-green-500 text-white border-green-600',
            'partially_patched' => 'bg-yellow-500 text-black border-yellow-600',

            // General Status
            'active' => 'bg-green-500 text-white border-green-600',
            'inactive' => 'bg-gray-500 text-white border-gray-600',
            'pending' => 'bg-yellow-500 text-black border-yellow-600',
            'cancelled' => 'bg-red-500 text-white border-red-600',
            'completed' => 'bg-blue-500 text-white border-blue-600',
            'in_progress' => 'bg-orange-500 text-white border-orange-600',
            'draft' => 'bg-gray-300 text-black border-gray-400',
            'published' => 'bg-green-500 text-white border-green-600',
            'archived' => 'bg-gray-600 text-white border-gray-700',

            // Validation Status
            'validated' => 'bg-green-500 text-white border-green-600',
            'rejected' => 'bg-red-500 text-white border-red-600',
            'under_review' => 'bg-blue-500 text-white border-blue-600',

            // Contact Status
            'contacted' => 'bg-blue-500 text-white border-blue-600',
            'not_contacted' => 'bg-gray-500 text-white border-gray-600',
            'responded' => 'bg-green-500 text-white border-green-600',
            'no_response' => 'bg-yellow-500 text-black border-yellow-600',

            // Vulnerability specific
            'duplicate' => 'bg-red-500 text-white border-red-600 shadow-lg',
            'finished' => 'bg-green-500 text-white border-green-600',
            'unvalidated' => 'bg-yellow-500 text-black border-yellow-600',

            // Boolean values
            'true' => 'bg-green-500 text-white border-green-600',
            'false' => 'bg-red-500 text-white border-red-600',
            '1' => 'bg-green-500 text-white border-green-600',
            '0' => 'bg-red-500 text-white border-red-600',
        ];

        $mergedStyles = array_merge($defaultStatusStyles, $statusStyles);

        return $this->withConditionalStyling($mergedStyles, 'bg-gray-200 text-gray-800 border-gray-300');
    }

    /**
     * Add severity/priority styling
     *
     * @param array<string, string> $severityStyles Optional custom severity styles
     * @return self
     */
    public function withSeverityStyling(array $severityStyles = []): self
    {
        $defaultSeverityStyles = [
            'critical' => 'bg-red-600 text-white border-red-700 shadow-lg',
            'high' => 'bg-red-500 text-white border-red-600',
            'medium' => 'bg-yellow-500 text-black border-yellow-600',
            'low' => 'bg-green-500 text-white border-green-600',
            'info' => 'bg-blue-500 text-white border-blue-600',

            // Numeric priorities
            '1' => 'bg-red-600 text-white border-red-700',
            '2' => 'bg-red-500 text-white border-red-600',
            '3' => 'bg-yellow-500 text-black border-yellow-600',
            '4' => 'bg-green-500 text-white border-green-600',
            '5' => 'bg-blue-500 text-white border-blue-600',
        ];

        $mergedStyles = array_merge($defaultSeverityStyles, $severityStyles);

        return $this->withConditionalStyling($mergedStyles, 'bg-gray-200 text-gray-800 border-gray-300');
    }

    /**
     * Add CVSS score styling
     */
    public function withCVSSstyling(): self
    {
        return $this->withAdvancedStyling([
            self::createCondition(StylingOperator::GREATER_THAN_EQUAL, 'bg-red-600 text-white border-red-700 shadow-lg', 9.0),
            self::createCondition(StylingOperator::GREATER_THAN_EQUAL, 'bg-red-500 text-white border-red-600', 7.0),
            self::createCondition(StylingOperator::GREATER_THAN_EQUAL, 'bg-yellow-500 text-black border-yellow-600', 4.0),
            self::createCondition(StylingOperator::GREATER_THAN, 'bg-green-500 text-white border-green-600', 0),
            self::createCondition(StylingOperator::EQUALS, 'bg-gray-200 text-gray-800 border-gray-300', 0)
        ], 'bg-gray-200 text-gray-800 border-gray-300');
    }

    /**
     * Add percentage-based styling
     */
    public function withPercentageStyling(): self
    {
        return $this->withAdvancedStyling([
            self::createCondition(StylingOperator::GREATER_THAN_EQUAL, 'bg-green-600 text-white border-green-700', 90),
            self::createCondition(StylingOperator::GREATER_THAN_EQUAL, 'bg-green-500 text-white border-green-600', 70),
            self::createCondition(StylingOperator::GREATER_THAN_EQUAL, 'bg-yellow-500 text-black border-yellow-600', 50),
            self::createCondition(StylingOperator::GREATER_THAN_EQUAL, 'bg-orange-500 text-white border-orange-600', 30),
            self::createCondition(StylingOperator::LESS_THAN, 'bg-red-500 text-white border-red-600', 30)
        ], 'bg-gray-200 text-gray-800 border-gray-300');
    }

    /**
     * Enhanced addField method with styling support
     */
    public function addField(
        string $label,
        string $key,
        bool $sortable = true,
        bool $canCreate = true,
        bool $canEdit = true,
        ?string $type = null,
        array $options = [],
        bool $unique = false,
        bool $nullable = false,
        ?string $endpoint = null,
        array $columns = [],
        ?string $model = null,
        bool $singleSearch = false,
        ?string $displayKey = null,
        mixed $rules = null,
        array $messages = [],
        bool $filterable = false,
        array $filterOptions = [],
        array $conditionalStyling = [],
        string $defaultStyle = ''
    ): self {
        if (empty($key)) {
            throw new \InvalidArgumentException('Field key cannot be empty');
        }

        $type ??= FieldTypes::TEXT->value;

        $processedRules = [];
        if ($rules !== null) {
            if (is_object($rules) && $rules instanceof ValidationRule) {
                $processedRules[] = $this->processRule($rules);
            } elseif (is_array($rules)) {
                foreach ($rules as $rule) {
                    $processedRules[] = $this->processRule($rule);
                }
            } elseif (is_string($rules)) {
                foreach (explode('|', $rules) as $rule) {
                    $processedRules[] = [
                        'type' => 'rule',
                        'value' => trim($rule)
                    ];
                }
            }
        }

        $field = [
            'label' => $label,
            'key' => $key,
            'sortable' => $sortable,
            'canCreate' => $canCreate,
            'canEdit' => $canEdit,
            'unique' => $unique,
            'nullable' => $nullable,
            'endpoint' => $endpoint,
            'columns' => $columns,
            'model' => $model,
            'singleSearch' => $singleSearch,
            'displayKey' => $displayKey,
            'tab' => $this->currentTab,
            'type' => $type,
            'rules' => $processedRules,
            'messages' => $messages,
            'filterable' => $filterable,
            'filter_options' => $filterOptions
        ];

        if (!empty($options)) {
            $field['options'] = $options;
        }

        // Add conditional styling if provided
        if (!empty($conditionalStyling)) {
            $field['conditionalStyling'] = [
                'conditions' => $conditionalStyling,
                'default' => $defaultStyle
            ];
        }

        $this->columns[] = $field;
        return $this;
    }

    /**
     * Add ID field (commonly used)
     */
    public function addIdField(
        string $label = 'Id',
        string $key = 'id'
    ): self {
        $this->defaultIdKey = $key;
        return $this->addField(
            label: $label,
            key: $key,
            sortable: true,
            canCreate: false,
            canEdit: false
        );
    }

    /**
     * Get all form fields
     */
    public function getFields(): array
    {
        return $this->columns;
    }

    /**
     * Get row styling configuration
     */
    public function getRowStyling(): array
    {
        return $this->rowStyling;
    }

    /**
     * Clear all fields and configurations
     */
    public function clear(): self
    {
        $this->columns = [];
        $this->config = [];
        $this->rowStyling = [];
        return $this;
    }

    /**
     * Get fields that are creatable
     */
    public function getCreatableFields(): array
    {
        return array_filter($this->columns, fn($field) => $field['canCreate'] ?? false);
    }

    /**
     * Get fields that are editable
     */
    public function getEditableFields(): array
    {
        return array_filter($this->columns, fn($field) => $field['canEdit'] ?? false);
    }

    /**
     * Get fields that are sortable
     */
    public function getSortableFields(): array
    {
        return array_filter($this->columns, fn($field) => $field['sortable'] ?? false);
    }

    /**
     * Set the basic endpoints for the table
     */
    public function setEndpoints(
        string $listEndpoint,
        string $deleteEndpoint,
        string $createEndpoint,
        string $editEndpoint
    ): self {
        $this->config['endpoint'] = $listEndpoint;
        $this->config['endpointDelete'] = $deleteEndpoint;
        $this->config['endpointCreate'] = $createEndpoint;
        $this->config['endpointEdit'] = $editEndpoint;

        return $this;
    }

    /**
     * Set endpoints using route names
     */
    public function setEndpointsFromRoutes(string $prefix): self
    {
        return $this->setEndpoints(
            listEndpoint: route($prefix . '.api.generic.table'),
            deleteEndpoint: route($prefix . '.api.generic.table.delete'),
            createEndpoint: route($prefix . '.api.generic.table.create'),
            editEndpoint: route($prefix . '.api.generic.table.update')
        );
    }

    /**
     * Set the model for the table
     */
    public function setModel(string $modelClass): self
    {
        if (!class_exists($modelClass)) {
            throw new \InvalidArgumentException("Model class {$modelClass} does not exist");
        }

        $this->config['model'] = encrypt($modelClass);
        return $this;
    }

    /**
     * Convert fields to collection
     */
    public function toCollection(): Collection
    {
        return collect($this->columns);
    }

    /**
     * Set permissions for CRUD operations
     */
    public function setPermissions(
        string $guard,
        string $type = 'permission',
        array $permissions = [
            'store' => 'create-permission',
            'update' => 'edit-permission',
            'delete' => 'delete-permission',
            'index' => 'read-permission',
        ]
    ): self {
        $this->config['permission'] = encrypt([
            'guard' => $guard,
            'type' => $type,
            'key' => $permissions,
        ]);

        return $this;
    }

    /**
     * Set custom edit route
     */
    public function setCustomEditRoute(string $route): self
    {
        $this->config['custom_edit_route'] = $route;
        return $this;
    }

    /**
     * Set custom point route, sometimes used for custom edit
     */
    public function setCustomPointRoute(string $route, string $customActionName = 'customAction'): self
    {
        $this->config['custom_point_route'] = $route;
        $this->config['custom_action_name'] = $customActionName;
        return $this;
    }

    /**
     * Quick setup method for common configurations
     */
    public function quickSetup(
        string $routePrefix,
        string $modelClass,
        string $guard,
        ?string $customEditRoute = null
    ): self {
        // Set endpoints using route names
        $this->setEndpointsFromRoutes($routePrefix);

        // Set model
        $this->setModel($modelClass);

        // Set permissions
        $this->setPermissions($guard);

        // Set custom edit route if provided
        if ($customEditRoute) {
            $this->setCustomEditRoute($customEditRoute);
        }

        return $this;
    }

    /**
     * Build the complete configuration array
     */
    public function build(): array
    {
        return array_merge($this->config, [
            'columns' => $this->getFields(),
            'defaultIdKey' => $this->defaultIdKey,
            'rowStyling' => $this->getRowStyling(),
        ]);
    }
}
