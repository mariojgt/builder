<?php

namespace Mariojgt\Builder\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Schema;
use Mariojgt\Builder\Helpers\BuilderHelper;
use Illuminate\Validation\ValidationException;

class TableBuilderApiController extends Controller
{
    /**
     * Handle data display to the table with AUTOMATIC relationship detection, custom attributes support,
     * advanced filters support, and MODEL SCOPES support.
     *
     * @param Request $request
     * @return array
     */
    public function index(Request $request)
    {
        $request->validate([
            'model' => 'required',
            'columns' => 'required',
        ]);

        $builderHelper = new BuilderHelper();
        if (!empty($request->permission)) {
            $builderHelper->permissionCheck($request, 'index');
        }

        $model = decrypt($request->model);
        $modelClass = $model;
        $model = new $model();
        $rawColumns = collect($request->columns);

        // 🚀 AUTO-DETECT RELATIONSHIPS from dot notation
        $relationships = $this->autoDetectRelationships($rawColumns);

        // 🚀 AUTO-DETECT CUSTOM ATTRIBUTES (accessors) to exclude from queries
        $customAttributes = $this->autoDetectCustomAttributes($model, $rawColumns);

        // 🚀 NEW: AUTO-DETECT ADDITIONAL RELATIONSHIPS from custom attributes
        $additionalRelationships = $this->autoDetectCustomAttributeRelationships($model, $customAttributes);
        $relationships = array_merge($relationships, $additionalRelationships);

        // 🚀 ENHANCED: Extract nested relationships (e.g., product.platform from product.platform.name)
        $relationships = $this->extractNestedRelationships($relationships);

        // Get base columns (no relationships, no custom attributes)
        $columns = $this->getBaseColumns($rawColumns, $customAttributes);

        // Add timestamps if model has them
        $hasTimestamps = $model->timestamps;
        if ($hasTimestamps && !$columns->contains('updated_at')) {
            $columns->push('updated_at');
        }

        // 🚀 SMART SELECT: Add foreign keys for relationships
        $selectColumns = $this->getSmartSelectColumns($rawColumns, $relationships, $customAttributes);

        // 🚀 ENHANCED: Start query with OPTIMIZED relationship loading
        $query = $model->query();
        if (!empty($relationships)) {
            // Remove duplicates and optimize relationship loading
            $optimizedRelationships = $this->optimizeRelationshipLoading($relationships, $model);

            // 🆕 Detect which relationships need withCount instead of eager loading
            $withRelationships = [];
            $withCountRelationships = [];

            foreach ($optimizedRelationships as $rel) {
                if (strpos($rel, ':count') !== false) {
                    // Already marked for withCount
                    $withCountRelationships[] = str_replace(':count', '', $rel);
                } else {
                    $withRelationships[] = $rel;
                }
            }

            // 🐛 DEBUG: Log what relationships are being eager loaded
            \Log::info("TableBuilder eager loading relationships", [
                'model' => get_class($model),
                'detected_relationships' => $relationships,
                'with_relationships' => $withRelationships,
                'with_count_relationships' => $withCountRelationships
            ]);

            // Apply eager loading
            if (!empty($withRelationships)) {
                $query->with($withRelationships);
            }

            // Apply withCount for performance
            if (!empty($withCountRelationships)) {
                $query->withCount($withCountRelationships);
            }
        }

        // ✨ NEW: Apply model scopes FIRST (these are part of the table configuration)
        if ($request->has('modelScopes') && !empty($request->modelScopes)) {
            $this->applyModelScopes($query, $request->modelScopes);
        }

        // ✨ Apply advanced filters SECOND (these are part of the table configuration)
        // BUT exclude orderByMultiple filters if user has specified manual sorting
        if ($request->has('advancedFilters') && !empty($request->advancedFilters)) {
            $advancedFiltersToApply = $request->advancedFilters;

            // If user has specified manual sorting, remove orderByMultiple from advanced filters
            if (!empty($request->sort)) {
                \Log::info("User specified manual sort: {$request->sort} {$request->direction}. Filtering out orderByMultiple from advanced filters.");
                $advancedFiltersToApply = [];
                foreach ($request->advancedFilters as $filter) {
                    $operator = $filter['operator'] ?? '';
                    if ($operator === 'orderByMultiple') {
                        \Log::info("Excluding orderByMultiple filter: " . json_encode($filter));
                    } else {
                        $advancedFiltersToApply[] = $filter;
                    }
                }
                \Log::info("Advanced filters after filtering: " . json_encode($advancedFiltersToApply));
            }

            $this->applyAdvancedFilters($query, $advancedFiltersToApply, $rawColumns, $customAttributes);
        }

        // Handle regular filters (with relationship support, excluding custom attributes)
        if ($request->has('filters') && !empty($request->filters)) {
            $this->applyFilters($query, $request->filters, $rawColumns, $customAttributes);
        }

        // Handle search (with relationship support, excluding custom attributes)
        if ($request->has('search') && !empty($request->search)) {
            $this->applySearch($query, $request->search, $rawColumns, $customAttributes);
        }

        // Handle sorting with relationship support (excluding custom attributes)
        // This comes AFTER advanced filters so user sorting takes precedence
        if (!empty($request->sort)) {
            $this->applySorting($query, $request->sort, $request->direction ?? 'asc', $rawColumns, $customAttributes);
        } else {
            // If no manual sort, apply orderByMultiple from advanced filters
            if ($request->has('advancedFilters') && !empty($request->advancedFilters)) {
                $orderByMultipleFilters = array_filter($request->advancedFilters, function($filter) {
                    return ($filter['operator'] ?? '') === 'orderByMultiple';
                });

                if (!empty($orderByMultipleFilters)) {
                    $this->applyAdvancedFilters($query, $orderByMultipleFilters, $rawColumns, $customAttributes);
                }
            }
        }

        // Execute query with smart column selection
        $modelPaginated = $query->select($selectColumns)->paginate($request->perPage ?? 10);

        // 🚀 NEW: Apply post-query optimization for remaining N+1 issues
        $this->applyPostQueryOptimization($modelPaginated, $customAttributes, $relationships);

        // Process data (with relationship values and custom attributes)
        $data = $builderHelper->columnReplacements($modelPaginated, $rawColumns);

        $data = $this->pruneUnrequestedColumns($data, $rawColumns);

        // Convert links to array to avoid any serialization issues
        $links = $modelPaginated->linkCollection()->toArray();

        // Get the latest updated_at timestamp from the model for client-side cache invalidation
        $latestUpdate = $model->max('updated_at');
        $cacheTimestamp = $latestUpdate ? strtotime($latestUpdate) : time();

        $responseData = [
            'data' => $data,
            'current_page' => $modelPaginated->currentPage(),
            'first_page_url' => $modelPaginated->url(1),
            'from' => $modelPaginated->firstItem(),
            'last_page' => $modelPaginated->lastPage(),
            'last_page_url' => $modelPaginated->url($modelPaginated->lastPage()),
            'links' => $links,
            'next_page_url' => $modelPaginated->nextPageUrl(),
            'path' => $modelPaginated->path(),
            'per_page' => $modelPaginated->perPage(),
            'prev_page_url' => $modelPaginated->previousPageUrl(),
            'to' => $modelPaginated->lastItem(),
            'total' => $modelPaginated->total(),
            // Client-side cache metadata
            'cache_key' => class_basename($modelClass),
            'cache_timestamp' => $cacheTimestamp,
        ];

        return $responseData;
    }

