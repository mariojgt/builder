# Laravel Builder

A powerful Laravel package that streamlines CRUD operations with a dynamic form builder and robust API integration. Build complex admin interfaces in minutes.

## Features

- **Dynamic Form Builder** with multiple field types
- **Built-in Laravel API** integration with automatic relationship detection
- **Advanced Conditional Styling** for dynamic visual feedback
- **Row-Level Conditional Styling** for entire table rows
- **Interactive Links** with customizable styling
- **Advanced Filtering** with date ranges and custom filters
- **Beautiful UI** with Tailwind & DaisyUI
- **Responsive Design** with SPA experience

## Installation

```bash
composer require mariojgt/builder
php artisan install::builder
```

## Quick Start

### 1. Setup Routes

```php
use Mariojgt\Builder\Controllers\TableBuilderApiController;

Route::controller(TableBuilderApiController::class)->group(function () {
    Route::post('/admin/api/generic/table', 'index')->name('admin.api.generic.table');
    Route::post('/admin/api/generic/table/create', 'store')->name('admin.api.generic.table.create');
    Route::post('/admin/api/generic/table/update', 'update')->name('admin.api.generic.table.update');
    Route::post('/admin/api/generic/table/delete', 'delete')->name('admin.api.generic.table.delete');
});
```

### 2. Basic Controller

```php
<?php

namespace App\Controllers;

use Inertia\Inertia;
use App\Models\Alert;
use App\Http\Controllers\Controller;
use Mariojgt\Builder\Enums\FieldTypes;
use Mariojgt\Builder\Helpers\FormHelper;

class AlertController extends Controller
{
    public function index()
    {
        $form = new FormHelper();
        $formConfig = $form
            ->addIdField()
            ->addField('Title', 'title', type: FieldTypes::TEXT->value)
            ->addField('Status', 'status', type: FieldTypes::TEXT->value)
            ->withConditionalStyling([
                'active' => 'bg-green-500 text-white',
                'inactive' => 'bg-red-500 text-white'
            ])
            ->addField('Created At', 'created_at', type: FieldTypes::TIMESTAMP->value)
            ->setEndpoints(
                listEndpoint: route('admin.api.generic.table'),
                deleteEndpoint: route('admin.api.generic.table.delete'),
                createEndpoint: route('admin.api.generic.table.create'),
                editEndpoint: route('admin.api.generic.table.update')
            )
            ->setModel(Alert::class)
            ->build();

        return Inertia::render('Admin/Generic/Index', [
            'title' => 'Alerts',
            'table_name' => 'alerts',
            ...$formConfig
        ]);
    }
}
```

### 3. Vue Component

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
      :row-styling="props.rowStyling"
    />
  </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@components/Layout/AppLayout.vue';
import Table from '@builder/Table.vue';

const props = defineProps({
  endpoint: String,
  columns: Object,
  model: String,
  endpointDelete: String,
  endpointCreate: String,
  endpointEdit: String,
  title: String,
  rowStyling: Object,
});
</script>
```

## 🔗 Interactive Links

Add clickable links to any field with customizable styling.

### Basic Links

```php
// Simple link
->addField('Component Name', 'reportedData.comp_name', type: FieldTypes::TEXT->value)
->withLink('https://nvd.nist.gov/search?q={value}', true) // true = new tab

// Link from another field
->addField('Component Name', 'reportedData.comp_name', type: FieldTypes::TEXT->value)
->withLinkFromField('reportedData.comp_link', true)

// Edit link
->addField('Actions', 'id', type: FieldTypes::TEXT->value)
->withEditLink('/admin/edit')
```

### Link Styling Options

```php
// Predefined styles
->withButtonLink('https://example.com/{value}')                    // Button outline
->withPrimaryButtonLink('https://example.com/{value}')             // Primary button
->withBadgeLink('https://example.com/{value}')                     // Badge style
->withExternalLink('https://example.com/{value}', 'underline')     // Always underlined

// Custom CSS classes
->withCustomLink('https://example.com/{value}', 'px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600')
->withCustomLinkFromField('reportedData.link', 'btn btn-lg btn-success shadow-lg', true)

