<?php

namespace Mariojgt\Builder\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Mariojgt\Builder\Helpers\BuilderHelper;

class TableBuilderApiController extends Controller
{
    /**
     * Handle data display to the table with AUTOMATIC relationship detection, custom attributes support,
     * and advanced filters support.
     *
     * @return array
     */
    public function index(Request $request)
    {
        $request->validate([
            'model' => 'required',
            'columns' => 'required',
        ]);

        $builderHelper = new BuilderHelper;
        if (! empty($request->permission)) {
            $builderHelper->permissionCheck($request, 'index');
        }

        $model = decrypt($request->model);
        $model = new $model;
        $rawColumns = collect($request->columns);

        // 🚀 AUTO-DETECT RELATIONSHIPS from dot notation
        $relationships = $this->autoDetectRelationships($rawColumns);

        // 🚀 AUTO-DETECT CUSTOM ATTRIBUTES (accessors) to exclude from queries
        $customAttributes = $this->autoDetectCustomAttributes($model, $rawColumns);

        // Get base columns (no relationships, no custom attributes)
        $columns = $this->getBaseColumns($rawColumns, $customAttributes);

        // Add timestamps if model has them
        $hasTimestamps = $model->timestamps;
        if ($hasTimestamps && ! $columns->contains('updated_at')) {
            $columns->push('updated_at');
        }

        // 🚀 SMART SELECT: Add foreign keys for relationships
        $selectColumns = $this->getSmartSelectColumns($rawColumns, $relationships, $customAttributes);

        // Start query with AUTO-LOADED relationships
        $query = $model->query();
        if (! empty($relationships)) {
            $query->with($relationships);
        }

        // ✨ NEW: Apply advanced filters FIRST (these are part of the table configuration)
        if ($request->has('advancedFilters') && ! empty($request->advancedFilters)) {
            $this->applyAdvancedFilters($query, $request->advancedFilters, $rawColumns, $customAttributes);
        }

        // Handle regular filters (with relationship support, excluding custom attributes)
        if ($request->has('filters') && ! empty($request->filters)) {
            $this->applyFilters($query, $request->filters, $rawColumns, $customAttributes);
        }

        // Handle search (with relationship support, excluding custom attributes)
        if ($request->has('search') && ! empty($request->search)) {
            $this->applySearch($query, $request->search, $rawColumns, $customAttributes);
        }

        // Handle sorting with relationship support (excluding custom attributes)
        if (! empty($request->sort)) {
            $this->applySorting($query, $request->sort, $request->direction ?? 'asc', $rawColumns, $customAttributes);
        }

        // Execute query with smart column selection
        $modelPaginated = $query->select($selectColumns)->paginate($request->perPage ?? 10);

        // Process data (with relationship values and custom attributes)
        $data = $builderHelper->columnReplacements($modelPaginated, $rawColumns);

        $data = $this->pruneUnrequestedColumns($data, $rawColumns);

        return [
            'data' => $data,
            'current_page' => $modelPaginated->currentPage(),
            'first_page_url' => $modelPaginated->url(1),
            'from' => $modelPaginated->firstItem(),
            'last_page' => $modelPaginated->lastPage(),
            'last_page_url' => $modelPaginated->url($modelPaginated->lastPage()),
            'links' => $modelPaginated->links(),
            'next_page_url' => $modelPaginated->nextPageUrl(),
            'path' => $modelPaginated->path(),
            'per_page' => $modelPaginated->perPage(),
            'prev_page_url' => $modelPaginated->previousPageUrl(),
            'to' => $modelPaginated->lastItem(),
            'total' => $modelPaginated->total(),
        ];
    }