    /**
     * ✨ NEW: Apply model scopes to the query
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $modelScopes
     * @return void
     */
    private function applyModelScopes($query, $modelScopes)
    {
        foreach ($modelScopes as $scope) {
            $scopeName = $scope['name'];
            $parameters = $scope['parameters'] ?? [];

            try {
                // Check if the scope method exists on the model
                $model = $query->getModel();
                $scopeMethod = 'scope' . ucfirst($scopeName);

                if (method_exists($model, $scopeMethod)) {
                    // Apply the scope with parameters
                    if (empty($parameters)) {
                        $query->$scopeName();
                    } else {
                        $query->$scopeName(...$parameters);
                    }

                    \Log::info("Applied scope '{$scopeName}' with parameters: " . json_encode($parameters));
                } else {
                    \Log::warning("Scope method '{$scopeMethod}' does not exist on model " . get_class($model));
                }
            } catch (\Exception $e) {
                \Log::error("Failed to apply scope '{$scopeName}': " . $e->getMessage());
            }
        }
    }

    /**
     * Helper to prune a collection of data items to include only explicitly requested columns.
     * This iterates through the items and removes fields not present in $rawColumns,
     * maintaining dot-notation as flat keys and handling fallback values for both
     * the main column key and the url_field key.
     *
     * @param \Illuminate\Support\Collection $collection The collection of data items (after columnReplacements)
     * @param \Illuminate\Support\Collection $rawColumns The collection of raw column definitions from the request
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
                    $formattedKey = $columnDefinition['key'] . '_link';
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
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $advancedFilters
     * @param \Illuminate\Support\Collection $rawColumns
     * @param array $customAttributes
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
                \Log::warning("Advanced filter failed for field '{$field}' with operator '{$operator}': " . $e->getMessage());
            }
        }
    }

    /**
     * ✨ NEW: Apply advanced filters on direct model fields
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $field
     * @param string $operator
     * @param mixed $value
     * @param array $options
     * @return void
     */
    private function applyAdvancedDirectFilter($query, $field, $operator, $value, $options)
    {
        switch ($operator) {
            case 'whereIn':
                if (is_array($value) && !empty($value)) {
                    $query->whereIn($field, $value);
                }
                break;

            case 'whereNotIn':
                if (is_array($value) && !empty($value)) {
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
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $field
     * @param string $operator
     * @param mixed $value
     * @param array $options
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
     * ENHANCED: Better detection of count accessors and nested relationship patterns
     */
    private function autoDetectCustomAttributes($model, $rawColumns)
    {
        $customAttributes = [];
        $tableName = $model->getTable();

        // Get all actual database columns
        $databaseColumns = Schema::getColumnListing($tableName);

        foreach ($rawColumns as $column) {
            $key = $column['key'];

            // Skip fallback fields (they contain pipes)
            if (strpos($key, '|') !== false) {
                continue;
            }

            // Check for nested count patterns (e.g., product.vulnerabilities_count)
            if (strpos($key, '.') !== false) {
                if (preg_match('/\.(\w+)_count$/', $key)) {
                    $customAttributes[] = $key;
                }
                continue;
            }

            // Check for direct _count accessor patterns (e.g., vulnerabilities_count)
            if (preg_match('/(\w+)_count$/', $key, $matches)) {
                $relationshipName = $matches[1];
                // Check if the relationship exists on the model
                if (method_exists($model, $relationshipName)) {
                    $customAttributes[] = $key;
                    \Log::info("Detected count accessor: {$key} (relationship: {$relationshipName})");
                    continue;
                }
            }

            // Check if this is a custom attribute (not in database but accessible on model)
            if (!in_array($key, $databaseColumns)) {
                // Check if the model has an accessor for this attribute
                $accessorMethod = 'get' . str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $key))) . 'Attribute';

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
     * ENHANCED: Detect _count patterns and mark for withCount
     */
    private function extractRelationshipsFromKey($key, &$relationships)
    {
        // Check for dot notation (relationship.attribute)
        if (strpos($key, '.') !== false) {
            $parts = explode('.', $key);
            $lastPart = end($parts);

            // Check if the last part is a _count accessor (e.g., product.vulnerabilities_count)
            if (preg_match('/(\w+)_count$/', $lastPart, $matches)) {
                $relationshipName = $matches[1];
                // Build the path without the _count part
                $pathParts = array_slice($parts, 0, -1);
                $pathParts[] = $relationshipName;

                // Add the full path for withCount (e.g., "product.vulnerabilities")
                $fullPath = implode('.', $pathParts);
                $relationships[] = $fullPath . '__count'; // Mark as count relationship

                // Also add intermediate paths (e.g., "product")
                $currentPath = '';
                foreach ($pathParts as $index => $part) {
                    if ($index === count($pathParts) - 1) {
                        break; // Skip the last part (the counted relationship)
                    }
                    $currentPath = $currentPath ? "$currentPath.$part" : $part;
                    $relationships[] = $currentPath;
                }
                return;
            }

            // Normal relationship without _count
            array_pop($parts); // Remove attribute, keep relationship path

            if (!empty($parts)) {
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
     * 🚀 NEW: Extract nested relationships to ensure proper eager loading
     * Example: ['product.platform.name'] => ['product', 'product.platform']
     */
    private function extractNestedRelationships($relationships)
    {
        $expanded = [];

        foreach ($relationships as $relationship) {
            // Add the relationship itself
            $expanded[] = $relationship;

            // If it contains dots, also add all parent relationships
            if (strpos($relationship, '.') !== false) {
                $parts = explode('.', $relationship);
                $currentPath = '';

                foreach ($parts as $part) {
                    $currentPath = $currentPath ? "$currentPath.$part" : $part;
                    $expanded[] = $currentPath;
                }
            }
        }

        return array_unique($expanded);
    }

    /**
     * 🚀 NEW: Auto-detect relationships from custom attribute accessors
     */
    private function autoDetectCustomAttributeRelationships($model, $customAttributes)
    {
        $relationships = [];

        foreach ($customAttributes as $attribute) {
            $accessorMethod = 'get' . str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $attribute))) . 'Attribute';

            if (method_exists($model, $accessorMethod)) {
                try {
                    $reflection = new \ReflectionMethod($model, $accessorMethod);
                    $methodSource = $this->getMethodSource($reflection);

                    // Look for relationship method calls in accessor
                    if (preg_match_all('/\$this->([a-zA-Z_][a-zA-Z0-9_]*)\(\)/', $methodSource, $matches)) {
                        foreach ($matches[1] as $relationMethod) {
                            if (method_exists($model, $relationMethod)) {
                                $relationships[] = $relationMethod;
                            }
                        }
                    }

                    // Look for nested relationship calls like $this->report()->exists()
                    if (preg_match_all('/\$this->([a-zA-Z_][a-zA-Z0-9_]*)\(\)->([a-zA-Z_][a-zA-Z0-9_]*)\(\)/', $methodSource, $matches)) {
                        foreach ($matches[1] as $relationMethod) {
                            if (method_exists($model, $relationMethod)) {
                                $relationships[] = $relationMethod;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    \Log::info("Could not analyze custom attribute accessor: {$attribute}");
                }
            }
        }

        return array_unique($relationships);
    }

    /**
     * 🚀 NEW: Optimize relationship loading to prevent N+1 queries
     * ENHANCED: Keep nested relationships (e.g., product.platform) and handle withCount properly
     */
    private function optimizeRelationshipLoading($relationships, $model)
    {
        $optimized = [];
        $withCount = [];

        // Remove duplicates
        $relationships = array_unique($relationships);

        foreach ($relationships as $relationship) {
            // Check if this is marked as a count relationship
            if (strpos($relationship, '__count') !== false) {
                $cleanRelationship = str_replace('__count', '', $relationship);
                // Verify the relationship exists
                if ($this->relationshipExists($model, $cleanRelationship)) {
                    $withCount[] = $cleanRelationship;
                }
                continue;
            }

            // Verify the relationship exists on the model before adding
            if ($this->relationshipExists($model, $relationship)) {
                $optimized[] = $relationship;
            }
        }

        // 🚀 NEW: Add count relationships for better performance
        $additionalCounts = $this->detectCountRelationships($optimized, $model);
        $withCount = array_merge($withCount, $additionalCounts);

        // Merge optimized relationships with count relationships
        $result = $optimized;
        foreach (array_unique($withCount) as $countRel) {
            // Don't add count if we're already loading the full relationship
            if (!in_array($countRel, $optimized)) {
                $result[] = $countRel . ':count';
            }
        }

        return array_unique($result);
    }

    /**
     * 🚀 NEW: Check if a relationship exists on the model
     */
    private function relationshipExists($model, $relationship)
    {
        $parts = explode('.', $relationship);
        $currentModel = $model;

        foreach ($parts as $part) {
            if (!method_exists($currentModel, $part)) {
                return false;
            }

            try {
                $relation = $currentModel->$part();
                if (!$relation instanceof \Illuminate\Database\Eloquent\Relations\Relation) {
                    return false;
                }

                // For nested relationships, get the related model
                if (count($parts) > 1) {
                    $currentModel = $relation->getRelated();
                }
            } catch (\Exception $e) {
                return false;
            }
        }

        return true;
    }

    /**
     * 🚀 NEW: Detect relationships that should use withCount for performance
     * Returns the relationship names that should be counted (without _count suffix)
     */
    private function detectCountRelationships($relationships, $model)
    {
        $countRelationships = [];

        // Look for relationships that might benefit from withCount
        foreach ($relationships as $relationship) {
            $parts = explode('.', $relationship);
            $baseRelation = $parts[0];

            // Check if this is a hasMany or belongsToMany relationship
            if (method_exists($model, $baseRelation)) {
                try {
                    $relation = $model->$baseRelation();
                    if ($relation instanceof \Illuminate\Database\Eloquent\Relations\HasMany ||
                        $relation instanceof \Illuminate\Database\Eloquent\Relations\BelongsToMany ||
                        $relation instanceof \Illuminate\Database\Eloquent\Relations\HasManyThrough) {
                        // Add withCount for performance if not already loading the full relationship
                        if (!in_array($baseRelation, $relationships)) {
                            $countRelationships[] = $baseRelation; // Just the relationship name
                        }
                    }
                } catch (\Exception $e) {
                    // Ignore invalid relationships
                }
            }
        }

        return $countRelationships;
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
            return !in_array($column['type'], ['media', 'pivot_model']);
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
                   !in_array($key, $customAttributes) &&
                   !in_array($column['type'], ['media', 'pivot_model']);
        })
        ->pluck('key')
        ->toArray();

        // Get the model to determine the correct primary key
        $request = request();
        $modelClass = decrypt($request->model);
        $model = new $modelClass();
        $primaryKey = $model->getKeyName();

        // Always include primary key
        if (!in_array($primaryKey, $baseColumns)) {
            array_unshift($baseColumns, $primaryKey);
        }

        // If we have relationships, just select all columns to be safe
        if (!empty($relationships)) {
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
            // Handle new filter structure with search modes
            $filterValue = $value;
            $isEmpty = false;

            if (is_array($value) && isset($value['value'])) {
                $filterValue = $value['value'];
                $isEmpty = empty($filterValue);
            } else {
                $isEmpty = empty($value);
            }

            if ($isEmpty) {
                continue;
            }

            // 🚀 HANDLE specific custom attributes that can be converted to database queries
            if (in_array($key, $customAttributes)) {
                if ($this->applyCustomAttributeFilter($query, $key, $value)) {
                    continue; // Successfully handled as custom attribute
                }
                \Log::info("Skipping filter for unsupported custom attribute: {$key}");
                continue;
            }

            $column = $rawColumns->firstWhere('key', $key);
            if (!$column) {
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
            return $column['sortable'] == true && !in_array($column['key'], $customAttributes);
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
        \Log::info("Attempting to apply sorting: {$sort} {$direction}");

        // 🚀 SKIP custom attributes - they can't be sorted in the database
        if (in_array($sort, $customAttributes)) {
            \Log::info("Skipping sort for custom attribute: {$sort}. Sorting by ID instead.");
            $query->orderBy('id', $direction);
            return;
        }

        // Check if this is a relationship field
        if (strpos($sort, '.') !== false) {
            \Log::info("Detected relationship field for sorting: {$sort}");
            $this->applyRelationshipSorting($query, $sort, $direction);
        } else {
            // Direct model field - normal sorting
            \Log::info("Applying direct field sorting: {$sort} {$direction}");
            try {
                $query->orderBy($sort, $direction);
                \Log::info("Successfully applied sorting for: {$sort} {$direction}");
            } catch (\Exception $e) {
                \Log::warning("Could not sort by column '{$sort}': " . $e->getMessage() . ". Sorting by ID instead.");
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
                    $subQuery->where($attribute, 'like', '%' . $search . '%');
                });
            } catch (\Exception $e) {
                \Log::warning("Search failed for relationship '{$relationPath}': " . $e->getMessage());
            }
        } else {
            // Search in main model
            try {
                $query->$method($key, 'like', '%' . $search . '%');
            } catch (\Exception $e) {
                \Log::warning("Search failed for column '{$key}': " . $e->getMessage());
            }
        }
    }

    /**
     * 🚀 Apply sorting for relationship fields using subquery approach
     */
    private function applyRelationshipSorting($query, $sort, $direction)
    {
        $parts = explode('.', $sort);
        $attribute = array_pop($parts);
        $relationPath = implode('.', $parts);

        try {
            $model = $query->getModel();

            // For simple one-level relationships, we can use a subquery approach
            if (count($parts) === 1) {
                $relationName = $parts[0];

                if (method_exists($model, $relationName)) {
                    $relation = $model->$relationName();
                    $relatedModel = $relation->getRelated();
                    $relatedTable = $relatedModel->getTable();
                    $mainTable = $model->getTable();

                    if ($relation instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo) {
                        $foreignKey = $relation->getForeignKeyName();
                        $ownerKey = $relation->getOwnerKeyName();

                        // Use subquery to avoid join issues and duplicate results
                        $query->orderBy(
                            $relatedModel->newQuery()
                                ->select($attribute)
                                ->whereColumn("{$relatedTable}.{$ownerKey}", "{$mainTable}.{$foreignKey}")
                                ->limit(1),
                            $direction
                        );

                        \Log::info("Applied relationship sorting for '{$sort}' using subquery approach");
                        return;
                    } elseif ($relation instanceof \Illuminate\Database\Eloquent\Relations\HasOne) {
                        $foreignKey = $relation->getForeignKeyName();
                        $localKey = $relation->getLocalKeyName();

                        // Use subquery for HasOne relationship
                        $query->orderBy(
                            $relatedModel->newQuery()
                                ->select($attribute)
                                ->whereColumn("{$relatedTable}.{$foreignKey}", "{$mainTable}.{$localKey}")
                                ->limit(1),
                            $direction
                        );

                        \Log::info("Applied HasOne relationship sorting for '{$sort}' using subquery approach");
                        return;
                    } elseif ($relation instanceof \Illuminate\Database\Eloquent\Relations\HasMany) {
                        $foreignKey = $relation->getForeignKeyName();
                        $localKey = $relation->getLocalKeyName();

                        // For HasMany, order by the first related record
                        $query->orderBy(
                            $relatedModel->newQuery()
                                ->select($attribute)
                                ->whereColumn("{$relatedTable}.{$foreignKey}", "{$mainTable}.{$localKey}")
                                ->limit(1),
                            $direction
                        );

                        \Log::info("Applied HasMany relationship sorting for '{$sort}' using subquery approach");
                        return;
                    }
                }
            } else {
                // For nested relationships (e.g., user.profile.name), use a more complex subquery
                $this->applyNestedRelationshipSorting($query, $parts, $attribute, $direction);
                return;
            }

            // Fallback to ordering by ID
            \Log::info("Falling back to ID sorting for relationship field: {$sort}");
            $query->orderBy('id', $direction);
        } catch (\Exception $e) {
            \Log::warning("Could not sort by relationship field '{$sort}': " . $e->getMessage());
            $query->orderBy('id', $direction);
        }
    }

    /**
     * Apply sorting for nested relationship fields (e.g., user.profile.name)
     */
    private function applyNestedRelationshipSorting($query, $relationParts, $attribute, $direction)
    {
        try {
            $model = $query->getModel();
            $currentModel = $model;
            $joins = [];
            $tables = [$model->getTable()];

            // Build the chain of relationships
            foreach ($relationParts as $relationName) {
                if (!method_exists($currentModel, $relationName)) {
                    throw new \Exception("Relationship {$relationName} does not exist on " . get_class($currentModel));
                }

                $relation = $currentModel->$relationName();
                $relatedModel = $relation->getRelated();
                $relatedTable = $relatedModel->getTable();

                if ($relation instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo) {
                    $foreignKey = $relation->getForeignKeyName();
                    $ownerKey = $relation->getOwnerKeyName();
                    $joins[] = [
                        'table' => $relatedTable,
                        'foreign' => end($tables) . '.' . $foreignKey,
                        'owner' => $relatedTable . '.' . $ownerKey
                    ];
                } elseif ($relation instanceof \Illuminate\Database\Eloquent\Relations\HasOne ||
                         $relation instanceof \Illuminate\Database\Eloquent\Relations\HasMany) {
                    $foreignKey = $relation->getForeignKeyName();
                    $localKey = $relation->getLocalKeyName();
                    $joins[] = [
                        'table' => $relatedTable,
                        'foreign' => $relatedTable . '.' . $foreignKey,
                        'owner' => end($tables) . '.' . $localKey
                    ];
                } else {
                    throw new \Exception("Unsupported relationship type for sorting: " . get_class($relation));
                }

                $tables[] = $relatedTable;
                $currentModel = $relatedModel;
            }

            // Build subquery for nested relationships
            $finalTable = end($tables);
            $subquery = \DB::table($tables[1]); // Start from the first related table

            // Add all the joins
            for ($i = 1; $i < count($joins); $i++) {
                $join = $joins[$i];
                $subquery->leftJoin($join['table'], $join['foreign'], '=', $join['owner']);
            }

            // Add the where condition to link back to the main table
            $firstJoin = $joins[0];
            $subquery->select($finalTable . '.' . $attribute)
                     ->whereColumn($firstJoin['foreign'], $firstJoin['owner'])
                     ->limit(1);

            $query->orderBy($subquery, $direction);

            \Log::info("Applied nested relationship sorting for: " . implode('.', $relationParts) . '.' . $attribute);
        } catch (\Exception $e) {
            \Log::warning("Could not sort by nested relationship: " . $e->getMessage());
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
            // Handle filters with search mode information
            $filterValue = $value;
            $searchMode = 'contains'; // default

            if (is_array($value) && isset($value['value'])) {
                $filterValue = $value['value'];
                $searchMode = $value['searchMode'] ?? 'contains';
            }

            switch ($column['type']) {
                case 'model_search':
                    $query->$method($key, $filterValue);
                    break;
                case 'boolean':
                    $boolValue = is_array($value) ? $filterValue === 'true' : $value === 'true';
                    $query->$method($key, $boolValue);
                    break;
                case 'date':
                case 'timestamp':
                    if (!empty($filterValue['from'])) {
                        $query->$methodDate($key, '>=', Carbon::parse($filterValue['from']));
                    }
                    if (!empty($filterValue['to'])) {
                        $query->$methodDate($key, '<=', Carbon::parse($filterValue['to']));
                    }
                    break;
                case 'select':
                    // When the mode is 'exact', we must use a strict comparison
                    if ($searchMode === 'exact') {
                        if (is_numeric($filterValue)) {
                            $query->$method($key, '=', (int)$filterValue);
                        } else {
                            $query->$method($key, '=', $filterValue);
                        }
                    } else {
                        // For other modes like 'contains', use the text filter logic
                        $this->applyTextFilterWithMode($query, $key, $filterValue, $searchMode, $useOr);
                    }
                    break;
                default:
                    // Apply search mode for text filters
                    $this->applyTextFilterWithMode($query, $key, $filterValue, $searchMode, $useOr);
                    break;
            }
        } catch (\Exception $e) {
            \Log::warning("Filter failed for column '{$key}': " . $e->getMessage());
        }
    }

    /**
     * Apply text filter with different search modes (contains, exact, starts)
     * Auto-detects numeric values and uses exact matching for better precision
     */
    private function applyTextFilterWithMode($query, $key, $value, $searchMode, $useOr = false)
    {
        $method = $useOr ? 'orWhere' : 'where';

        // If value is numeric, always use exact matching to avoid LIKE matching issues
        // (e.g., searching for "5" shouldn't match "15", "25", "50", etc.)
        if (is_numeric($value)) {
            $query->$method($key, '=', $value);
            return;
        }

        switch ($searchMode) {
            case 'exact':
                $query->$method($key, '=', $value);
                break;
            case 'starts':
                $query->$method($key, 'LIKE', $value . '%');
                break;
            case 'contains':
            default:
                $query->$method($key, 'LIKE', '%' . $value . '%');
                break;
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
                // Handle filters with search mode information
                $filterValue = $value;
                $searchMode = 'contains'; // default

                if (is_array($value) && isset($value['value'])) {
                    $filterValue = $value['value'];
                    $searchMode = $value['searchMode'] ?? 'contains';
                }

                switch ($column['type']) {
                    case 'boolean':
                        $boolValue = is_array($value) ? $filterValue === 'true' : $value === 'true';
                        $q->where($attribute, $boolValue);
                        break;
                    case 'date':
                    case 'timestamp':
                        $this->applyDateFilter($q, $attribute, $filterValue);
                        break;
                    case 'select':
                        // Check if search mode is set to 'contains' - allow partial matching
                        if ($searchMode === 'contains') {
                            $this->applyTextFilterWithMode($q, $attribute, $filterValue, $searchMode, false);
                        } else {
                            // Default to exact match for select fields
                            // Ensure numeric values are cast correctly for comparison
                            if (is_numeric($filterValue)) {
                                $q->where($attribute, '=', (int)$filterValue);
                            } else {
                                $q->where($attribute, '=', $filterValue);
                            }
                        }
                        break;
                    default:
                        // Apply search mode for text filters in relationships
                        $this->applyTextFilterWithMode($q, $attribute, $filterValue, $searchMode, false);
                        break;
                }
            });
        } catch (\Exception $e) {
            \Log::warning("Filter failed for relationship '{$relationPath}': " . $e->getMessage());
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
            if (isset($value['from']) && !empty($value['from'])) {
                if ($useOr) {
                    $query->orWhereDate($key, '>=', $value['from']);
                } else {
                    $query->whereDate($key, '>=', $value['from']);
                }
            }

            if (isset($value['to']) && !empty($value['to'])) {
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

            $builderHelper = new BuilderHelper();
            if (!empty($request->permission)) {
                $builderHelper->permissionCheck($request, 'store');
            }

            $model = decrypt($request->model);
            $model = new $model();

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
                if (!empty($mediaRelation['value']) && is_array($mediaRelation['value'])) {
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
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the item: ' . $e->getMessage(),
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

            $builderHelper = new BuilderHelper();
            if (!empty($request->permission)) {
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
            if (!empty($mediaRelations)) {
                $model->media()->delete();
                foreach ($mediaRelations as $mediaRelation) {
                    if (!empty($mediaRelation['value']) && is_array($mediaRelation['value'])) {
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
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the item: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 🚀 DYNAMIC: Apply custom attribute filters that can be converted to database queries
     * This method dynamically handles custom attributes by analyzing the accessor method
     * and attempting to convert it to database-level filtering operations
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $key The custom attribute key
     * @param mixed $value The filter value
     * @return bool True if the filter was applied, false if not supported
     */
    private function applyCustomAttributeFilter($query, $key, $value)
    {
        // Get the model instance to check available relationships
        $request = request();
        $modelClass = decrypt($request->model);
        $model = new $modelClass();

        try {
            // Extract actual filter value from new structure {value, searchMode}
            $filterValue = $value;
            if (is_array($value) && isset($value['value'])) {
                $filterValue = $value['value'];
            }

            \Log::info("Applying custom attribute filter for {$key} with value: " . json_encode($filterValue));

            // Get the accessor method name
            $accessorMethod = 'get' . str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $key))) . 'Attribute';

            \Log::info("Trying to find accessor method: {$accessorMethod} for key: {$key}");

            if (!method_exists($model, $accessorMethod)) {
                \Log::info("Accessor method {$accessorMethod} does not exist");
                return false;
            }

            // Use reflection to analyze the accessor method
            $reflection = new \ReflectionMethod($model, $accessorMethod);
            $methodSource = $this->getMethodSource($reflection);

            if (!$methodSource) {
                \Log::info("Could not analyze accessor method for {$key}");
                return false;
            }

            \Log::info("Method source for {$key}: " . trim($methodSource));

            // Analyze the accessor method to determine how to filter it
            $filterApplied = $this->analyzeAndApplyCustomAttributeFilter($query, $key, $filterValue, $methodSource, $model);

            if ($filterApplied) {
                \Log::info("Successfully applied dynamic filter for custom attribute: {$key}");
                return true;
            } else {
                \Log::info("Could not apply dynamic filter for custom attribute: {$key}");
            }
        } catch (\Exception $e) {
            \Log::warning("Failed to apply custom attribute filter for {$key}: " . $e->getMessage());
        }

        return false;
    }

    /**
     * 🚀 NEW: Get method source code for analysis
     */
    private function getMethodSource(\ReflectionMethod $method)
    {
        try {
            $filename = $method->getFileName();
            $startLine = $method->getStartLine() - 1;
            $endLine = $method->getEndLine();
            $length = $endLine - $startLine;

            $source = file($filename);
            return implode('', array_slice($source, $startLine, $length));
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 🚀 DYNAMIC: Analyze accessor method and apply appropriate filter
     */
    private function analyzeAndApplyCustomAttributeFilter($query, $key, $value, $methodSource, $model)
    {
        // Convert value to boolean if needed - handle string "true"/"false" properly
        $boolValue = null;
        if ($value === 'true' || $value === true) {
            $boolValue = true;
        } elseif ($value === 'false' || $value === false) {
            $boolValue = false;
        } else {
            $boolValue = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        \Log::info("Analyzing custom attribute {$key} with original value: " . json_encode($value) . ", converted to bool: " . json_encode($boolValue));

        // Pattern 1: Check for relationship existence patterns like "->relationName()->exists()"
        if (preg_match('/\$this->(\w+)\(\)->exists\(\)/', $methodSource, $matches)) {
            $relationName = $matches[1];

            \Log::info("Found relationship existence pattern for {$key}: relation = {$relationName}");

            if (method_exists($model, $relationName)) {
                if ($boolValue === true) {
                    \Log::info("Applying whereHas for {$relationName} (true condition)");
                    $query->whereHas($relationName);
                } elseif ($boolValue === false) {
                    \Log::info("Applying whereDoesntHave for {$relationName} (false condition)");
                    $query->whereDoesntHave($relationName);
                }
                return true;
            } else {
                \Log::warning("Relationship method {$relationName} does not exist on model");
            }
        }

        // Pattern 2: Check for database field comparisons like "->field > 0"
        if (preg_match('/\$this->(\w+)\s*([><=!]+)\s*(\d+)/', $methodSource, $matches)) {
            $fieldName = $matches[1];
            $operator = $matches[2];
            $compareValue = (int)$matches[3];

            // Convert the comparison to appropriate database query
            if ($boolValue === true) {
                $query->where($fieldName, $operator, $compareValue);
            } elseif ($boolValue === false) {
                // Invert the operator for false condition
                $invertedOperator = $this->invertOperator($operator);
                $query->where($fieldName, $invertedOperator, $compareValue);
            }
            return true;
        }

        // Pattern 3: Check for relationship with field conditions like "->relationship ? ->relationship->field : false"
        if (preg_match('/\$this->(\w+)\s*\?\s*\$this->\1->(\w+)\s*:\s*false/', $methodSource, $matches)) {
            $relationName = $matches[1];
            $fieldName = $matches[2];

            if (method_exists($model, $relationName)) {
                if ($boolValue === true) {
                    $query->whereHas($relationName, function ($q) use ($fieldName) {
                        $q->whereNotNull($fieldName);
                    });
                } elseif ($boolValue === false) {
                    $query->whereDoesntHave($relationName)
                          ->orWhereHas($relationName, function ($q) use ($fieldName) {
                              $q->whereNull($fieldName);
                          });
                }
                return true;
            }
        }

        // Pattern 4: Check for simple relationship field access like "->relationship->field"
        if (preg_match('/\$this->(\w+)->(\w+)/', $methodSource, $matches)) {
            $relationName = $matches[1];
            $fieldName = $matches[2];

            if (method_exists($model, $relationName)) {
                if ($boolValue !== null) {
                    $query->whereHas($relationName, function ($q) use ($fieldName, $boolValue) {
                        if (is_bool($boolValue)) {
                            $q->where($fieldName, $boolValue);
                        } else {
                            $q->where($fieldName, 'like', "%{$value}%");
                        }
                    });
                }
                return true;
            }
        }

        // Pattern 5: Check for direct field access patterns
        if (preg_match('/\$this->(\w+)/', $methodSource, $matches)) {
            $fieldName = $matches[1];

            // Check if this field exists in the database
            $tableName = $model->getTable();
            $databaseColumns = \Schema::getColumnListing($tableName);

            if (in_array($fieldName, $databaseColumns)) {
                if ($boolValue !== null) {
                    if (is_bool($boolValue)) {
                        $query->where($fieldName, $boolValue);
                    } else {
                        $query->where($fieldName, 'like', "%{$value}%");
                    }
                }
                return true;
            }
        }

        return false;
    }

    /**
     * 🚀 Helper: Invert comparison operators
     */
    private function invertOperator($operator)
    {
        $inversions = [
            '>' => '<=',
            '>=' => '<',
            '<' => '>=',
            '<=' => '>',
            '=' => '!=',
            '!=' => '=',
            '==' => '!=',
            '!=' => '=='
        ];

        return $inversions[$operator] ?? $operator;
    }

    /**
     * Dynamic delete model item.
     */
    public function delete(Request $request)
    {
        $request->validate([
            'model' => 'required',
            'id'    => 'required',
        ]);

        DB::beginTransaction();

        $builderHelper = new BuilderHelper();
        if (!empty($request->permission)) {
            $builderHelper->permissionCheck($request, 'delete');
        }

        $model = decrypt($request->model);
        $model = new $model();
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
                'data'  => 'required',
            ],
            [
                'data.required' => 'Fields are required',
            ]
        );

        $errorMessages = [];

        // Get model to detect custom attributes
        $modelClass = decrypt($request->model);
        $model = new $modelClass();
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
                $errorMessages[] = 'The ' . $value['label'] . ' is required';
            }
        }

        if (count($errorMessages) > 0) {
            throw ValidationException::withMessages($errorMessages);
        }
    }

    /**
     * 🚀 NEW: Apply post-query optimization for remaining N+1 issues
     */
    private function applyPostQueryOptimization($paginatedResults, $customAttributes, $relationships)
    {
        $items = $paginatedResults->getCollection();

        if ($items->isEmpty()) {
            return;
        }

        // 🚀 Batch load missing relationships that weren't caught by eager loading
        $this->batchLoadMissingRelationships($items, $customAttributes);

        // 🚀 Optimize custom attribute calculations
        $this->optimizeCustomAttributeCalculations($items, $customAttributes);
    }

    /**
     * 🚀 NEW: Batch load relationships that might be called by custom attributes
     */
    private function batchLoadMissingRelationships($items, $customAttributes)
    {
        $model = $items->first();
        if (!$model) {
            return;
        }

        $toLoad = [];

        // Analyze custom attributes for additional relationship loading needs
        foreach ($customAttributes as $attribute) {
            $accessorMethod = 'get' . str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $attribute))) . 'Attribute';

            if (method_exists($model, $accessorMethod)) {
                try {
                    $reflection = new \ReflectionMethod($model, $accessorMethod);
                    $methodSource = $this->getMethodSource($reflection);

                    // Look for relationships that might not be loaded
                    if (preg_match_all('/\$this->([a-zA-Z_][a-zA-Z0-9_]*)\(\)/', $methodSource, $matches)) {
                        foreach ($matches[1] as $relationMethod) {
                            if (method_exists($model, $relationMethod) && !$model->relationLoaded($relationMethod)) {
                                $toLoad[] = $relationMethod;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    // Continue silently if reflection fails
                }
            }
        }

        // Load missing relationships in batch
        if (!empty($toLoad)) {
            $items->load(array_unique($toLoad));
        }
    }

    /**
     * 🚀 NEW: Optimize custom attribute calculations to prevent repeated queries
     */
    private function optimizeCustomAttributeCalculations($items, $customAttributes)
    {
        // This method can be extended to cache expensive custom attribute calculations
        // For now, the batch loading above should handle most N+1 issues

        // Example: Pre-calculate expensive custom attributes and cache them
        foreach ($customAttributes as $attribute) {
            // Check if this attribute requires database queries
            $sampleItem = $items->first();
            if (!$sampleItem) {
                continue;
            }

            try {
                // Pre-trigger the custom attribute to ensure it's calculated
                // This will use the batch-loaded relationships
                $sampleItem->getAttribute($attribute);
            } catch (\Exception $e) {
                // Continue silently if attribute access fails
            }
        }
    }
}