// Add classes to predefined styles
->withButtonLink('https://example.com/{value}', false, 'shadow-xl border-2')
->withPrimaryButtonLink('/edit/{id}', false, 'animate-pulse glow-effect')
```

### Available Link Styles

| Style | Description | Example Classes |
|-------|-------------|-----------------|
| `'default'` | Standard link with hover underline | `text-primary hover:underline` |
| `'button'` | Button outline style | `btn btn-sm btn-outline` |
| `'button-primary'` | Primary button style | `btn btn-sm btn-primary` |
| `'button-secondary'` | Secondary button style | `btn btn-sm btn-secondary` |
| `'badge'` | Badge/pill style | `badge badge-primary` |
| `'underline'` | Always underlined | `underline text-primary` |
| `'link'` | DaisyUI link class | `link link-primary` |
| `'none'` | No styling, just opacity on hover | `hover:opacity-80` |
| `'custom'` | Use custom CSS classes | Your custom classes |

### Real-World Link Examples

```php
protected function getFormConfig(): FormHelper
{
    return (new FormHelper())
        ->addIdField()

        // Component as gradient button
        ->addField('Component Name', 'reportedData.comp_name', type: FieldTypes::TEXT->value)
        ->withCustomLinkFromField(
            'reportedData.comp_link',
            'bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-700 hover:to-blue-700 text-white font-bold py-2 px-4 rounded-lg shadow-lg transform transition hover:scale-105',
            true
        )

        // Status as animated badge
        ->addField('Status', 'status', type: FieldTypes::TEXT->value)
        ->withBadgeLink('/status/{id}', false, 'animate-pulse shadow-md')
        ->withConditionalStyling([...])

        // CVSS Score as danger button
        ->addField('CVSS Score', 'cvss_score', type: FieldTypes::TEXT->value)
        ->withButtonLink('https://nvd.nist.gov/calculator?score={value}', true, 'btn-error btn-sm gap-2')

        // Custom icon button
        ->addField('Actions', 'id', type: FieldTypes::TEXT->value)
        ->withCustomLink('/admin/edit/{id}', 'inline-flex items-center justify-center w-8 h-8 text-gray-400 bg-white border border-gray-300 rounded-full hover:text-gray-500 hover:bg-gray-100');
}
```

### Dynamic Link Classes

You can also pass CSS classes directly as the style:

```php
// Pass any CSS classes as the style parameter
->withLink('https://example.com/{value}', false, 'bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded')
->withLinkFromField('reportedData.link', true, 'btn btn-ghost btn-sm underline')
```

## 🎨 Conditional Styling

Apply dynamic styling based on field values for instant visual feedback.

### Simple Conditional Styling

```php
->addField('Status', 'status', type: FieldTypes::TEXT->value)
->withConditionalStyling([
    'active' => 'bg-green-500 text-white border-green-600',
    'inactive' => 'bg-red-500 text-white border-red-600',
    'pending' => 'bg-yellow-500 text-black border-yellow-600'
], 'bg-gray-200 text-gray-800') // Default style
```

### Advanced Conditional Styling

```php
->addField('CVSS Score', 'cvss_score', type: FieldTypes::NUMBER->value)
->withAdvancedStyling([
    ['operator' => 'between', 'min' => 9.0, 'max' => 10.0, 'classes' => 'bg-red-600 text-white font-bold animate-pulse'],
    ['operator' => 'between', 'min' => 7.0, 'max' => 8.9, 'classes' => 'bg-red-500 text-white'],
    ['operator' => 'between', 'min' => 4.0, 'max' => 6.9, 'classes' => 'bg-orange-500 text-white'],
    ['operator' => 'less_than', 'value' => 4.0, 'classes' => 'bg-green-500 text-white']
])
```

### Preset Styling Methods

```php
// Built-in presets for common patterns
->withStatusStyling()      // Common status values
->withCVSSStyling()        // CVSS scoring (0-10 scale)
->withSeverityStyling()    // Severity levels
->withPercentageStyling()  // Percentage-based colors
```

### Available Operators

- **Equality**: `equals`, `not_equals`
- **Comparison**: `greater_than`, `greater_than_equal`, `less_than`, `less_than_equal`
- **Range**: `between` (with min/max values)
- **String**: `contains`, `starts_with`, `ends_with`
- **Existence**: `exists`, `not_exists`

## 🎯 Row-Level Conditional Styling

Style entire table rows based on data conditions.

### Basic Row Styling

```php
return (new FormHelper())
    ->addIdField()
    ->addField('Status', 'status')
    ->addField('Priority', 'priority')

    // Style entire rows based on conditions
    ->withAdvancedRowStyling([
        [
            'field' => 'status',
            'operator' => 'equals',
            'value' => 'critical',
            'classes' => 'bg-red-50 border-red-300 border-l-4'
        ],
        [
            'field' => 'priority',
            'operator' => 'equals',
            'value' => 'high',
            'classes' => 'bg-orange-50 border-orange-200'
        ]
    ], 'bg-white hover:bg-gray-50') // Default row styling
    ->build();
