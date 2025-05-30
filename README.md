
![Builder Logo](https://raw.githubusercontent.com/mariojgt/builder/main/Publish/Art/logo.png)

# Laravel Builder

A powerful Laravel package that streamlines CRUD operations with a dynamic form builder and robust API integration. Build complex admin interfaces in minutes with our flexible, feature-rich solution.

## Features

- Dynamic Form Builder with multiple field types
- Built-in Laravel API integration
- Beautiful UI with Tailwind & DaisyUI
- Integrated permission system
- Highly customizable components
- Responsive design
- SPA experience with Inertia.js

## Requirements

- Laravel 8.x or higher
- PHP 8.0 or higher
- Tailwind CSS
- DaisyUI
- Inertia.js

## Installation

### Via Composer

```bash
composer require mariojgt/builder

php artisan install::builder
```

This command will:

- Copy required assets
- Run migrations
- Set up configuration files
- Install necessary dependencies

## Setup

### 1. Route Configuration

Add the following routes to your `web.php` or route file:

```php
use Mariojgt\Builder\Controllers\TableBuilderApiController;

Route::controller(TableBuilderApiController::class)->group(function () {
    Route::post('/admin/api/generic/table', 'index')
        ->name('admin.api.generic.table');
    Route::post('/admin/api/generic/table/create', 'store')
        ->name('admin.api.generic.table.create');
    Route::post('/admin/api/generic/table/update', 'update')
        ->name('admin.api.generic.table.update');
    Route::post('/admin/api/generic/table/delete', 'delete')
        ->name('admin.api.generic.table.delete');
});
```

### 2. Implementation

Here's a basic example of implementing the Builder in your controller:

```php
<?php

namespace Mariojgt\GameDev\Controllers\Backend;

use Inertia\Inertia;
use Mariojgt\GameDev\Models\Alert;
use App\Http\Controllers\Controller;
use Mariojgt\Builder\Enums\FieldTypes;
use Mariojgt\Builder\Helpers\FormHelper;
use Mariojgt\Builder\Enums\PermissionEnum;;

class AlertController extends Controller
{
    public function index()
    {
        $breadcrumb = [
            [
                'label' => 'Alerts',
                'url' => route('gamedev.alert.index'),
            ]
        ];

        $form = new FormHelper();
        $formConfig = $form
            ->addIdField()
            // ->addIdField(label: 'product_id', key: 'product_id') // custom id field
            ->tab('General Info')
            ->addField(
                label: 'Title',
                key: 'title',
                sortable: true,
                type: FieldTypes::TEXT->value
            )->withRules([ // You can pass laravel validation rules in the withRules method
                'required',
                'min:3',
                new AlertTitleValidator()
            ])
            // Example 2 with custom error message
            ->withRules([
                'required',
                'min:3',
                new AlertTitleValidator()
            ], ['min' => 'Please add at least 3 characters'])
            ->addField(
                label: 'HTML Content',
                key: 'html_content',
                sortable: false,
                type: FieldTypes::EDITOR->value
            )
            ->tab('Settings')
            ->addField(
                label: 'Type',
                key: 'type',
                sortable: true,
                type: FieldTypes::SELECT->value,
                options: [
                    'info' => 'Information',
                    'warning' => 'Warning',
                    'error' => 'Error',
                    'maintenance' => 'Maintenance'
                ],
            )
            ->addField(
                label: 'Icon',
                key: 'icon',
                sortable: false,
                type: FieldTypes::SELECT->value,
                options: [
                    'AlertTriangle' => 'Warning Triangle',
                    'AlertCircle' => 'Info Circle',
                    'AlertOctagon' => 'Error Octagon',
                    'Clock' => 'Clock',
                    'Bell' => 'Bell',
                    'Info' => 'Info'
                ],
            )
            ->addField(
                label: 'Theme',
                key: 'theme',
                sortable: true,
                type: FieldTypes::SELECT->value,
                options: [
                    'dark' => 'Dark Theme',
                    'light' => 'Light Theme'
                ],
            )
            ->addField(
                label: 'Button Text',
                key: 'button_text',
                sortable: false,
                type: FieldTypes::TEXT->value,
            )
            ->addField(
                label: 'Button Icon',
                key: 'button_icon',
                sortable: false,
                type: FieldTypes::SELECT->value,
                options: [
                    'Eye' => 'Eye',
                    'Check' => 'Checkmark',
                    'X' => 'Close',
                    'ThumbsUp' => 'Thumbs Up'
                ],
            )
            ->addField(
                label: 'Enabled',
                key: 'is_enabled',
                sortable: true,
                type: FieldTypes::BOOLEAN->value,
            )
            ->addField(
                label: 'Full Screen',
                key: 'is_full_screen',
                sortable: true,
                type: FieldTypes::BOOLEAN->value,
            )
            ->addField(
                label: 'Scheduled At',
                key: 'scheduled_at',
                sortable: true,
                canEdit: false,
                type: FieldTypes::TIMESTAMP->value,
            )
            ->addField(
                label: 'Start At',
                key: 'start_at',
                sortable: true,
                canEdit: false,
                type: FieldTypes::TIMESTAMP->value,
            )
            ->addField(
                label: 'End At',
                key: 'end_at',
                sortable: true,
                canEdit: false,
                type: FieldTypes::TIMESTAMP->value,
            )
            ->addField(
                label: 'Dismissible',
                key: 'is_dismissible',
                sortable: true,
                type: FieldTypes::BOOLEAN->value,
            )
            ->addField(
                label: 'Display Order',
                key: 'display_order',
                sortable: true,
                type: FieldTypes::NUMBER->value,
            )
            ->setEndpoints(
                listEndpoint: route('admin.api.generic.table'),
                deleteEndpoint: route('admin.api.generic.table.delete'),
                createEndpoint: route('admin.api.generic.table.create'),
                editEndpoint: route('admin.api.generic.table.update')
            )
            ->setModel(Alert::class)
            ->setPermissions(
                guard: 'skeleton_admin',
                type: 'permission',
                permissions: [
                    'store' => PermissionEnum::CreatePermission->value,
                    'update' => PermissionEnum::EditPermission->value,
                    'delete' => PermissionEnum::DeletePermission->value,
                    'index' => PermissionEnum::ReadPermission->value,
                ]
            )
            ->setCustomEditRoute('/sysadmin/vendor/edit/') // Custom edit route
            ->setCustomPointRoute('/sysadmin/user/permissions/edit/') // Custom point route
            ->build();

        return Inertia::render('BackEnd/Vendor/GameDev/Generic/Index', [
            'title' => 'Alerts | Index',
            'table_name' => 'alerts',
            'breadcrumb' => $breadcrumb,
            ...$formConfig
        ]);
    }
}


// Table filters example
$form = new FormHelper();
$formConfig = $form
    ->addIdField()
    ->tab('General Info')
    ->addField(
        label: 'Name',
        key: 'name',
        sortable: true,
        canCreate: true,
        canEdit: true,
        type: FieldTypes::TEXT->value,
        filterable: true  // Enable filtering for this field
    )
    ->addField(
        label: 'Is Active',
        key: 'is_active',
        sortable: true,
        type: FieldTypes::BOOLEAN->value,
        canCreate: true,
        canEdit: true,
        filterable: true
    )
    ->addField(
        label: 'Created At',
        key: 'created_at',
        type: FieldTypes::DATE->value,
        filterable: true,
        filterOptions: ['type' => 'date-range']
    )
    ->addField(
        label: 'Status',
        key: 'status',
        type: FieldTypes::SELECT->value,
        options: [
            'select_options' => [
                ['value' => 'active', 'label' => 'Active'],
                ['value' => 'inactive', 'label' => 'Inactive'],
                ['value' => 'pending', 'label' => 'Pending']
            ]
        ],
        filterable: true
    )
    // ... rest of your fields
```

Vue js example

```js
<template>
    <AppLayout>
      <Table
        :columns="props.columns"
        :model="props.model"
        :endpoint="props.endpoint"
        :endpoint-delete="props.endpointDelete"
        :endpoint-create="props.endpointCreate"
        :endpoint-edit="props.endpointEdit"
        :table-title="props.title"
        :permission="props.permission"
        :defaultIdKey="props.defaultIdKey"
        :custom_edit_route="props.custom_edit_route"
        :custom_point_route="props.custom_point_route"
        :custom_action_name="props.custom_action_name"
      />
    </AppLayout>
  </template>

  <script setup lang="ts">
  import AppLayout from '@components/Layout/AppLayout.vue';
  import Table from '@builder/Table.vue';

  const props = defineProps({
    endpoint: { type: String, default: '' },
    columns: { type: Object, default: () => ({}) },
    model: { type: String, default: '' },
    endpointDelete: { type: String, default: '' },
    endpointCreate: { type: String, default: '' },
    endpointEdit: { type: String, default: '' },
    permission: { type: String, default: '' },
    title: { type: String, default: '' },
    defaultIdKey: { type: String, default: '' },
    custom_edit_route: { type: String, default: '' },
    custom_point_route: { type: String, default: '' },
    custom_action_name: { type: String, default: '' },
  });
  </script>
```

## Field Types

Builder supports various field types:

- `text` - Text input
- `email` - Email input
- `password` - Password input
- `date` - Date picker
- `timestamp` - Datetime picker
- `select` - Dropdown select
- `boolean` - Toggle switch
- `media` - Media upload
- `editor` - Rich text editor
- `number` - Number input
- `model_search` - Model relationship search
- `pivot_model` - Many-to-many relationship
- `chips` - Tags/chips input
- `icon` - Icon selector

## Permissions

Builder includes built-in permission support:

- Role-based access control
- Permission-based access control
- Encrypted permission handling
- Customizable guards

## Customization

You can customize:

- Field types and validation
- UI components and styling
- API endpoints
- Permission handling
- Form layouts and sections
- Error handling and display
- Loading states and animations

## Node Dependencies

Builder requires the following Node dependencies:

- `"@headlessui/vue": "^1.7.23"`
- `"@inertiajs/vue3": "^2.0.3"`
- `"@mariojgt/masterui": "^0.5.6"`
- `"@mariojgt/wind-notify": "^1.0.3"`
- `"lucide-vue-next": "^0.474.0",`

Vite configuration:

```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite'
import path from 'path';

export default defineConfig({
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources'),
            '@css': path.resolve(__dirname, './resources/css'), // Used in the builder plugins to point ot the css
            '@components': path.resolve(__dirname, './resources/js/components'),
            '@builder': '/resources/vendor/Builder/Table',
        },
    },
    server: {
        host: '0.0.0.0',
        hmr: {
            host: 'localhost'
        },
    },
    plugins: [
        tailwindcss(),
        laravel({
            input: ['resources/js/app.js', 'resources/css/app.css'],
            refresh: true,
        }),
        vue(),
    ],
});

```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

If you found this package helpful, please consider giving it a star on GitHub!
