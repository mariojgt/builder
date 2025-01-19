
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
use Mariojgt\SkeletonAdmin\Enums\PermissionEnum;

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
            ->build();

        return Inertia::render('BackEnd/Vendor/GameDev/Generic/Index', [
            'title' => 'Alerts | Index',
            'table_name' => 'alerts',
            'breadcrumb' => $breadcrumb,
            ...$formConfig
        ]);
    }
}
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

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

If you found this package helpful, please consider giving it a star on GitHub!
