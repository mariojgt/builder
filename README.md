# Laravel Builder

A powerful Laravel package that streamlines CRUD operations with a dynamic form builder and robust API integration. Build complex admin interfaces in minutes.

## Features

- **Dynamic Form Builder** with multiple field types
- **Built-in Laravel API** integration with automatic relationship detection
- **Advanced Conditional Styling** for dynamic visual feedback
- **Row-Level Conditional Styling** for entire table rows
- **Interactive Links** with customizable styling
- **Advanced Filtering** with complex query operations (whereNotIn, whereBetween, etc.)
- **Default Filters** applied automatically on table load
- **Chained Relationships** with unlimited depth support
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

### 2. Basic Controller with Advanced Filters

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
        $formConfig = (new FormHelper())
            ->addIdField()
            ->addField('Title', 'title', type: FieldTypes::TEXT->value, filterable: true)
            ->addField('Status', 'status', type: FieldTypes::TEXT->value, filterable: true)
            ->addField('CVSS Score', 'cvss_score', type: FieldTypes::NUMBER->value, filterable: true)
            ->addField('Platform', 'product.platform.name', type: FieldTypes::TEXT->value, filterable: true)

            // ✨ NEW: Advanced Filters (always applied)
            ->withExcludeStatuses('status', ['unknown', 'unvalidated', 'incomplete', 'published', 'rejected', 'finished'])
            ->withHighSeverityOnly('cvss_score', 4.0)      // Only show CVSS >= 4.0
            ->withRecentItems('created_at', 90)            // Only last 90 days

            // ✨ NEW: Default Filters (user can change via UI)
            ->withDefaultFilter('product.platform.name', 'WordPress')
            ->withDefaultFilter('cvss_score', 4.8)

            // Only items in active disclosure window
            ->withBetweenFilter('disclosure_date',
                now()->subDays(90)->format('Y-m-d'),  // 90 days ago
                now()->addDays(30)->format('Y-m-d')   // 30 days from now
            )

            // Critical items: disclosed in last 30 days or upcoming
            ->withRelationshipFilter('critical_timeline', function ($query) {
                $query->where(function ($criticalQuery) {
                    $criticalQuery->where('cvssbase', '>=', 9.0)
                                ->where('disclosure_date', '>=', now()->subDays(30)->format('Y-m-d'));
                });
            })

            ->withDateRangeFilter('created_at', '2024-01-01', '2024-12-31')
            ->withCreatedAtFilter('2024-06-01', '2024-12-31')

            // ✨ Specific year filtering
            ->withYear('created_at', 2024)             // Only 2024 items
            ->withYear('disclosure_date', 2023)        // Only 2023 disclosures;

            ->withConditionalStyling([
                'active' => 'bg-green-500 text-white',
                'inactive' => 'bg-red-500 text-white'
            ])
            ->setEndpoints(
                listEndpoint: route('admin.api.generic.table'),
                deleteEndpoint: route('admin.api.generic.table.delete'),
                createEndpoint: route('admin.api.generic.table.create'),
                editEndpoint: route('admin.api.generic.table.update')
            )
            ->setModel(Vulnerability::class)
            ->withScope('notPublished') // if you have a scope in your model
            ->build();

        return Inertia::render('Admin/Vulnerabilities/Index', [
            'title' => 'Vulnerabilities',
            'subheader' => 'Manage all vulnerabilities in the system',
            ...$formConfig,
        ]);
    }
}
```

### 3. Updated Vue Component

```vue
<template>
    <AppLayout>
        <Table
            :columns="page.props.columns"
            :model="page.props.model"
            :endpoint="page.props.endpoint"
            :endpoint-delete="page.props.endpointDelete"
            :endpoint-create="page.props.endpointCreate"
            :endpoint-edit="page.props.endpointEdit"
            :table-title="page.props.title"
            :subheader="page.props.subheader"
            :permission="page.props.permission"
            :defaultIdKey="page.props.defaultIdKey"
            :custom_edit_route="page.props.custom_edit_route"
            :custom_edit_route_field="page.props.custom_edit_route_field"
            :custom_create_route="page.props.custom_create_route"
            :custom_point_route="page.props.custom_point_route"
            :custom_action_name="page.props.custom_action_name"
            :row-styling="page.props.rowStyling"
            :default-filters="page.props.defaultFilters"
            :advanced-filters="page.props.advancedFilters"
            :disableDelete="page.props.disableDelete"
            :modelScopes="page.props.modelScopes"
            :customLinks="page.props.customLinks"
            @tableDataLoaded="handleTableData"
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
    modelScopes: { type: Object, default: () => ({}) },
    endpointDelete: { type: String, default: '' },
    endpointCreate: { type: String, default: '' },
    endpointEdit: { type: String, default: '' },
    permission: { type: String, default: '' },
    title: { type: String, default: '' },
    subheader: { type: String, default: '' },
    defaultIdKey: { type: String, default: '' },
    custom_edit_route: { type: String, default: '' },
    custom_point_route: { type: String, default: '' },
    custom_action_name: { type: String, default: '' },
    custom_create_route: { type: String, default: '' },
    rowStyling: { type: Object, default: () => ({}) },
    disableDelete: { type: Boolean, default: false }, // Disable delete action
    defaultFilters: { type: Object, default: () => ({}) },  // Simple key-value filters
    advancedFilters: { type: Array, default: () => [] },   // Complex query filters
});
</script>
```

## 🚀 Advanced Filtering System

The new advanced filtering system provides powerful query capabilities that are automatically applied to your tables.

### Simple Default Filters

Default filters are applied automatically but can be modified by users through the UI:

```php
return (new FormHelper())
    ->addIdField()
    ->addField('Title', 'title', filterable: true)
    ->addField('Status', 'status', filterable: true)
    ->addField('Platform', 'product.platform.name', filterable: true)

    // ✨ Simple default filters (users can change these)
    ->withDefaultFilter('status', 'active')
    ->withPlatformFilter('WordPress')              // Helper method
    ->withPublishedFilter(true)                   // Helper method
    ->withPatchedFilter(false)                    // Helper method
    ->build();
