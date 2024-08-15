<?php

namespace Mariojgt\Builder\Controllers;

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
            'model'   => 'required',
            'columns' => 'required',
        ]);

        $builderHelper = new BuilderHelper();
        if (!empty($request->permission)) {
            $builderHelper->permissionCheck($request, 'index');
        }

        $model = decrypt($request->model);
        $model = new $model();

        $rawColumns = collect($request->columns);
        $columns = $rawColumns->where('type', '!=', 'media')
            ->where('type', '!=', 'pivot_model')
            ->pluck('key');

        if ($request->has('search')) {
            $sortableColumns = $rawColumns->filter(function ($column) {
                return $column['sortable'] == true;
            });
            $columnSearch = $sortableColumns->pluck('key');

            $model = $model->where(function ($query) use ($request, $columnSearch) {
                foreach ($columnSearch as $column) {
                    $query->orWhere($column, 'like', '%' . $request->search . '%');
                }
            });
        }

        if (!empty($request->sort)) {
            $model = $model->orderBy($request->sort, $request->direction ?? 'asc');
        }

        $modelPaginated = $model->select($columns->toArray())->paginate($request->perPage ?? 10);

        $data = $builderHelper->columnReplacements($modelPaginated, $rawColumns);

        return [
            'data'           => $data,
            'current_page'   => $modelPaginated->currentPage(),
            'first_page_url' => $modelPaginated->url(1),
            'from'           => $modelPaginated->firstItem(),
            'last_page'      => $modelPaginated->lastPage(),
            'last_page_url'  => $modelPaginated->url($modelPaginated->lastPage()),
            'links'          => $modelPaginated->links(),
            'next_page_url'  => $modelPaginated->nextPageUrl(),
            'path'           => $modelPaginated->path(),
            'per_page'       => $modelPaginated->perPage(),
            'prev_page_url'  => $modelPaginated->previousPageUrl(),
            'to'             => $modelPaginated->lastItem(),
            'total'          => $modelPaginated->total(),
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