    /**
     * Helper to prune a collection of data items to include only explicitly requested columns.
     * This iterates through the items and removes fields not present in $rawColumns,
     * maintaining dot-notation as flat keys and handling fallback values for both
     * the main column key and the url_field key.
     *
     * @param  \Illuminate\Support\Collection  $collection  The collection of data items (after columnReplacements)
     * @param  \Illuminate\Support\Collection  $rawColumns  The collection of raw column definitions from the request
     * @return \Illuminate\Support\Collection A new collection with pruned data
     */
    private function pruneUnrequestedColumns($collection, $rawColumns)
    {
        $prunedCollection = collect();

        foreach ($collection as $item) {
            $prunedItem = [];

            foreach ($rawColumns as $columnDefinition) {
                // --- Handle the main column key (e.g., 'reportedData.comp_name' or 'reportedTempData.affected_in|reportedData.vuln_version') ---
                $originalKey = $columnDefinition['key'];
                $resolvedValue = null;
                $outputKey = $originalKey; // The output JSON key must be the exact original key

                // Resolve the VALUE using fallback logic if 'key' contains a pipe
                if (strpos($originalKey, '|') !== false) {
                    $keys = explode('|', $originalKey);
                    $firstKey = trim($keys[0]);
                    $fallbackKey = trim($keys[1]);

                    $resolvedValue = data_get($item, $firstKey);
                    if (is_null($resolvedValue) || (is_string($resolvedValue) && trim($resolvedValue) === '')) {
                        $resolvedValue = data_get($item, $fallbackKey);
                    }
                } else {
                    $resolvedValue = data_get($item, $originalKey);
                }
                $prunedItem[$outputKey] = $resolvedValue;

                // --- NEW: Handle the 'url_field' if it exists in the 'link' definition ---
                if (isset($columnDefinition['link']['url_field'])) {
                    $urlFieldKey = $columnDefinition['link']['url_field'];
                    $urlFieldValue = null;
                    $urlOutputKey = $urlFieldKey; // The output JSON key for url_field must be the exact url_field string

                    // Resolve the VALUE for url_field using fallback logic if it contains a pipe
                    if (strpos($urlFieldKey, '|') !== false) {
                        $urlKeys = explode('|', $urlFieldKey);
                        $firstUrlKey = trim($urlKeys[0]);
                        $fallbackUrlKey = trim($urlKeys[1]);

                        $urlFieldValue = data_get($item, $firstUrlKey);
                        if (is_null($urlFieldValue) || (is_string($urlFieldValue) && trim($urlFieldValue) === '')) {
                            $urlFieldValue = data_get($item, $fallbackUrlKey);
                        }
                    } else {
                        $urlFieldValue = data_get($item, $urlFieldKey);
                    }
                    // TODO: IMPROVE THIS LOGIC
                    $formattedKey = $columnDefinition['key'].'_link';
                    $prunedItem[$formattedKey] = [
                        'url' => $urlFieldValue,
                        'target' => $columnDefinition['link']['target'] ?? '_blank',
                        'style' => $columnDefinition['link']['style'] ?? 'default',
                    ];
                }
            }
            $prunedCollection->push($prunedItem);
        }

        return $prunedCollection;
    }

    /**
     * ✨ NEW: Apply advanced filters with complex operators
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  array  $advancedFilters
     * @param  \Illuminate\Support\Collection  $rawColumns
     * @param  array  $customAttributes
     * @return void
     */
    private function applyAdvancedFilters($query, $advancedFilters, $rawColumns, $customAttributes = [])
    {
        foreach ($advancedFilters as $filter) {
            $field = $filter['field'];
            $operator = $filter['operator'];
            $value = $filter['value'] ?? null;
            $options = $filter['options'] ?? [];

            // Skip custom attributes - they can't be filtered in the database
            if (in_array($field, $customAttributes)) {
                \Log::info("Skipping advanced filter for custom attribute: {$field}");

                continue;
            }

            try {
                // Handle relationship filters (field contains dots)
                if (strpos($field, '.') !== false) {
                    $this->applyAdvancedRelationshipFilter($query, $field, $operator, $value, $options);
                } else {
                    // Handle direct model field filters
                    $this->applyAdvancedDirectFilter($query, $field, $operator, $value, $options);
                }
            } catch (\Exception $e) {
                \Log::warning("Advanced filter failed for field '{$field}' with operator '{$operator}': ".$e->getMessage());
            }
        }
    }

