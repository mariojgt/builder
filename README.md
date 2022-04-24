![image info](https://raw.githubusercontent.com/mariojgt/builder/main/Publish/Art/logo.png)


# Builder

Laravel package to quick build generict crud operations.

# Features

-   [ ] Ready to use datatabel api integrated with laravel.

# Requirements
-   [ ] laravel.
- [ ] tailwind.
- [ ] daisy ui.
- [ ] inersia js.


### First option via composer

1. composer require mariojgt/builder
2. php artisan install::builder

This will copy the resource assets, run migrations and copy over some config file we need to use;

## How to use

1: Go to the routes file and you first need to add the following line:
```php
    // Table api controller
    Route::controller(TableBuilderApiController::class)->group(function () {
        Route::post('/admin/api/generic/table', 'index')->name('admin.api.generic.table');
        Route::post('/admin/api/generic/table/create', 'store')->name('admin.api.generic.table.create');
        Route::post('/admin/api/generic/table/update', 'update')->name('admin.api.generic.table.update');
        Route::post('/admin/api/generic/table/delete', 'delete')->name('admin.api.generic.table.delete');
    });
```
2: in you controller you need the following array
```php
        // Table columns
        $columns = [
            [
                'label'     => 'Id',    // Display name
                'key'       => 'id',    // Table column key
                'sortable'  => true,    // Can be use in the filter
                'canCreate' => false,   // Can be use in the create form
                'canEdit'   => false,   // Can be use in the edit form
            ],
            [
                'label'     => 'Name',   // Display name
                'key'       => 'name',   // Table column key
                'sortable'  => true,     // Can be use in the filter
                'canCreate' => true,     // Can be use in the create form
                'canEdit'   => true,     // Can be use in the edit form
                'type'      => 'text',   // Type text,email,password,date,timestamp
            ],
            [
                'label'     => 'Guard',
                'key'       => 'guard_name',
                'sortable'  => true,
                'canCreate' => true,
                'canEdit'   => true,
                'type'      => 'text',
            ],
            [
                'label'     => 'Created At',
                'key'       => 'created_at',
                'sortable'  => false,
                'canCreate' => false,
                'canEdit'   => true,
                'type'      => 'date',
            ],
            [
                'label'     => 'Updated At',
                'key'       => 'updated_at',
                'sortable'  => false,
                'canCreate' => false,
                'canEdit'   => true,
                'type'      => 'timestamp',
            ],
        ];

        return Inertia::render('BackEnd/Permissions/Index', [
            'title' => 'Permissions | Roles',
            // Required for the generic table api
            'endpoint'       => route('admin.api.generic.table'), // Index table endpoint
            'endpointDelete' => route('admin.api.generic.table.delete'), // Delete table endpoint
            'endpointCreate' => route('admin.api.generic.table.create'), // Create table endpoint
            'endpointEdit'   => route('admin.api.generic.table.update'), // Edit table endpoint
            'columns'        => $columns, // Table columns
            'model'          => encrypt(Role::class), // Model name encrypted
        ]);
```