```

### Row Styling Examples

```php
// Security vulnerability table
->withAdvancedRowStyling([
    // Critical vulnerabilities
    [
        'field' => 'cvss_score',
        'operator' => 'greater_than_equal',
        'value' => 9.0,
        'classes' => 'bg-red-100 border-red-400 border-l-4 shadow-sm'
    ],
    // Duplicate entries
    [
        'field' => 'status',
        'operator' => 'equals',
        'value' => 'duplicate',
        'classes' => 'bg-gray-100 border-gray-300 opacity-60'
    ],
    // Completed items
    [
        'field' => 'status',
        'operator' => 'equals',
        'value' => 'finished',
        'classes' => 'bg-green-50 border-green-200'
    ]
], 'bg-white hover:bg-gray-50')
```

## 🔍 Advanced Filtering

Comprehensive filtering with various field types.

```php
// Enable filtering on fields
->addField('Name', 'name', type: FieldTypes::TEXT->value, filterable: true)
->addField('Status', 'status', type: FieldTypes::SELECT->value, filterable: true,
    options: [
        'select_options' => [
            ['value' => 'active', 'label' => 'Active'],
            ['value' => 'inactive', 'label' => 'Inactive']
        ]
    ]
)
->addField('Created At', 'created_at', type: FieldTypes::TIMESTAMP->value, filterable: true)
->addField('Is Active', 'is_active', type: FieldTypes::BOOLEAN->value, filterable: true)
```

### Filter Types

- **Text Filters**: Contains, exact match, starts with
- **Date Range Filters**: From/To with quick presets (Today, 7 days, 30 days, 90 days)
- **Boolean Filters**: True/False dropdown
- **Select Filters**: Dropdown with predefined options
- **Number Filters**: Numeric value filtering
- **Model Search**: Searchable dropdown with related models

## 🔗 Relationship Support

Builder automatically detects and loads relationships from field keys.

### Basic Relationships

```php
// Automatically loads 'reportedData' relationship
->addField('Component Name', 'reportedData.comp_name', type: FieldTypes::TEXT->value)

// Nested relationships
->addField('User Profile', 'user.profile.name', type: FieldTypes::TEXT->value)
```

### Fallback Relationships

```php
// Try first field, fall back to second if empty
->addField('Version', 'reportedTempData.affected_in|reportedData.vuln_version', type: FieldTypes::TEXT->value)

// Multiple fallbacks
->addField('Contact', 'user.email|user.profile.email|contact.email', type: FieldTypes::TEXT->value)
```

## 📋 Field Types

Supported field types:

- `TEXT` - Text input
- `EMAIL` - Email input
- `PASSWORD` - Password input
- `DATE` - Date picker
- `TIMESTAMP` - Datetime picker
- `SELECT` - Dropdown select
- `BOOLEAN` - Toggle switch
- `MEDIA` - Media upload
- `EDITOR` - Rich text editor
- `NUMBER` - Number input
- `MODEL_SEARCH` - Model relationship search
- `PIVOT_MODEL` - Many-to-many relationship
- `CHIPS` - Tags/chips input
- `ICON` - Icon selector

## 🎬 Complete Example

```php
<?php

namespace App\Controllers;

use Inertia\Inertia;
use App\Models\Vulnerability;
use App\Http\Controllers\Controller;
use Mariojgt\Builder\Enums\FieldTypes;
use Mariojgt\Builder\Helpers\FormHelper;