```

### Advanced Query Filters

Advanced filters are part of the table configuration and are always applied:

```php
return (new FormHelper())
    ->addIdField()
    ->addField('Title', 'title', filterable: true)
    ->addField('Status', 'status', filterable: true)
    ->addField('CVSS Score', 'cvss_score', filterable: true)
    ->addField('Created At', 'created_at', filterable: true)

    // ✨ Advanced filters (always applied, users cannot change)

    // whereNotIn - exclude specific statuses
    ->withExcludeStatuses('status', ['unknown', 'unvalidated', 'incomplete', 'published', 'rejected', 'finished'])

    // whereIn - include only specific values
    ->withInFilter('status', ['active', 'pending', 'in_progress'])

    // whereBetween - range filtering
    ->withBetweenFilter('cvss_score', 4.0, 8.9)

    // where with operators
    ->withWhereFilter('cvss_score', '>=', 7.0)
    ->withWhereFilter('created_at', '>', '2024-01-01')

    // whereNull / whereNotNull
    ->withNotNullFilter('cvss_score')             // Must have CVSS score
    ->withNullFilter('patched_at')                // Only unpatched items

    // Date-based filtering
    ->withRecentItems('created_at', 30)           // Last 30 days
    ->withYear('created_at', 2024)                // From 2024 only

    ->build();
```

### Advanced Filter Methods

| Method | SQL Equivalent | Description |
|--------|----------------|-------------|
| `withNotInFilter($field, $values)` | `WHERE field NOT IN (...)` | Exclude specific values |
| `withInFilter($field, $values)` | `WHERE field IN (...)` | Include only specific values |
| `withBetweenFilter($field, $min, $max)` | `WHERE field BETWEEN min AND max` | Range filtering |
| `withWhereFilter($field, $operator, $value)` | `WHERE field operator value` | Custom operators |
| `withNullFilter($field)` | `WHERE field IS NULL` | Null values only |
| `withNotNullFilter($field)` | `WHERE field IS NOT NULL` | Non-null values only |
| `withLikeFilter($field, $value)` | `WHERE field LIKE '%value%'` | Pattern matching |
| `withRecentItems($field, $days)` | `WHERE field >= DATE_SUB(NOW(), INTERVAL days DAY)` | Recent items |
| `withYear($field, $year)` | `WHERE YEAR(field) = year` | Year-based filtering |

### Helper Methods for Common Patterns

```php
// Status filtering helpers
->withExcludeStatuses('status', ['finished', 'rejected'])
->withIncludeStatuses('status', ['active', 'pending'])

