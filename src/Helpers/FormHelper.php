<?php

namespace Mariojgt\Builder\Helpers;

use Mariojgt\Builder\Enums\FieldTypes;

class FormHelper
{
    /**
     * Store the form columns/fields
     */
    private array $columns = [];

    /**
     * Store the form configuration
     */
    private array $config = [];

    /**
     * Add a new field to the form
     *
     * @param string $label
     * @param string $key
     * @param bool $sortable
     * @param bool $canCreate
     * @param bool $canEdit
     * @param string|null $type
     * @param array $options
     * @return self
     */
    public function addField(
        string $label,
        string $key,
        bool $sortable = true,
        bool $canCreate = true,
        bool $canEdit = true,
        ?string $type = FieldTypes::TEXT->value,
        array $options = [],
        bool $unique = false,
        bool $nullable = false,
        ?string $endpoint = null,
        array $columns = [],
        ?string $model = null,
        bool $singleSearch = false,
        ?string $displayKey = null
    ): self {
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
            'displayKey' => $displayKey
        ];

        if ($type !== null) {
            $field['type'] = $type;
        }

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
     *
     * @param string $modelClass
     * @return self
     */
    public function setModel(string $modelClass): self
    {
        $this->config['model'] = encrypt($modelClass);
        return $this;
    }

    /**
     * Set permissions for CRUD operations
     *
     * @param string $guard
     * @param string $type
     * @param array $permissions
     * @return self
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
     * @return array
     */
    public function build(): array
    {
        return array_merge($this->config, [
            'columns' => $this->getFields(),
        ]);
    }
}
