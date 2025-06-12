![Builder Logo](https://raw.githubusercontent.com/mariojgt/builder/main/Publish/Art/logo.png)

# Laravel Builder

A powerful Laravel package that streamlines CRUD operations with a dynamic form builder and robust API integration. Build complex admin interfaces in minutes with our flexible, feature-rich solution.

## Features

- Dynamic Form Builder with multiple field types
- Built-in Laravel API integration with automatic relationship detection
- **Advanced Conditional Styling** for dynamic visual feedback
- Fallback relationship support for flexible data display
- Advanced filtering with date ranges and custom filters
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

### 2. Basic Implementation

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
            ->tab('General Info')
            ->addField(
                label: 'Title',
                key: 'title',
                sortable: true,
                type: FieldTypes::TEXT->value
            )
            ->withRules([
                'required',
                'min:3',
                new AlertTitleValidator()
            ])
            ->addField(
                label: 'Status',
                key: 'status',
                sortable: true,
                type: FieldTypes::TEXT->value
            )
            ->withConditionalStyling([
                'active' => 'bg-green-500 text-white border-green-600',
                'inactive' => 'bg-red-500 text-white border-red-600',
                'pending' => 'bg-yellow-500 text-black border-yellow-600'
            ])
            ->addField(
                label: 'Created At',
                key: 'created_at',
                sortable: true,
                type: FieldTypes::TIMESTAMP->value,
                filterable: true
            )
            ->setEndpoints(
                listEndpoint: route('admin.api.generic.table'),
                deleteEndpoint: route('admin.api.generic.table.delete'),
                createEndpoint: route('admin.api.generic.table.create'),
                editEndpoint: route('admin.api.generic.table.update')
            )
            ->setModel(Alert::class)
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

## 🎨 Advanced Conditional Styling

One of Builder's most powerful features is the ability to apply dynamic styling based on field values. This provides instant visual feedback and improves user experience.

### Simple Conditional Styling

Apply different styles based on exact value matches:

```php
->addField(
    label: 'Status',
    key: 'status',
    type: FieldTypes::TEXT->value
)
->withConditionalStyling([
    'unpatched' => 'bg-red-500 text-white border-red-600 shadow-lg',
    'patched' => 'bg-green-500 text-white border-green-600',
    'pending' => 'bg-yellow-500 text-black border-yellow-600'
], 'bg-gray-200 text-gray-800') // Default style for unknown values
```

### Advanced Conditional Styling with Operators

Use advanced operators for more complex conditions:

```php
->addField(
    label: 'CVSS Score',
    key: 'cvss_score',
    type: FieldTypes::NUMBER->value
)
->withAdvancedStyling([
    ['operator' => 'between', 'min' => 9.0, 'max' => 10.0, 'classes' => 'bg-red-600 text-white border-red-700 font-bold animate-pulse'],
    ['operator' => 'between', 'min' => 7.0, 'max' => 8.9, 'classes' => 'bg-red-500 text-white border-red-600'],
    ['operator' => 'between', 'min' => 4.0, 'max' => 6.9, 'classes' => 'bg-orange-500 text-white border-orange-600'],
    ['operator' => 'less_than', 'value' => 4.0, 'classes' => 'bg-green-500 text-white border-green-600']
])
```

### Available Operators

- **Equality**: `equals`, `not_equals`
- **Comparison**: `greater_than`, `greater_than_equal`, `less_than`, `less_than_equal`
- **Range**: `between` (with min/max values)
- **String**: `contains`, `starts_with`, `ends_with`

### Preset Styling Methods

Builder includes convenient preset methods for common use cases:

```php
// Status styling with common status values
->withStatusStyling()

// CVSS scoring (0-10 scale with appropriate colors)
->withCVSSStyling()

// Severity levels (critical, high, medium, low)
->withSeverityStyling()

// Percentage-based styling (0-100% with color grades)
->withPercentageStyling()
```

### Real-World Security Example

```php
return (new FormHelper())
    ->addIdField(label: 'id', key: 'id')
    ->tab('Vulnerability Details')

    // Component with framework detection
    ->addField(
        label: 'Component Name',
        key: 'component_name',
        type: FieldTypes::TEXT->value
    )
    ->withAdvancedStyling([
        ['operator' => 'contains', 'value' => 'wordpress', 'classes' => 'bg-blue-100 text-blue-800 border-blue-200'],
        ['operator' => 'contains', 'value' => 'drupal', 'classes' => 'bg-indigo-100 text-indigo-800 border-indigo-200'],
        ['operator' => 'contains', 'value' => 'laravel', 'classes' => 'bg-red-100 text-red-800 border-red-200']
    ])

    // Vulnerability type with severity-based colors
    ->addField(
        label: 'Vulnerability Type',
        key: 'vuln_type',
        type: FieldTypes::TEXT->value
    )
    ->withAdvancedStyling([
        ['operator' => 'contains', 'value' => 'sql injection', 'classes' => 'bg-red-600 text-white border-red-700 font-semibold'],
        ['operator' => 'contains', 'value' => 'xss', 'classes' => 'bg-red-500 text-white border-red-600'],
        ['operator' => 'contains', 'value' => 'csrf', 'classes' => 'bg-orange-500 text-white border-orange-600']
    ])

    // Status with multiple states
    ->addField(
        label: 'Status',
        key: 'status',
        type: FieldTypes::TEXT->value
    )
    ->withConditionalStyling([
        'duplicate' => 'bg-red-500 text-white border-red-600 shadow-lg',
        'finished' => 'bg-green-500 text-white border-green-600',
        'unvalidated' => 'bg-yellow-500 text-black border-yellow-600',
        'in_progress' => 'bg-blue-500 text-white border-blue-600 animate-pulse'
    ])

    // Patch status
    ->addField(
        label: 'Patch Status',
        key: 'patch_status',
        type: FieldTypes::TEXT->value
    )
    ->withConditionalStyling([
        'patched' => 'bg-green-500 text-white border-green-600',
        'unpatched' => 'bg-red-500 text-white border-red-600 animate-pulse',
        'partial' => 'bg-yellow-500 text-black border-yellow-600'
    ])

    // CVSS Score with built-in styling
    ->addField(
        label: 'CVSS Base Score',
        key: 'cvss_base',
        type: FieldTypes::NUMBER->value
    )
    ->withCVSSStyling()

    // Priority with numeric conditions
    ->addField(
        label: 'Priority',
        key: 'priority',
        type: FieldTypes::NUMBER->value
    )
    ->withAdvancedStyling([
        ['operator' => 'equals', 'value' => 1, 'classes' => 'bg-red-600 text-white border-red-700 font-bold'],
        ['operator' => 'equals', 'value' => 2, 'classes' => 'bg-orange-500 text-white border-orange-600'],
        ['operator' => 'equals', 'value' => 3, 'classes' => 'bg-yellow-500 text-black border-yellow-600'],
        ['operator' => 'greater_than_equal', 'value' => 4, 'classes' => 'bg-green-500 text-white border-green-600']
    ]);
```

### Visual Effects and Animations

Enhance your styling with CSS animations and effects:

```php
->withConditionalStyling([
    'critical' => 'bg-red-600 text-white border-red-700 font-bold animate-pulse shadow-lg',
    'urgent' => 'bg-orange-500 text-white border-orange-600 shadow-md',
    'normal' => 'bg-blue-500 text-white border-blue-600',
    'low' => 'bg-green-500 text-white border-green-600'
])
```

Available CSS classes:
- **Animations**: `animate-pulse`, `animate-bounce`, `animate-spin`
- **Shadows**: `shadow-sm`, `shadow-md`, `shadow-lg`, `shadow-xl`
- **Font weights**: `font-bold`, `font-semibold`, `font-medium`
- **Special effects**: Gradients, rounded corners, borders

## Advanced Relationship Features

### Fallback Relationship Support

Builder automatically detects and loads relationships from field keys. It also supports fallback relationships for flexible data display:

```php
// Example with relationships and fallback support
return (new FormHelper())
    ->addIdField(label: 'id', key: 'id')
    ->tab('General Info')

    // Simple relationship field
    ->addField(
        label: 'Component Name',
        key: 'reportedData.comp_name',  // Automatically loads 'reportedData' relationship
        type: FieldTypes::TEXT->value
    )

    // Fallback relationship - tries first field, falls back to second if first is empty
    ->addField(
        label: 'Version',
        key: 'reportedTempData.affected_in|reportedData.vuln_version',  // Fallback with |
        type: FieldTypes::TEXT->value
    )

    // Nested relationship with fallback
    ->addField(
        label: 'Vulnerability type',
        key: 'reportedTempData.type.name|reportedData.select_type',  // Multi-level relationship
        type: FieldTypes::TEXT->value
    );
```

### How Relationships Work

Builder automatically:

1. **Detects relationships** from field keys using dot notation
2. **Loads relationships** using Eloquent's `with()` method
3. **Supports nested relationships** like `reportedTempData.type.name`
4. **Handles fallback relationships** using the pipe (`|`) separator
5. **Optimizes queries** by loading only necessary relationships

#### Relationship Examples:

- `reportedData.comp_name` → Loads `reportedData` relationship
- `reportedTempData.type.name` → Loads `reportedTempData.type` nested relationship
- `user.profile.avatar` → Loads `user.profile` nested relationship
- `primary.field|backup.field` → Tries primary first, falls back to backup

## Advanced Filtering

Builder supports comprehensive filtering with various field types:

```php
// Table filters example with all supported filter types
$form = new FormHelper();
$formConfig = $form
    ->addIdField()
    ->tab('General Info')

    // Text filter with search modes (contains, exact, starts)
    ->addField(
        label: 'Name',
        key: 'name',
        sortable: true,
        canCreate: true,
        canEdit: true,
        type: FieldTypes::TEXT->value,
        filterable: true
    )

    // Boolean filter (True/False dropdown)
    ->addField(
        label: 'Is Active',
        key: 'is_active',
        sortable: true,
        type: FieldTypes::BOOLEAN->value,
        canCreate: true,
        canEdit: true,
        filterable: true
    )

    // Date range filter with quick presets
    ->addField(
        label: 'Created At',
        key: 'created_at',
        type: FieldTypes::TIMESTAMP->value,
        filterable: true,
        sortable: true
    )

    // Select dropdown filter
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
        filterable: true,
        sortable: true
    );
```

### Filter Features:

- **Date Range Filters**: From/To date selection with quick presets (Today, 7 days, 30 days, 90 days)
- **Text Search Modes**: Contains, Exact match, Starts with
- **Boolean Filters**: True/False dropdown selection
- **Select Filters**: Dropdown with predefined options
- **Relationship Filters**: Filter by related model data
- **Model Search Filters**: Searchable dropdown with related model data
- **Number Filters**: Numeric value filtering
- **Real-time Filtering**: Filters apply automatically as you type/select

## Vue.js Frontend Example

```vue
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

## Best Practices for Conditional Styling

### 1. When to Use Styling

✅ **Use conditional styling for:**
- Status and state fields
- Priority levels
- Severity indicators
- CVSS scores
- Progress indicators
- Boolean values

❌ **Avoid styling for:**
- Descriptive text fields
- Names and titles
- Long descriptions
- Generic text content

### 2. Styling Guidelines

```php
// ✅ Good: Status field with clear visual distinction
->addField(label: 'Status', key: 'status', type: FieldTypes::TEXT->value)
->withConditionalStyling([
    'active' => 'bg-green-500 text-white border-green-600',
    'inactive' => 'bg-red-500 text-white border-red-600'
])

// ✅ Good: Numeric field with meaningful ranges
->addField(label: 'Score', key: 'score', type: FieldTypes::NUMBER->value)
->withAdvancedStyling([
    ['operator' => 'greater_than', 'value' => 80, 'classes' => 'bg-green-500 text-white'],
    ['operator' => 'less_than', 'value' => 60, 'classes' => 'bg-red-500 text-white']
])

// ❌ Avoid: Over-styling descriptive fields
->addField(label: 'Description', key: 'description', type: FieldTypes::TEXT->value)
// No styling needed - let it display as plain text
```

### 3. Performance Tips

1. **Use styling sparingly** - Only where it adds real value
2. **Prefer simple conditions** over complex operators when possible
3. **Use preset methods** (`withStatusStyling()`, `withCVSSStyling()`) for common patterns
4. **Test with real data** to ensure styling works as expected

## Key Features Explained

### Automatic Relationship Detection

Builder automatically detects relationships from your field keys:

```php
// These field keys automatically load the necessary relationships:
'user.name'                    // Loads: user
'category.parent.name'         // Loads: category, category.parent
'product.vendor.contact.email' // Loads: product, product.vendor, product.vendor.contact
```

### Smart Query Optimization

Builder optimizes your queries by:

- Only loading relationships that are actually used in your columns
- Using `select()` to limit columns when no relationships are present
- Automatically including the model's primary key (even custom ones like `product_id`)
- Batching relationship loads to minimize database queries

### Custom Primary Keys

Builder automatically detects custom primary keys:

```php
// In your model:
class Product extends Model
{
    protected $primaryKey = 'product_id';
}

// In your FormHelper:
->addIdField(label: 'Product ID', key: 'product_id')
```

## Permissions

Builder includes built-in permission support:

- Role-based access control
- Permission-based access control
- Encrypted permission handling
- Customizable guards

```php
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
```

## Customization

You can customize:

- Field types and validation
- UI components and styling
- API endpoints
- Permission handling
- Form layouts and sections
- Error handling and display
- Loading states and animations
- Custom edit and point routes
- Filter types and behaviors
- Conditional styling rules and effects

## Node Dependencies

Builder requires the following Node dependencies:

- `"@headlessui/vue": "^1.7.23"`
- `"@inertiajs/vue3": "^2.0.3"`
- `"@mariojgt/masterui": "^0.5.6"`
- `"@mariojgt/wind-notify": "^1.0.3"`
- `"lucide-vue-next": "^0.474.0"`

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
            '@css': path.resolve(__dirname, './resources/css'), // Used in the builder plugins to point to the css
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

## Troubleshooting

### Common Issues:

1. **Conditional styling not working**:
   - Ensure you're calling `->withConditionalStyling()` or `->withAdvancedStyling()` after the field
   - Check that the values match exactly (case-sensitive)
   - Verify the CSS classes are available in your build

2. **Relationship not loading**:
   - Ensure the relationship exists in your model
   - Check the relationship name matches the field key

3. **Custom primary key errors**:
   - Define `protected $primaryKey = 'your_key'` in your model

4. **Filtering not working**:
   - Check that `filterable: true` is set and the field type supports filtering

### Debug Mode:

Add logging to your controller to debug styling:

```php
// Add this to see what styling is being applied
\Log::info('Column configuration:', $formConfig['columns']);
```

## Performance Tips

1. **Use conditional styling wisely** - Apply only where visual distinction adds value
2. **Enable filtering only where needed** - Each filterable field adds to the UI complexity
3. **Optimize your model relationships** - Ensure proper indexing on foreign keys
4. **Use custom primary keys properly** - Define them in your model for automatic detection
5. **Test with real data** - Ensure your styling conditions work with actual values

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

If you found this package helpful, please consider giving it a star on GitHub!',
        type: FieldTypes::TEXT->value
    )
    ->addField(
        label: 'Status Contact',
        key: 'status_contact',
        type: FieldTypes::TEXT->value
    )

    // Nested relationship field
    ->addField(
        label: 'CVSS Base',
        key: 'reportedTempData.cvssbase',
        type: FieldTypes::TEXT->value
    )

    // Filterable timestamp fields
    ->addField(
        label: 'Created at',
        key: 'created_at',
        type: FieldTypes::TIMESTAMP->value,
        filterable: true,
        sortable: true
    )
    ->addField(
        label: 'Updated at',
        key: 'updated_at',
        type: FieldTypes::TIMESTAMP->value,
        filterable: true,
        sortable: true
    );
```

### How Relationships Work

Builder automatically:

1. **Detects relationships** from field keys using dot notation
2. **Loads relationships** using Eloquent's `with()` method
3. **Supports nested relationships** like `reportedTempData.type.name`
4. **Handles fallback relationships** using the pipe (`|`) separator
5. **Optimizes queries** by loading only necessary relationships

#### Relationship Examples:

- `reportedData.comp_name` → Loads `reportedData` relationship
- `reportedTempData.type.name` → Loads `reportedTempData.type` nested relationship
- `user.profile.avatar` → Loads `user.profile` nested relationship
- `primary.field|backup.field` → Tries primary first, falls back to backup

### 4. Advanced Filtering

Builder supports comprehensive filtering with various field types:

```php
// Table filters example with all supported filter types
$form = new FormHelper();
$formConfig = $form
    ->addIdField()
    ->tab('General Info')

    // Text filter with search modes (contains, exact, starts)
    ->addField(
        label: 'Name',
        key: 'name',
        sortable: true,
        canCreate: true,
        canEdit: true,
        type: FieldTypes::TEXT->value,
        filterable: true
    )

    // Boolean filter (True/False dropdown)
    ->addField(
        label: 'Is Active',
        key: 'is_active',
        sortable: true,
        type: FieldTypes::BOOLEAN->value,
        canCreate: true,
        canEdit: true,
        filterable: true
    )

    // Date range filter with quick presets
    ->addField(
        label: 'Created At',
        key: 'created_at',
        type: FieldTypes::TIMESTAMP->value,
        filterable: true,
        sortable: true
    )

    // Select dropdown filter
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
        filterable: true,
        sortable: true
    )

    // Relationship field filter (also supports fallback relationships)
    ->addField(
        label: 'Category',
        key: 'category.name',
        type: FieldTypes::TEXT->value,
        filterable: true,
        sortable: true
    )

    // Model search filter (dropdown with related model data)
    ->addField(
        label: 'User',
        key: 'user_id',
        type: FieldTypes::MODEL_SEARCH->value,
        relation: 'user',
        displayKey: 'name',
        endpoint: route('admin.api.users.search'),
        filterable: true,
        sortable: true
    )

    // Number filter
    ->addField(
        label: 'Price',
        key: 'price',
        type: FieldTypes::NUMBER->value,
        filterable: true,
        sortable: true
    );
```

### Filter Features:

- **Date Range Filters**: From/To date selection with quick presets (Today, 7 days, 30 days, 90 days)
- **Text Search Modes**: Contains, Exact match, Starts with
- **Boolean Filters**: True/False dropdown selection
- **Select Filters**: Dropdown with predefined options
- **Relationship Filters**: Filter by related model data
- **Model Search Filters**: Searchable dropdown with related model data
- **Number Filters**: Numeric value filtering
- **Real-time Filtering**: Filters apply automatically as you type/select

### Filter Types:

1. **`filterable: true`** - Enables filtering for the field
2. **Date/Timestamp** - Shows date range picker with quick presets
3. **Boolean** - Shows True/False/All dropdown
4. **Select** - Shows dropdown with predefined options
5. **Text** - Shows text input with search modes
6. **Model Search** - Shows searchable dropdown with related data
7. **Number** - Shows number input for numeric filtering

## Vue.js Frontend Example

```vue
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

## Key Features Explained

### Automatic Relationship Detection

Builder automatically detects relationships from your field keys:

```php
// These field keys automatically load the necessary relationships:
'user.name'                    // Loads: user
'category.parent.name'         // Loads: category, category.parent
'product.vendor.contact.email' // Loads: product, product.vendor, product.vendor.contact
```

### Fallback Relationships

Use the pipe (`|`) separator for fallback relationships:

```php
// If 'tempData.version' is empty, it will show 'data.backup_version'
'tempData.version|data.backup_version'

// Multiple fallbacks are supported
'primary.field|secondary.field|tertiary.field'
```

### Smart Query Optimization

Builder optimizes your queries by:

- Only loading relationships that are actually used in your columns
- Using `select()` to limit columns when no relationships are present
- Automatically including the model's primary key (even custom ones like `product_id`)
- Batching relationship loads to minimize database queries

### Custom Primary Keys

Builder automatically detects custom primary keys:

```php
// In your model:
class Product extends Model
{
    protected $primaryKey = 'product_id';
}

// In your FormHelper:
->addIdField(label: 'Product ID', key: 'product_id')
```

## Permissions

Builder includes built-in permission support:

- Role-based access control
- Permission-based access control
- Encrypted permission handling
- Customizable guards

```php
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
```

## Customization

You can customize:

- Field types and validation
- UI components and styling
- API endpoints
- Permission handling
- Form layouts and sections
- Error handling and display
- Loading states and animations
- Custom edit and point routes
- Filter types and behaviors

## Node Dependencies

Builder requires the following Node dependencies:

- `"@headlessui/vue": "^1.7.23"`
- `"@inertiajs/vue3": "^2.0.3"`
- `"@mariojgt/masterui": "^0.5.6"`
- `"@mariojgt/wind-notify": "^1.0.3"`
- `"lucide-vue-next": "^0.474.0"`

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
            '@css': path.resolve(__dirname, './resources/css'), // Used in the builder plugins to point to the css
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

## Performance Tips

1. **Use fallback relationships wisely** - They're great for display flexibility but can impact performance with many fallbacks
2. **Enable filtering only where needed** - Each filterable field adds to the UI complexity
3. **Optimize your model relationships** - Ensure proper indexing on foreign keys
4. **Use custom primary keys properly** - Define them in your model for automatic detection

## Troubleshooting

### Common Issues:

1. **Relationship not loading**: Ensure the relationship exists in your model
2. **Custom primary key errors**: Define `protected $primaryKey = 'your_key'` in your model
3. **Filtering not working**: Check that `filterable: true` is set and the field type supports filtering
4. **Fallback not working**: Ensure you're using the pipe (`|`) separator correctly

### Debug Mode:

Add logging to your controller to debug relationship detection:

```php
// Add this to your TableBuilderApiController
\Log::info('Auto-detected relationships:', $relationships);
\Log::info('Filters received:', $filters);
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

If you found this package helpful, please consider giving it a star on GitHub!