// CVSS/Security helpers
->withHighSeverityOnly('cvss_score', 7.0)        // cvss_score >= 7.0
->withCvssRange('cvss_score', 4.0, 6.9)         // Medium severity

// Date helpers
->withRecentItems('created_at', 30)              // Last 30 days
->withCreatedAtFilter('2024-01-01', '2024-12-31') // Date range

// Platform helpers
->withPlatformFilter('WordPress')                // product.platform.name = 'WordPress'
->withPublishedFilter(true)                      // published = true
->withPatchedFilter(false)                       // is_patched = false

// Liker filters
// ✨ WHERE title LIKE '%mario%'
->withLikeFilter('title', 'mario')
// ✨ WHERE description LIKE '%sql injection%'
->withLikeFilter('description', 'sql injection')
// ✨ WHERE author.name LIKE '%john%' (works with relationships!)
->withLikeFilter('author.name', 'john')
```

## 🔗 Chained Relationships

Builder automatically supports unlimited depth chained relationships:

### Basic Chained Relationships

```php
return (new FormHelper())
    ->addIdField()

    // 2-level chain: vulnerability -> product -> name
    ->addField('Product', 'product.name', filterable: true)

    // 3-level chain: vulnerability -> product -> platform -> name
    ->addField('Platform', 'product.platform.name', filterable: true)

    // 4-level chain: vulnerability -> product -> platform -> company -> name
    ->addField('Company', 'product.platform.company.name', filterable: true)

    // 5-level chain: vulnerability -> researcher -> user -> profile -> company -> name
    ->addField('Researcher Company', 'researcher.user.profile.company.name', filterable: true)

    // Filter on chained relationships
    ->withDefaultFilter('product.platform.name', 'WordPress')
    ->withNotInFilter('product.platform.company.status', ['inactive', 'suspended'])
    ->withWhereFilter('researcher.user.profile.reputation_score', '>=', 80)

    ->build();
```

### Fallback Chained Relationships

```php
return (new FormHelper())
    ->addIdField()

    // Try multiple relationship paths (fallback with |)
    ->addField('Contact Email',
        'product.platform.company.security_email|product.platform.company.admin_email|product.platform.company.main_email',
        filterable: true
    )

    // Complex fallback
    ->addField('Version Info',
        'product.versions.latest.detailed_info|product.versions.latest.simple_info|Unknown Version'
    )

    ->build();
```

### Complex Relationship Filtering

```php
return (new FormHelper())
    ->addIdField()
    ->addField('Title', 'title')
    ->addField('Company', 'product.platform.company.name')

    // ✨ Complex relationship filtering with callbacks
    ->withRelationshipFilter('product.platform.company.employees', function ($query) {
        $query->where('role', 'security_engineer')
              ->where('years_experience', '>=', 3)
              ->whereHas('certifications', function ($certQuery) {
                  $certQuery->whereIn('name', ['CISSP', 'CEH', 'OSCP']);
              });
    })

    // ✨ Multi-level relationship conditions
    ->withRelationshipFilter('product.platform.category', function ($query) {
        $query->where('is_security_critical', true)
              ->whereHas('parent', function ($parentQuery) {
                  $parentQuery->where('name', 'Web Applications')
                            ->where('risk_level', 'high');
              });
    })

    ->build();
