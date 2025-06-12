<?php

namespace Mariojgt\Builder\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Mariojgt\Builder\Helpers\BuilderHelper;
use Illuminate\Validation\ValidationException;

class TableBuilderApiController extends Controller
{
    /**
     * Handle data display to the table with AUTOMATIC relationship detection.
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
        $model = new $model();
        $rawColumns = collect($request->columns);

        // 🚀 AUTO-DETECT RELATIONSHIPS from dot notation
        $relationships = $this->autoDetectRelationships($rawColumns);

        // Get base columns (no relationships)
        $columns = $this->getBaseColumns($rawColumns);

        // Add timestamps if model has them
        $hasTimestamps = $model->timestamps;
        if ($hasTimestamps && !$columns->contains('updated_at')) {
            $columns->push('updated_at');
        }
        // 🚀 SMART SELECT: Add foreign keys for relationships
        $selectColumns = $this->getSmartSelectColumns($rawColumns, $relationships);

        // Start query with AUTO-LOADED relationships
        $query = $model->query();
        if (!empty($relationships)) {
            $query->with($relationships);
        }

        // Handle filters (with relationship support)
        if ($request->has('filters')) {
            $this->applyFilters($query, $request->filters, $rawColumns);
        }

        // Handle search (with relationship support)
        if ($request->has('search')) {
            $this->applySearch($query, $request->search, $rawColumns);
        }

        // Handle sorting with relationship support
        if (!empty($request->sort)) {
            $this->applySorting($query, $request->sort, $request->direction ?? 'asc', $rawColumns);
        }

        // Execute query with smart column selection
        $modelPaginated = $query->select($selectColumns)->paginate($request->perPage ?? 10);

        // Process data (with relationship values)
        $data = $builderHelper->columnReplacements($modelPaginated, $rawColumns);

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
     * 🚀 AUTO-DETECT relationships from column keys with fallback support
     * Examples:
     * - 'reportedData.comp_name' → loads 'reportedData'
     * - 'reportedTempData.product.name' → loads 'reportedTempData.product'
     * - 'reportedTempData.product.product_url|reportedData.comp_link' → loads both 'reportedTempData.product' and 'reportedData'
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

            if (!empty($parts)) {
                // Single level: reportedData.comp_name → 'reportedData'
                // Multi level: reportedTempData.product.name → 'reportedTempData.product'
                $relationshipPath = implode('.', $parts);
                $relationships[] = $relationshipPath;

                // Also add intermediate paths for nested relationships
                // reportedTempData.product.category.name → ['reportedTempData', 'reportedTempData.product', 'reportedTempData.product.category']
                $currentPath = '';
                foreach ($parts as $part) {
                    $currentPath = $currentPath ? "$currentPath.$part" : $part;
                    $relationships[] = $currentPath;
                }
            }
        }
    }

    /**
     * Get base columns (exclude relationship fields)
     */
    private function getBaseColumns($rawColumns)
    {
        return $rawColumns->filter(function ($column) {
            // Only include direct model columns (no dot notation)
            return strpos($column['key'], '.') === false;
        })
        ->where('type', '!=', 'media')
        ->where('type', '!=', 'pivot_model')
        ->pluck('key');
    }

    /**
     * 🚀 SMART SELECT: Get columns with foreign keys for relationships
     */
    private function getSmartSelectColumns($rawColumns, $relationships)
    {
        // Get base columns
        $baseColumns = $rawColumns->filter(function ($column) {
            return strpos($column['key'], '.') === false;
        })
        ->where('type', '!=', 'media')
        ->where('type', '!=', 'pivot_model')
        ->pluck('key')
        ->toArray();

        // Get the model to determine the correct primary key
        $request = request();
        $modelClass = decrypt($request->model);
        $model = new $modelClass();
        $primaryKey = $model->getKeyName(); // This will get the correct primary key

        // Always include primary key (use the model's actual primary key)
        if (!in_array($primaryKey, $baseColumns)) {
            array_unshift($baseColumns, $primaryKey);
        }

        // If we have relationships, just select all columns to be safe
        // This ensures foreign keys are available for relationship loading
        if (!empty($relationships)) {
            return ['*'];
        }

        return $baseColumns;
    }