class VulnerabilityController extends Controller
{
    public function index()
    {
        $form = new FormHelper();
        $formConfig = $form
            ->addIdField()
            ->tab('Vulnerability Details')

            // Component with custom button link
            ->addField('Component Name', 'reportedData.comp_name', type: FieldTypes::TEXT->value)
            ->withPrimaryButtonLinkFromField('reportedData.comp_link', true, 'shadow-lg')

            // Status with conditional styling and badge link
            ->addField('Status', 'status', type: FieldTypes::TEXT->value, filterable: true)
            ->withConditionalStyling([
                'duplicate' => 'bg-red-500 text-white border-red-600',
                'finished' => 'bg-green-500 text-white border-green-600',
                'pending' => 'bg-yellow-500 text-black border-yellow-600'
            ])
            ->withBadgeLink('/status/{id}')

            // CVSS Score with advanced styling and external link
            ->addField('CVSS Score', 'cvss_score', type: FieldTypes::NUMBER->value)
            ->withCVSSStyling()
            ->withButtonLink('https://nvd.nist.gov/calculator?score={value}', true, 'btn-sm btn-outline-primary')

            // Vulnerability type with custom styling
            ->addField('Type', 'vuln_type', type: FieldTypes::TEXT->value)
            ->withAdvancedStyling([
                ['operator' => 'contains', 'value' => 'sql injection', 'classes' => 'bg-red-600 text-white font-bold'],
                ['operator' => 'contains', 'value' => 'xss', 'classes' => 'bg-red-500 text-white'],
                ['operator' => 'contains', 'value' => 'csrf', 'classes' => 'bg-orange-500 text-white']
            ])

            // Created date with filtering
            ->addField('Created At', 'created_at', type: FieldTypes::TIMESTAMP->value, filterable: true)

            // Row-level styling for critical items
            ->withAdvancedRowStyling([
                [
                    'field' => 'cvss_score',
                    'operator' => 'greater_than_equal',
                    'value' => 9.0,
                    'classes' => 'bg-red-50 border-red-300 border-l-4 shadow-md'
                ],
                [
                    'field' => 'status',
                    'operator' => 'equals',
                    'value' => 'duplicate',
                    'classes' => 'bg-gray-100 border-gray-300 opacity-60'
                ]
            ], 'bg-white hover:bg-gray-50')

            ->setEndpoints(
                listEndpoint: route('admin.api.generic.table'),
                deleteEndpoint: route('admin.api.generic.table.delete'),
                createEndpoint: route('admin.api.generic.table.create'),
                editEndpoint: route('admin.api.generic.table.update')
            )
            ->setModel(Vulnerability::class)
            ->build();

        return Inertia::render('Admin/Vulnerabilities/Index', [
            'title' => 'Vulnerabilities',
            'table_name' => 'vulnerabilities',
            ...$formConfig
        ]);
    }
}
```

## 🎯 Best Practices

### When to Use Links
✅ **Good for:**
- External resources (NVD, vendor sites)
- Related records (view details, edit)
- Documentation links
- Quick actions

❌ **Avoid for:**
- Long descriptive text
- Non-actionable content
- Too many links in one table

### When to Use Styling
✅ **Good for:**
- Status indicators
- Priority levels
- Severity scores
- Boolean values
- Progress indicators

❌ **Avoid for:**
- Names and titles
- Long descriptions
- Generic text content

### Performance Tips
1. Use links and styling where they add real value
2. Test with real data to ensure proper functionality
3. Don't overwhelm users with too many colors/links
4. Use preset methods for common patterns
5. Optimize model relationships with proper indexing

## 🛠️ Node Dependencies

```json
{
  "@headlessui/vue": "^1.7.23",
  "@inertiajs/vue3": "^2.0.3",
  "@mariojgt/masterui": "^0.5.6",
  "@mariojgt/wind-notify": "^1.0.3",
  "lucide-vue-next": "^0.474.0"
}
```

## ⚙️ Vite Configuration

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
            '@components': path.resolve(__dirname, './resources/js/components'),
            '@builder': '/resources/vendor/Builder/Table',
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

## 🔧 Troubleshooting

### Links Not Working
- Ensure backend provides `{field}_link` data
- Check link URLs are properly formatted
- Verify target (`_blank` vs `_self`) is correct

### Styling Not Applied
- Ensure CSS classes are available in your build
- Check that values match exactly (case-sensitive)
- Use browser dev tools to inspect applied classes

### Relationships Not Loading
- Verify relationship exists in your model
- Check relationship name matches field key exactly
- Use dot notation for nested relationships

## 📄 License

MIT License - feel free to use in your projects!

## 🌟 Support

If this package helped you, please give it a star on GitHub!
