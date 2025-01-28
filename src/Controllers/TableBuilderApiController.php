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
     * Handle data display to the table.
     *
     * @param Request $request
     *
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

        // Get the base columns
        $columns = $rawColumns->where('type', '!=', 'media')
            ->where('type', '!=', 'pivot_model')
            ->pluck('key');

        // Check if updated_at exists in the model's table
        $hasTimestamps = $model->timestamps;
        if ($hasTimestamps) {
            // Add updated_at to the columns if it's not already included
            if (!$columns->contains('updated_at')) {
                $columns->push('updated_at');
            }
        }

        // Start the query
        $query = $model->query();

        // Handle filters
        if ($request->has('filters')) {
            $filters = $request->filters;

            foreach ($filters as $key => $value) {
                if (empty($value)) {
                    continue;
                }

                $column = $rawColumns->firstWhere('key', $key);
                if (!$column) {
                    continue;
                }

                switch ($column['type']) {
                    case 'model_search':
                        $query->where($key, $value);
                        break;

                    case 'boolean':
                        $query->where($key, $value === 'true');
                        break;

                    case 'date':
                        if (!empty($value['from'])) {
                            $query->whereDate($key, '>=', Carbon::parse($value['from']));
                        }
                        if (!empty($value['to'])) {
                            $query->whereDate($key, '<=', Carbon::parse($value['to']));
                        }
                        break;

                    case 'select':
                        $query->where($key, $value);
                        break;

                    default:
                        $query->where($key, 'LIKE', "%{$value}%");
                        break;
                }
            }
        }

        // Handle search
        if ($request->has('search')) {
            $sortableColumns = $rawColumns->filter(function ($column) {
                return $column['sortable'] == true;
            });
            $columnSearch = $sortableColumns->pluck('key');
            $query->where(function ($q) use ($request, $columnSearch) {
                foreach ($columnSearch as $column) {
                    $q->orWhere($column, 'like', '%' . $request->search . '%');
                }
            });
        }

        // Handle sorting
        if (!empty($request->sort)) {
            $query->orderBy($request->sort, $request->direction ?? 'asc');
        }

        // Add any needed relationships for model_search
        $relationships = $rawColumns->where('type', 'model_search')
            ->pluck('relation')
            ->filter();

        if ($relationships->isNotEmpty()) {
            $query->with($relationships->toArray());
        }

        // Execute query with pagination and column selection
        $modelPaginated = $query->select($columns->toArray())->paginate($request->perPage ?? 10);

        // Process the data through builder helper
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
     * Store method for creating a new row.
     *
     * @param Request $request
     *
     * @return array
     */
    public function store(Request $request)
    {
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
            if ($column['type'] == 'media') {
                $mediaRelations[] = $column;
            } elseif ($column['type'] == 'pivot_model') {
                $pivotRelations[] = $column;
            } else {
                $model = $builderHelper->genericValidation($model, $column);
            }
        }

        $model->save();

        foreach ($mediaRelations as $mediaRelation) {
            foreach ($mediaRelation['value'] as $item) {
                $model->media()->create([
                    'media_id' => $item['id'],
                ]);
            }
        }

        foreach ($pivotRelations as $dynamicRelation) {
            $model->{$dynamicRelation['relation']}()->detach();
            $idsSync = collect($dynamicRelation['value'])->pluck('id');
            if ($idsSync->count() > 0) {
                $model->{$dynamicRelation['relation']}()->attach($idsSync);
            }
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Item created successfully',
        ]);
    }

    /**
     * Update the table with new data.
     *
     * @param Request $request
     *
     * @return array
     */
    public function update(Request $request)
    {
        DB::beginTransaction();

        $this->dynamicFieldValidation($request);

        $builderHelper = new BuilderHelper();
        if (!empty($request->permission)) {
            $builderHelper->permissionCheck($request, 'update');
        }

        $model = decrypt($request->model);
        $model = new $model();

        $model = $model->find($request->id);

        $rawColumns = collect($request->data);
        $mediaRelations = [];
        $pivotRelations = [];

        foreach ($rawColumns as $column) {
            if ($column['type'] == 'media') {
                $mediaRelations[] = $column;
            } elseif ($column['type'] == 'pivot_model') {
                $pivotRelations[] = $column;
            } else {
                $model = $builderHelper->genericValidation($model, $column);
            }
        }

        $model->save();

        if ($mediaRelations) {
            $model->media()->delete();
            foreach ($mediaRelations as $mediaRelation) {
                foreach ($mediaRelation['value'] as $item) {
                    $model->media()->create([
                        'media_id' => $item['id'],
                    ]);
                }
            }
        }

        foreach ($pivotRelations as $dynamicRelation) {
            $model->{$dynamicRelation['relation']}()->detach();
            $idsSync = collect($dynamicRelation['value'])->pluck('id');
            if ($idsSync->count() > 0) {
                $model->{$dynamicRelation['relation']}()->attach($idsSync);
            }
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Item updated successfully',
        ]);
    }

    /**
     * Dynamic delete model item.
     *
     * @return array
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
}