    /**
     * Apply filters with AUTOMATIC relationship support and fallback
     */
    private function applyFilters($query, $filters, $rawColumns)
    {
        foreach ($filters as $key => $value) {
            if (empty($value)) {
                continue;
            }

            $column = $rawColumns->firstWhere('key', $key);
            if (!$column) {
                continue;
            }

            // Handle fallback relationships (separated by |)
            if (strpos($key, '|') !== false) {
                $fallbackKeys = explode('|', $key);
                $query->where(function ($fallbackQuery) use ($fallbackKeys, $value, $column) {
                    foreach ($fallbackKeys as $fallbackKey) {
                        $fallbackKey = trim($fallbackKey);
                        $this->applyFilterToSingleKey($fallbackQuery, $fallbackKey, $value, $column, true); // true for OR condition
                    }
                });
            } else {
                $this->applyFilterToSingleKey($query, $key, $value, $column);
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
     * 🚀 Apply sorting with automatic relationship detection
     */
    private function applySorting($query, $sort, $direction, $rawColumns)
    {
        // Check if this is a relationship field
        if (strpos($sort, '.') !== false) {
            // For relationship fields, we can't sort directly
            // Instead, we'll use a subquery or join approach
            $this->applyRelationshipSorting($query, $sort, $direction);
        } else {
            // Direct model field - normal sorting
            $query->orderBy($sort, $direction);
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
                // If relationship doesn't exist, silently skip this search term
                \Log::warning("Search failed for relationship '{$relationPath}': " . $e->getMessage());
            }
        } else {
            // Search in main model
            try {
                $query->$method($key, 'like', '%' . $search . '%');
            } catch (\Exception $e) {
                // If column doesn't exist, silently skip this search term
                \Log::warning("Search failed for column '{$key}': " . $e->getMessage());
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
            // Use a subquery to sort by relationship field
            $model = $query->getModel();

            // For simple one-level relationships, we can use a join
            if (count($parts) === 1) {
                $relationName = $parts[0];

                // Try to get the relationship instance
                if (method_exists($model, $relationName)) {
                    $relation = $model->$relationName();

                    // Check if it's a belongsTo relationship (most common case)
                    if ($relation instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo) {
                        $foreignKey = $relation->getForeignKeyName();
                        $ownerKey = $relation->getOwnerKeyName();
                        $relatedTable = $relation->getRelated()->getTable();
                        $mainTable = $model->getTable();

                        $query->leftJoin($relatedTable, "{$mainTable}.{$foreignKey}", '=', "{$relatedTable}.{$ownerKey}")
                              ->orderBy("{$relatedTable}.{$attribute}", $direction)
                              ->select("{$mainTable}.*"); // Only select from main table

                        return;
                    }
                }
            }

            // Fallback: For complex relationships or when join doesn't work,
            // just order by ID to avoid errors (not ideal but safe)
            $query->orderBy('id', $direction);

        } catch (\Exception $e) {
            // If anything goes wrong, fallback to ordering by ID
            \Log::warning("Could not sort by relationship field '{$sort}': " . $e->getMessage());
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
                    if (!empty($value['from'])) {
                        $query->$methodDate($key, '>=', Carbon::parse($value['from']));
                    }
                    if (!empty($value['to'])) {
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
            // If column doesn't exist, silently skip this filter
            \Log::warning("Filter failed for column '{$key}': " . $e->getMessage());
        }
    }

    /**
     * Apply search with AUTOMATIC relationship support and fallback
     */
    private function applySearch($query, $search, $rawColumns)
    {
        $sortableColumns = $rawColumns->filter(function ($column) {
            return $column['sortable'] == true;
        });

        $query->where(function ($q) use ($search, $sortableColumns) {
            foreach ($sortableColumns as $column) {
                $key = $column['key'];

                // Handle fallback relationships (separated by |)
                if (strpos($key, '|') !== false) {
                    $fallbackKeys = explode('|', $key);
                    foreach ($fallbackKeys as $fallbackKey) {
                        $fallbackKey = trim($fallbackKey);
                        $this->applySearchToSingleKey($q, $search, $fallbackKey, true); // true for OR condition
                    }
                } else {
                    $this->applySearchToSingleKey($q, $search, $key);
                }
            }
        });
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

            $rawColumns = collect($request->data);
            $mediaRelations = [];
            $pivotRelations = [];

            foreach ($rawColumns as $column) {
                // 🚀 SKIP relationship fields automatically (they can't be directly saved)
                if (strpos($column['key'], '.') !== false) {
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

            $rawColumns = collect($request->data);
            $mediaRelations = [];
            $pivotRelations = [];

            foreach ($rawColumns as $column) {
                // 🚀 SKIP relationship fields automatically (they can't be directly saved)
                if (strpos($column['key'], '.') !== false) {
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
     * Dynamic field validation
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
        foreach ($request->data as $value) {
            // 🚀 SKIP validation for relationship fields (they're read-only)
            if (strpos($value['key'], '.') !== false) {
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
     * Apply date filter with proper handling of from/to format
     */
    private function applyDateFilter($query, $key, $value, $useOr = false)
    {
        // Handle both string dates and array format from frontend
        if (is_string($value)) {
            // Simple date string
            if ($useOr) {
                $query->orWhereDate($key, $value);
            } else {
                $query->whereDate($key, $value);
            }
        } elseif (is_array($value)) {
            // Date range format: {"from": "2025-06-12", "to": "2025-06-12"}
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
            // If relationship doesn't exist, silently skip this filter
            \Log::warning("Filter failed for relationship '{$relationPath}': " . $e->getMessage());
        }
    }
}