    /**
     * ✨ NEW: Apply advanced filters on direct model fields
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $field
     * @param  string  $operator
     * @param  mixed  $value
     * @param  array  $options
     * @return void
     */
    private function applyAdvancedDirectFilter($query, $field, $operator, $value, $options)
    {
        switch ($operator) {
            case 'whereIn':
                if (is_array($value) && ! empty($value)) {
                    $query->whereIn($field, $value);
                }
                break;

            case 'whereNotIn':
                if (is_array($value) && ! empty($value)) {
                    $query->whereNotIn($field, $value);
                }
                break;

            case 'whereBetween':
                if (is_array($value) && count($value) >= 2) {
                    $query->whereBetween($field, [$value[0], $value[1]]);
                }
                break;

            case 'whereNotBetween':
                if (is_array($value) && count($value) >= 2) {
                    $query->whereNotBetween($field, [$value[0], $value[1]]);
                }
                break;

            case 'whereNull':
                $query->whereNull($field);
                break;

            case 'whereNotNull':
                $query->whereNotNull($field);
                break;

            case 'where':
                $sqlOperator = $options['operator'] ?? '=';
                if ($value !== null) {
                    $query->where($field, $sqlOperator, $value);
                }
                break;

            case 'whereDate':
                if ($value !== null) {
                    $query->whereDate($field, $value);
                }
                break;

            case 'whereMonth':
                if ($value !== null) {
                    $query->whereMonth($field, $value);
                }
                break;

            case 'whereYear':
                if ($value !== null) {
                    $query->whereYear($field, $value);
                }
                break;

            case 'whereDay':
                if ($value !== null) {
                    $query->whereDay($field, $value);
                }
                break;

            case 'whereTime':
                if ($value !== null) {
                    $query->whereTime($field, $value);
                }
                break;

            case 'whereHas':
                if (isset($options['callback']) && is_callable($options['callback'])) {
                    $query->whereHas($field, $options['callback']);
                }
                break;

            case 'whereDoesntHave':
                if (isset($options['callback']) && is_callable($options['callback'])) {
                    $query->whereDoesntHave($field, $options['callback']);
                } else {
                    $query->whereDoesntHave($field);
                }
                break;
            case 'orderBy':
                $direction = $options['direction'] ?? 'asc';
                $query->orderBy($field, $direction);
                break;

            case 'orderByMultiple':
                if (is_array($value)) {
                    foreach ($value as $orderItem) {
                        $column = $orderItem['column'] ?? $field;
                        $direction = $orderItem['direction'] ?? 'asc';
                        $query->orderBy($column, $direction);
                    }
                }
                break;

            default:
                \Log::warning("Unknown advanced filter operator: {$operator}");
                break;
        }
    }

    /**
     * ✨ NEW: Apply advanced filters on relationship fields
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $field
     * @param  string  $operator
     * @param  mixed  $value
     * @param  array  $options
     * @return void
     */
    private function applyAdvancedRelationshipFilter($query, $field, $operator, $value, $options)
    {
        $parts = explode('.', $field);
        $attribute = array_pop($parts);
        $relationPath = implode('.', $parts);

        switch ($operator) {
            case 'whereHas':
                if (isset($options['callback']) && is_callable($options['callback'])) {
                    // Use the custom callback
                    $query->whereHas($relationPath, $options['callback']);
                } else {
                    // Apply the filter on the relationship's attribute
                    $query->whereHas($relationPath, function ($subQuery) use ($attribute, $value, $options) {
                        $subOperator = $options['operator'] ?? '=';
                        if ($value !== null) {
                            $subQuery->where($attribute, $subOperator, $value);
                        }
                    });
                }
                break;

            case 'whereDoesntHave':
                if (isset($options['callback']) && is_callable($options['callback'])) {
                    $query->whereDoesntHave($relationPath, $options['callback']);
                } else {
                    $query->whereDoesntHave($relationPath, function ($subQuery) use ($attribute, $value, $options) {
                        $subOperator = $options['operator'] ?? '=';
                        if ($value !== null) {
                            $subQuery->where($attribute, $subOperator, $value);
                        }
                    });
                }
                break;

            case 'whereIn':
            case 'whereNotIn':
            case 'whereBetween':
            case 'whereNotBetween':
            case 'whereNull':
            case 'whereNotNull':
            case 'where':
            case 'whereDate':
            case 'whereMonth':
            case 'whereYear':
            case 'whereDay':
            case 'whereTime':
                // Apply the advanced filter within the relationship context
                $query->whereHas($relationPath, function ($subQuery) use ($attribute, $operator, $value, $options) {
                    $this->applyAdvancedDirectFilter($subQuery, $attribute, $operator, $value, $options);
                });
                break;

            default:
                \Log::warning("Unknown advanced relationship filter operator: {$operator}");
                break;
        }
    }