```

## 🎯 Real-World Advanced Examples

### Security Vulnerability Dashboard

```php
public function vulnerabilityDashboard()
{
    $formConfig = (new FormHelper())
        ->addIdField()
        ->tab('Vulnerability Details')

        // Basic fields with chained relationships
        ->addField('Title', 'title', type: FieldTypes::TEXT->value, filterable: true)
        ->addField('CVSS Score', 'cvss_score', type: FieldTypes::NUMBER->value, filterable: true)
        ->addField('Status', 'status', type: FieldTypes::TEXT->value, filterable: true)
        ->addField('Platform', 'product.platform.name', type: FieldTypes::TEXT->value, filterable: true)
        ->addField('Company', 'product.platform.company.name', type: FieldTypes::TEXT->value, filterable: true)
        ->addField('Researcher', 'reportedBy.researcher.user.name', type: FieldTypes::TEXT->value, filterable: true)
        ->addField('Country', 'product.platform.company.headquarters.country', type: FieldTypes::TEXT->value, filterable: true)
        ->addField('Created At', 'created_at', type: FieldTypes::TIMESTAMP->value, filterable: true)

        // ✨ COMPREHENSIVE ADVANCED FILTERING

        // Exclude non-actionable statuses (always applied)
        ->withExcludeStatuses('status', [
            'unknown', 'unvalidated', 'incomplete',
            'published', 'rejected', 'finished', 'duplicate'
        ])

        // Only medium+ severity vulnerabilities
        ->withHighSeverityOnly('cvss_score', 4.0)

        // Only recent vulnerabilities (last 90 days)
        ->withRecentItems('created_at', 90)

        // Only from verified companies
        ->withRelationshipFilter('product.platform.company', function ($query) {
            $query->where('is_verified', true)
                  ->where('status', 'active')
                  ->whereNotNull('security_contact_email');
        })

        // Only from specific countries
        ->withInFilter('product.platform.company.headquarters.country', [
            'US', 'CA', 'GB', 'DE', 'FR', 'AU'
        ])

        // Exclude certain platforms
        ->withNotInFilter('product.platform.name', ['Unknown', 'Legacy', 'Deprecated'])

        // Only high-reputation researchers
        ->withWhereFilter('reportedBy.researcher.reputation_score', '>=', 75)

        // ✨ DEFAULT FILTERS (users can change these)
        ->withDefaultFilter('product.platform.name', 'WordPress')  // Default to WordPress
        ->withDefaultFilter('status', 'pending')                   // Default to pending status

        // ✨ CONDITIONAL STYLING
        ->withConditionalStyling([
            'critical' => 'bg-red-600 text-white border-red-700 animate-pulse',
            'pending' => 'bg-yellow-500 text-black border-yellow-600',
            'active' => 'bg-blue-500 text-white border-blue-600'
        ])

        // ✨ ROW-LEVEL STYLING
        ->withAdvancedRowStyling([
            [
                'field' => 'cvss_score',
                'operator' => 'greater_than_equal',
                'value' => 9.0,
                'classes' => 'bg-red-50 border-red-300 border-l-4 shadow-lg'
            ],
            [
                'field' => 'status',
                'operator' => 'equals',
                'value' => 'critical',
                'classes' => 'bg-red-100 border-red-400 animate-pulse'
            ]
        ], 'bg-white hover:bg-gray-50')

        ->setEndpointsFromRoutes('admin.vulnerabilities')
        ->setModel(Vulnerability::class)
        ->setPermissions('admin')
        ->build();

    return Inertia::render('Admin/Vulnerabilities/Dashboard', [
        'tableConfig' => $formConfig
    ]);
}
```

### User Management with Complex Filters

```php
public function userManagement()
{
    $formConfig = (new FormHelper())
        ->addIdField()
        ->addField('Name', 'name', type: FieldTypes::TEXT->value, filterable: true)
        ->addField('Email', 'email', type: FieldTypes::EMAIL->value, filterable: true)
        ->addField('Department', 'profile.department.name', type: FieldTypes::TEXT->value, filterable: true)
        ->addField('Manager', 'profile.manager.user.name', type: FieldTypes::TEXT->value, filterable: true)
        ->addField('Company', 'profile.company.name', type: FieldTypes::TEXT->value, filterable: true)
        ->addField('Last Login', 'last_login_at', type: FieldTypes::TIMESTAMP->value, filterable: true)

        // ✨ Advanced user filtering
        ->withNotNullFilter('email_verified_at')           // Only verified users
        ->withWhereFilter('profile.status', '!=', 'suspended')  // No suspended users
        ->withRecentItems('last_login_at', 30)             // Active in last 30 days

        // Department-based filtering
        ->withNotInFilter('profile.department.name', ['Temp', 'Contractor', 'Intern'])

        // Company requirements
        ->withRelationshipFilter('profile.company', function ($query) {
            $query->where('is_active', true)
                  ->where('employee_count', '>=', 10)
                  ->whereHas('subscriptions', function ($subQuery) {
                      $subQuery->where('status', 'active')
                             ->where('plan', '!=', 'trial');
                  });
        })

        // Single
        ->withAdvancedFilter('id', 'orderBy', null, ['direction' => 'desc'])

        // ✅ Multiple sorts:
        ->withAdvancedFilter('multi_sort', 'orderByMultiple', [
            ['column' => 'patch_priority', 'direction' => 'desc'],
            ['column' => 'cvssbase', 'direction' => 'desc'],
            ['column' => 'id', 'direction' => 'desc']
        ])

        // Default filters
        ->withDefaultFilter('profile.department.name', 'Engineering')

        ->setEndpointsFromRoutes('admin.users')
        ->setModel(User::class)
        ->build();

    return Inertia::render('Admin/Users/Index', [
        'tableConfig' => $formConfig
    ]);
}
```

## 🔧 Filter Processing Order

The system processes filters in this order:

1. **Advanced Filters** (from FormHelper configuration - always applied)
2. **Default Filters** (from FormHelper configuration - user can modify)
3. **User Filters** (applied by users through the UI)
4. **Search** (global search across columns)
5. **Sorting** (column sorting)

This ensures that your advanced filters are always active while still allowing users to add their own filters.

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

or

->addField('CVSS Score', 'cvss_score', type: FieldTypes::NUMBER->value)
->withAdvancedStyling([
    [
        'operator' => 'greater_than_equal',
        'value' => 9.0,
        'classes' => 'bg-red-600 text-white font-bold',
        'icon' => 'skull',
        'tooltip' => 'Critical Risk (9.0-10.0) - Exploit likely causes significant impact'
    ],
    [
        'operator' => 'between',
        'min' => 7.0,
        'max' => 8.9,
        'classes' => 'bg-red-500 text-white',
        'icon' => 'flame',
        'tooltip' => 'High Risk (7.0-8.9) - Exploitation more difficult but serious impact'
    ],
    [
        'operator' => 'between',
        'min' => 4.0,
        'max' => 6.9,
        'classes' => 'bg-yellow-500 text-white',
        'icon' => 'alerttriangle',
        'tooltip' => 'Medium Risk (4.0-6.9) - Moderate impact, monitor for patches'
    ],
    [
        'operator' => 'between',
        'min' => 0.1,
        'max' => 3.9,
        'classes' => 'bg-blue-500 text-white',
        'icon' => 'info',
        'tooltip' => 'Low Risk (0.1-3.9) - Minor impact, low priority'
    ],
    [
        'operator' => 'less_than_equal',
        'value' => 0.0,
        'classes' => 'bg-gray-500 text-white',
        'icon' => 'ampersand',
        'tooltip' => 'No Score (0.0) - Not yet assessed or informational only'
    ],
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

### Advanced Filters Not Working
- Ensure your model relationships are properly defined
- Check that field names match exactly (case-sensitive)
- Verify advanced filters are passed in the `tableConfig`

### Chained Relationships Not Loading
- Verify each level of the relationship chain exists
- Check relationship names match your model methods exactly
- Use Laravel Telescope to debug relationship queries

### Performance with Complex Filters
- Add proper database indexes for filtered fields
- Use `select()` to limit loaded columns when possible
- Consider eager loading for frequently accessed relationships

## 🎯 Best Practices

### Filter Strategy
✅ **Use Advanced Filters for:**
- Business logic that should always apply
- Security restrictions
- Data quality filters (exclude invalid records)
- Performance optimizations (limit result sets)

✅ **Use Default Filters for:**
- Common user preferences
- Sensible defaults that users might want to change
- Quick-start configurations

### Performance Tips
1. Add database indexes for frequently filtered fields
2. Use advanced filters to limit result sets early
3. Avoid too many chained relationships in one query
4. Use relationship callbacks for complex filtering
5. Test with realistic data volumes

## 📄 License

MIT License - feel free to use in your projects!

## 🌟 Support

If this package helped you, please give it a star on GitHub!
