<?php

namespace Mariojgt\Builder\Helpers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Mariojgt\Builder\Enums\FieldTypes;
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
     * @var string|null|null
     */
    private ?string $defaultIdKey = null;

    /**
     * Set the current tab for subsequent fields
     */
    public function tab(string $tabName): self
    {
        $this->currentTab = $tabName;
        return $this;
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
     * Add a new field to the form
     *
     * @throws \InvalidArgumentException When invalid parameters are provided
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
        array $filterOptions = []
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

        $this->columns[] = $field;
        return $this;
    }

    /**
     * Add ID field (commonly used)
     *
     * @param string $label
     * @param string $key
     * @return self
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
     * Add email field with preset configuration
     *
     * @param string $label
     * @param string $key
     * @param bool $canEdit
     * @return self
     */
    public function addEmailField(
        string $label = 'Email',
        string $key = 'email',
        bool $canEdit = false
    ): self {
        return $this->addField(
            label: $label,
            key: $key,
            type: FieldTypes::EMAIL->value,
            canEdit: $canEdit
        );
    }

    /**
     * @return self
     */
    public function addImageField(): self
    {
        return $this->addField(
            label: 'Image',
            key: 'image',
            sortable: false,
            canCreate: true,
            canEdit: true,
            nullable: true,
            type: FieldTypes::MEDIA->value,
            endpoint: route('admin.api.media.search')
        );
    }

    /**
     * Add password field
     *
     * @param string $label
     * @param string $key
     * @return self
     */
    public function addPasswordField(
        string $label = 'Password',
        string $key = 'password'
    ): self {
        return $this->addField(
            label: $label,
            key: $key,
            type: FieldTypes::PASSWORD->value,
            sortable: false
        );
    }

    /**
     * Add date field
     *
     * @param string $label
     * @param string $key
     * @param bool $sortable
     * @param bool $canEdit
     * @return self
     */
    public function addDateField(
        string $label,
        string $key,
        bool $sortable = true,
        bool $canEdit = true
    ): self {
        return $this->addField(
            label: $label,
            key: $key,
            type: FieldTypes::DATE->value,
            sortable: $sortable,
            canEdit: $canEdit
        );
    }

    /**
     * Add select field
     *
     * @param string $label
     * @param string $key
     * @param array $options
     * @param bool $sortable
     * @param bool $canEdit
     * @return self
     */
    public function addSelectField(
        string $label,
        string $key,
        array $options,
        bool $sortable = true,
        bool $canEdit = true
    ): self {
        return $this->addField(
            label: $label,
            key: $key,
            type: FieldTypes::SELECT->value,
            sortable: $sortable,
            canEdit: $canEdit,
            options: $options
        );
    }

     /**
     * Add a timestamp field
     *
     * @param string $label
     * @param string $key
     * @param bool $sortable
     * @param bool $canCreate
     * @param bool $canEdit
     * @return self
     */
    public function addTimestampField(
        string $label,
        string $key,
        bool $sortable = false,
        bool $canCreate = false,
        bool $canEdit = false
    ): self {
        return $this->addField(
            label: $label,
            key: $key,
            type: FieldTypes::TIMESTAMP->value,
            sortable: $sortable,
            canCreate: $canCreate,
            canEdit: $canEdit
        );
    }

    /**
     * Add a select field with options
     *
     * @param string $label
     * @param string $key
     * @param array $options
     * @param bool $sortable
     * @param bool $canCreate
     * @param bool $canEdit
     * @return self
     */
    public function addSelectWithOptions(
        string $label,
        string $key,
        array $options,
        bool $sortable = true,
        bool $canCreate = true,
        bool $canEdit = true
    ): self {
        return $this->addField(
            label: $label,
            key: $key,
            type: FieldTypes::SELECT->value,
            sortable: $sortable,
            canCreate: $canCreate,
            canEdit: $canEdit,
            options: [
                'select_options' => $options
            ]
        );
    }

     /**
     * Add an icon field
     *
     * @param string $label
     * @param string $key
     * @param bool $sortable
     * @param bool $canCreate
     * @param bool $canEdit
     * @return self
     */
    public function addIconField(
        string $label,
        string $key,
        bool $sortable = true,
        bool $canCreate = false,
        bool $canEdit = true
    ): self {
        return $this->addField(
            label: $label,
            key: $key,
            type: FieldTypes::ICON->value,
            sortable: $sortable,
            canCreate: $canCreate,
            canEdit: $canEdit
        );
    }

    /**
     * Add a boolean field
     *
     * @param string $label
     * @param string $key
     * @param bool $sortable
     * @param bool $canCreate
     * @param bool $canEdit
     * @return self
     */
    public function addBooleanField(
        string $label,
        string $key,
        bool $sortable = true,
        bool $canCreate = false,
        bool $canEdit = true
    ): self {
        return $this->addField(
            label: $label,
            key: $key,
            type: FieldTypes::BOOLEAN->value,
            sortable: $sortable,
            canCreate: $canCreate,
            canEdit: $canEdit
        );
    }

    /**
     * Get all form fields
     *
     * @return array
     */
    public function getFields(): array
    {
        return $this->columns;
    }

    /**
     * Clear all fields
     *
     * @return self
     */
    public function clear(): self
    {
        $this->columns = [];
        $this->config = [];
        return $this;
    }

    /**
     * Get fields that are creatable
     *
     * @return array
     */
    public function getCreatableFields(): array
    {
        return array_filter($this->columns, fn($field) => $field['canCreate'] ?? false);
    }

    /**
     * Get fields that are editable
     *
     * @return array
     */
    public function getEditableFields(): array
    {
        return array_filter($this->columns, fn($field) => $field['canEdit'] ?? false);
    }

    /**
     * Get fields that are sortable
     *
     * @return array
     */
    public function getSortableFields(): array
    {
        return array_filter($this->columns, fn($field) => $field['sortable'] ?? false);
    }

    /**
     * Set the basic endpoints for the table
     *
     * @param string $listEndpoint
     * @param string $deleteEndpoint
     * @param string $createEndpoint
     * @param string $editEndpoint
     * @return self
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
     *
     * @param string $prefix
     * @return self
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
     *
     * @param array<string, string> $permissions
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
     *
     * @param string $route
     * @return self
     */
    public function setCustomEditRoute(string $route): self
    {
        $this->config['custom_edit_route'] = $route;
        return $this;
    }

    /**
     * Set custom point route, sometimes used for custom edit
     *
     * @param string $route
     * @param string $customActionName
     * @return self
     */
    public function setCustomPointRoute(string $route, string $customActionName = 'customAction'): self
    {
        $this->config['custom_point_route'] = $route;
        $this->config['custom_action_name'] = $customActionName;
        return $this;
    }

    /**
     * Quick setup method for common configurations
     *
     * @param string $routePrefix
     * @param string $modelClass
     * @param string $guard
     * @param string|null $customEditRoute
     * @return self
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
     *
     * @return array<string, mixed>
     */
    public function build(): array
    {
        return array_merge($this->config, [
            'columns' => $this->getFields(),
            'defaultIdKey' => $this->defaultIdKey,
        ]);
    }
}