    /**
     * 🚀 AUTO-DETECT custom attributes (accessors) to exclude from database queries
     * This prevents SQL errors when trying to filter/sort on computed attributes
     */
    private function autoDetectCustomAttributes($model, $rawColumns)
    {
        $customAttributes = [];
        $tableName = $model->getTable();

        // Get all actual database columns
        $databaseColumns = Schema::getColumnListing($tableName);

        foreach ($rawColumns as $column) {
            $key = $column['key'];

            // Skip relationship fields (they contain dots)
            if (strpos($key, '.') !== false) {
                continue;
            }

            // Skip fallback fields (they contain pipes)
            if (strpos($key, '|') !== false) {
                continue;
            }

            // Check if this is a custom attribute (not in database but accessible on model)
            if (! in_array($key, $databaseColumns)) {
                // Check if the model has an accessor for this attribute
                $accessorMethod = 'get'.str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $key))).'Attribute';

                if (method_exists($model, $accessorMethod)) {
                    $customAttributes[] = $key;
                    \Log::info("Detected custom attribute: {$key} (accessor: {$accessorMethod})");
                } else {
                    // Also check if the attribute is defined in $appends
                    if (in_array($key, $model->getAppends())) {
                        $customAttributes[] = $key;
                        \Log::info("Detected custom attribute: {$key} (in \$appends)");
                    } else {
                        \Log::warning("Column '{$key}' not found in database or as accessor. This might cause SQL errors.");
                    }
                }
            }
        }

        return $customAttributes;
    }

    /**
     * 🚀 AUTO-DETECT relationships from column keys with fallback support
     */
    private function autoDetectRelationships($columns)
    {
        $relationships = [];

        foreach ($columns as $column) {
            $key = $column['key'];

            // Handle fallback relationships (separated by |)
            if (strpos($key, '|') !== false) {
                $fallbackKeys = explode('|', $key);
                foreach ($fallbackKeys as $fallbackKey) {
                    $this->extractRelationshipsFromKey(trim($fallbackKey), $relationships);
                }
            } else {
                $this->extractRelationshipsFromKey($key, $relationships);
            }

            // Also handle existing model_search relations
            if (isset($column['type']) && $column['type'] === 'model_search' && isset($column['relation'])) {
                $relationships[] = $column['relation'];
            }
        }

        return array_unique($relationships);
    }

    /**
     * Extract relationships from a single key
     */
    private function extractRelationshipsFromKey($key, &$relationships)
    {
        // Check for dot notation (relationship.attribute)
        if (strpos($key, '.') !== false) {
            $parts = explode('.', $key);
            array_pop($parts); // Remove attribute, keep relationship path

            if (! empty($parts)) {
                $relationshipPath = implode('.', $parts);
                $relationships[] = $relationshipPath;

                // Also add intermediate paths for nested relationships
                $currentPath = '';
                foreach ($parts as $part) {
                    $currentPath = $currentPath ? "$currentPath.$part" : $part;
                    $relationships[] = $currentPath;
                }
            }
        }
    }

    /**
     * Get base columns (exclude relationship fields and custom attributes)
     */
    private function getBaseColumns($rawColumns, $customAttributes = [])
    {
        return $rawColumns->filter(function ($column) use ($customAttributes) {
            $key = $column['key'];

            // Exclude relationship fields (contain dots)
            if (strpos($key, '.') !== false) {
                return false;
            }

            // Exclude fallback fields (contain pipes)
            if (strpos($key, '|') !== false) {
                return false;
            }

            // Exclude custom attributes
            if (in_array($key, $customAttributes)) {
                return false;
            }

            if (empty($column['type'])) {
                // If type is not defined, assume it's a regular column
                return false;
            }

            // Exclude special field types
            return ! in_array($column['type'], ['media', 'pivot_model']);
        })
            ->pluck('key');
    }

    /**
     * 🚀 SMART SELECT: Get columns with foreign keys for relationships (excluding custom attributes)
     */
    private function getSmartSelectColumns($rawColumns, $relationships, $customAttributes = [])
    {
        // Get base columns (excluding custom attributes)
        $baseColumns = $rawColumns->filter(function ($column) use ($customAttributes) {
            $key = $column['key'];

            if (empty($column['type'])) {
                // If type is not defined, assume it's a regular column
                return false;
            }

            return strpos($key, '.') === false &&
                   strpos($key, '|') === false &&
                   ! in_array($key, $customAttributes) &&
                   ! in_array($column['type'], ['media', 'pivot_model']);
        })
            ->pluck('key')
            ->toArray();

        // Get the model to determine the correct primary key
        $request = request();
        $modelClass = decrypt($request->model);
        $model = new $modelClass;
        $primaryKey = $model->getKeyName();

        // Always include primary key
        if (! in_array($primaryKey, $baseColumns)) {
            array_unshift($baseColumns, $primaryKey);
        }

        // If we have relationships, just select all columns to be safe
        if (! empty($relationships)) {
            return ['*'];
        }

        return $baseColumns;
    }

    /**
     * Apply filters with AUTOMATIC relationship support and custom attribute exclusion
     */
    private function applyFilters($query, $filters, $rawColumns, $customAttributes = [])
    {
        foreach ($filters as $key => $value) {
            if (empty($value)) {
                continue;
            }

            // 🚀 SKIP custom attributes - they can't be filtered in the database
            if (in_array($key, $customAttributes)) {
                \Log::info("Skipping filter for custom attribute: {$key}");

                continue;
            }

            $column = $rawColumns->firstWhere('key', $key);
            if (! $column) {
                continue;
            }

            // Handle fallback relationships (separated by |)
            if (strpos($key, '|') !== false) {
                $fallbackKeys = explode('|', $key);
                $query->where(function ($fallbackQuery) use ($fallbackKeys, $value, $column, $customAttributes) {
                    foreach ($fallbackKeys as $fallbackKey) {
                        $fallbackKey = trim($fallbackKey);

                        // Skip if this fallback key is a custom attribute
                        if (in_array($fallbackKey, $customAttributes)) {
                            continue;
                        }

                        $this->applyFilterToSingleKey($fallbackQuery, $fallbackKey, $value, $column, true);
                    }
                });
            } else {
                $this->applyFilterToSingleKey($query, $key, $value, $column);
            }
        }
    }

    /**
     * Apply search with AUTOMATIC relationship support and custom attribute exclusion
     */
    private function applySearch($query, $search, $rawColumns, $customAttributes = [])
    {
        $sortableColumns = $rawColumns->filter(function ($column) use ($customAttributes) {
            // Only include sortable columns that are not custom attributes
            return $column['sortable'] == true && ! in_array($column['key'], $customAttributes);
        });

        $query->where(function ($q) use ($search, $sortableColumns, $customAttributes) {
            foreach ($sortableColumns as $column) {
                $key = $column['key'];

                // Skip custom attributes
                if (in_array($key, $customAttributes)) {
                    continue;
                }

                // Handle fallback relationships (separated by |)
                if (strpos($key, '|') !== false) {
                    $fallbackKeys = explode('|', $key);
                    foreach ($fallbackKeys as $fallbackKey) {
                        $fallbackKey = trim($fallbackKey);

                        // Skip if this fallback key is a custom attribute
                        if (in_array($fallbackKey, $customAttributes)) {
                            continue;
                        }

                        $this->applySearchToSingleKey($q, $search, $fallbackKey, true);
                    }
                } else {
                    $this->applySearchToSingleKey($q, $search, $key, true);
                }
            }
        });
    }

    /**
     * 🚀 Apply sorting with automatic relationship detection and custom attribute exclusion
     */
    private function applySorting($query, $sort, $direction, $rawColumns, $customAttributes = [])
    {
        // 🚀 SKIP custom attributes - they can't be sorted in the database
        if (in_array($sort, $customAttributes)) {
            \Log::info("Skipping sort for custom attribute: {$sort}. Sorting by ID instead.");
            $query->orderBy('id', $direction);

            return;
        }

        // Check if this is a relationship field
        if (strpos($sort, '.') !== false) {
            $this->applyRelationshipSorting($query, $sort, $direction);
        } else {
            // Direct model field - normal sorting
            try {
                $query->orderBy($sort, $direction);
            } catch (\Exception $e) {
                \Log::warning("Could not sort by column '{$sort}': ".$e->getMessage().'. Sorting by ID instead.');
                $query->orderBy('id', $direction);
            }
        }
    }

    /**
     * Apply filter to a single key
     */
    private function applyFilterToSingleKey($query, $key, $value, $column, $useOr = false)
    {
        // Check if this is a relationship field
        if (strpos($key, '.') !== false) {
            $this->applyRelationshipFilter($query, $key, $value, $column, $useOr);
        } else {
            $this->applyDirectFilter($query, $key, $value, $column, $useOr);
        }
    }

    /**
     * Apply search to a single key
     */
    private function applySearchToSingleKey($query, $search, $key, $useOr = false)
    {
        $method = $useOr ? 'orWhere' : 'where';
        $methodHas = $useOr ? 'orWhereHas' : 'whereHas';

        // Check if this is a relationship field
        if (strpos($key, '.') !== false) {
            // Search in relationship
            $parts = explode('.', $key);
            $attribute = array_pop($parts);
            $relationPath = implode('.', $parts);

            try {
                $query->$methodHas($relationPath, function ($subQuery) use ($attribute, $search) {
                    $subQuery->where($attribute, 'like', '%'.$search.'%');
                });
            } catch (\Exception $e) {
                \Log::warning("Search failed for relationship '{$relationPath}': ".$e->getMessage());
            }
        } else {
            // Search in main model
            try {
                $query->$method($key, 'like', '%'.$search.'%');
            } catch (\Exception $e) {
                \Log::warning("Search failed for column '{$key}': ".$e->getMessage());
            }
        }
    }

    /**
     * 🚀 Apply sorting for relationship fields using subquery
     */
    private function applyRelationshipSorting($query, $sort, $direction)
    {
        $parts = explode('.', $sort);
        $attribute = array_pop($parts);
        $relationPath = implode('.', $parts);

        try {
            $model = $query->getModel();

            // For simple one-level relationships, we can use a join
            if (count($parts) === 1) {
                $relationName = $parts[0];

                if (method_exists($model, $relationName)) {
                    $relation = $model->$relationName();

                    if ($relation instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo) {
                        $foreignKey = $relation->getForeignKeyName();
                        $ownerKey = $relation->getOwnerKeyName();
                        $relatedTable = $relation->getRelated()->getTable();
                        $mainTable = $model->getTable();

                        $query->leftJoin($relatedTable, "{$mainTable}.{$foreignKey}", '=', "{$relatedTable}.{$ownerKey}")
                            ->orderBy("{$relatedTable}.{$attribute}", $direction)
                            ->select("{$mainTable}.*");

                        return;
                    }
                }
            }

            // Fallback to ordering by ID
            $query->orderBy('id', $direction);

        } catch (\Exception $e) {
            \Log::warning("Could not sort by relationship field '{$sort}': ".$e->getMessage());
            $query->orderBy('id', $direction);
        }
    }

    /**
     * Apply filter on direct model field with OR support
     */
    private function applyDirectFilter($query, $key, $value, $column, $useOr = false)
    {
        $method = $useOr ? 'orWhere' : 'where';
        $methodDate = $useOr ? 'orWhereDate' : 'whereDate';

        try {
            switch ($column['type']) {
                case 'model_search':
                    $query->$method($key, $value);
                    break;
                case 'boolean':
                    $query->$method($key, $value === 'true');
                    break;
                case 'date':
                case 'timestamp':
                    if (! empty($value['from'])) {
                        $query->$methodDate($key, '>=', Carbon::parse($value['from']));
                    }
                    if (! empty($value['to'])) {
                        $query->$methodDate($key, '<=', Carbon::parse($value['to']));
                    }
                    break;
                case 'select':
                    $query->$method($key, $value);
                    break;
                default:
                    $query->$method($key, 'LIKE', "%{$value}%");
                    break;
            }
        } catch (\Exception $e) {
            \Log::warning("Filter failed for column '{$key}': ".$e->getMessage());
        }
    }

    /**
     * Apply filter on relationship field with OR support
     */
    private function applyRelationshipFilter($query, $key, $value, $column, $useOr = false)
    {
        $parts = explode('.', $key);
        $attribute = array_pop($parts);
        $relationPath = implode('.', $parts);

        $method = $useOr ? 'orWhereHas' : 'whereHas';

        try {
            $query->$method($relationPath, function ($q) use ($attribute, $value, $column) {
                switch ($column['type']) {
                    case 'boolean':
                        $q->where($attribute, $value === 'true');
                        break;
                    case 'date':
                    case 'timestamp':
                        $this->applyDateFilter($q, $attribute, $value);
                        break;
                    case 'select':
                        $q->where($attribute, $value);
                        break;
                    default:
                        $q->where($attribute, 'LIKE', "%{$value}%");
                        break;
                }
            });
        } catch (\Exception $e) {
            \Log::warning("Filter failed for relationship '{$relationPath}': ".$e->getMessage());
        }
    }

    /**
     * Apply date filter with proper handling of from/to format
     */
    private function applyDateFilter($query, $key, $value, $useOr = false)
    {
        if (is_string($value)) {
            if ($useOr) {
                $query->orWhereDate($key, $value);
            } else {
                $query->whereDate($key, $value);
            }
        } elseif (is_array($value)) {
            if (isset($value['from']) && ! empty($value['from'])) {
                if ($useOr) {
                    $query->orWhereDate($key, '>=', $value['from']);
                } else {
                    $query->whereDate($key, '>=', $value['from']);
                }
            }

            if (isset($value['to']) && ! empty($value['to'])) {
                if ($useOr) {
                    $query->orWhereDate($key, '<=', $value['to']);
                } else {
                    $query->whereDate($key, '<=', $value['to']);
                }
            }
        }
    }

    /**
     * Store method for creating a new row.
     */
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $this->dynamicFieldValidation($request);

            $builderHelper = new BuilderHelper;
            if (! empty($request->permission)) {
                $builderHelper->permissionCheck($request, 'store');
            }

            $model = decrypt($request->model);
            $model = new $model;

            // 🚀 AUTO-DETECT custom attributes to exclude from saving
            $rawColumns = collect($request->data);
            $customAttributes = $this->autoDetectCustomAttributes($model, $rawColumns);

            $mediaRelations = [];
            $pivotRelations = [];

            foreach ($rawColumns as $column) {
                // 🚀 SKIP relationship fields automatically
                if (strpos($column['key'], '.') !== false) {
                    continue;
                }

                // 🚀 SKIP custom attributes automatically
                if (in_array($column['key'], $customAttributes)) {
                    \Log::info("Skipping save for custom attribute: {$column['key']}");

                    continue;
                }

                if ($column['type'] == 'media') {
                    $mediaRelations[] = $column;
                } elseif ($column['type'] == 'pivot_model') {
                    $pivotRelations[] = $column;
                } else {
                    $model = $builderHelper->genericValidation($model, $column);
                }
            }

            $model->save();

            // Handle media relations
            foreach ($mediaRelations as $mediaRelation) {
                if (! empty($mediaRelation['value']) && is_array($mediaRelation['value'])) {
                    foreach ($mediaRelation['value'] as $item) {
                        if (isset($item['id'])) {
                            $model->media()->create([
                                'media_id' => $item['id'],
                            ]);
                        }
                    }
                }
            }

            // Handle pivot relations
            foreach ($pivotRelations as $dynamicRelation) {
                if (isset($dynamicRelation['relation'])) {
                    $model->{$dynamicRelation['relation']}()->detach();
                    $idsSync = collect($dynamicRelation['value'])->pluck('id')->filter();
                    if ($idsSync->count() > 0) {
                        $model->{$dynamicRelation['relation']}()->attach($idsSync);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Item created successfully',
                'data' => [
                    'id' => $model->id,
                    'created_at' => $model->created_at,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the item: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the table with new data.
     */
    public function update(Request $request)
    {
        try {
            DB::beginTransaction();

            $this->dynamicFieldValidation($request);

            $builderHelper = new BuilderHelper;
            if (! empty($request->permission)) {
                $builderHelper->permissionCheck($request, 'update');
            }

            $modelClass = decrypt($request->model);
            $model = $modelClass::findOrFail($request->id);

            // 🚀 AUTO-DETECT custom attributes to exclude from saving
            $rawColumns = collect($request->data);
            $customAttributes = $this->autoDetectCustomAttributes($model, $rawColumns);

            $mediaRelations = [];
            $pivotRelations = [];

            foreach ($rawColumns as $column) {
                // 🚀 SKIP relationship fields automatically
                if (strpos($column['key'], '.') !== false) {
                    continue;
                }

                // 🚀 SKIP custom attributes automatically
                if (in_array($column['key'], $customAttributes)) {
                    \Log::info("Skipping update for custom attribute: {$column['key']}");

                    continue;
                }

                if ($column['type'] == 'media') {
                    $mediaRelations[] = $column;
                } elseif ($column['type'] == 'pivot_model') {
                    $pivotRelations[] = $column;
                } else {
                    $model = $builderHelper->genericValidation($model, $column);
                }
            }

            $model->save();

            // Handle media relations
            if (! empty($mediaRelations)) {
                $model->media()->delete();
                foreach ($mediaRelations as $mediaRelation) {
                    if (! empty($mediaRelation['value']) && is_array($mediaRelation['value'])) {
                        foreach ($mediaRelation['value'] as $item) {
                            if (isset($item['id'])) {
                                $model->media()->create([
                                    'media_id' => $item['id'],
                                ]);
                            }
                        }
                    }
                }
            }

            // Handle pivot relations
            foreach ($pivotRelations as $dynamicRelation) {
                if (isset($dynamicRelation['relation'])) {
                    $model->{$dynamicRelation['relation']}()->detach();
                    $idsSync = collect($dynamicRelation['value'])->pluck('id')->filter();
                    if ($idsSync->count() > 0) {
                        $model->{$dynamicRelation['relation']}()->attach($idsSync);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Item updated successfully',
                'data' => [
                    'id' => $model->id,
                    'updated_at' => $model->updated_at,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the item: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Dynamic delete model item.
     */
    public function delete(Request $request)
    {
        $request->validate([
            'model' => 'required',
            'id' => 'required',
        ]);

        DB::beginTransaction();

        $builderHelper = new BuilderHelper;
        if (! empty($request->permission)) {
            $builderHelper->permissionCheck($request, 'delete');
        }

        $model = decrypt($request->model);
        $model = new $model;
        $modelItem = $model->find($request->id);

        $relations = $model->getRelations();
        foreach ($relations as $relation) {
            $model->$relation()->delete();
        }

        $modelItem->delete();
        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Item deleted successfully',
        ]);
    }

    /**
     * Dynamic field validation (excluding custom attributes)
     */
    private function dynamicFieldValidation(Request $request)
    {
        $request->validate(
            [
                'model' => 'required',
                'data' => 'required',
            ],
            [
                'data.required' => 'Fields are required',
            ]
        );

        $errorMessages = [];

        // Get model to detect custom attributes
        $modelClass = decrypt($request->model);
        $model = new $modelClass;
        $customAttributes = $this->autoDetectCustomAttributes($model, collect($request->data));

        foreach ($request->data as $value) {
            // 🚀 SKIP validation for relationship fields
            if (strpos($value['key'], '.') !== false) {
                continue;
            }

            // 🚀 SKIP validation for custom attributes
            if (in_array($value['key'], $customAttributes)) {
                continue;
            }

            if (empty($value['nullable']) && empty($value['value'])) {
                if ($value['type'] === 'boolean' && $value['value'] === false) {
                    continue;
                }
                $errorMessages[] = 'The '.$value['label'].' is required';
            }
        }

        if (count($errorMessages) > 0) {
            throw ValidationException::withMessages($errorMessages);
        }
    }
}
