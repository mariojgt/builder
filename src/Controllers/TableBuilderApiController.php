<?php

namespace Mariojgt\Builder\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Mariojgt\Builder\Helpers\BuilderHelper;
use Illuminate\Validation\ValidationException;

/**
 * This controller will handle the crud for the table builder, note that this is a generic controller that will be use to create the forms more info check the documentation.
 */
class TableBuilderApiController extends Controller
{
    /**
     * This is the main table builder and will handle the data display to the table.
     *
     * @param Request $request
     *
     * @return json [$data]
     */
    public function index(Request $request)
    {
        $request->validate([
            'model'   => 'required',
            'columns' => 'required',
        ]);

        $builderHelper = new BuilderHelper();
        // Check if the permission can be checked
        if (!empty($request->permission)) {
            // First check if the user has the permission to access
            $builderHelper->permissionCheck($request, 'index');
        }

        // Fist we need to decrypt the model and instantiate it
        $model = decrypt($request->model);
        $model = new $model();

        // Get the columns
        $rawColumns = collect($request->columns);
        $columns = $rawColumns->where('type', '!=', 'media')
            ->where('type', '!=', 'pivot_model')
            ->pluck('key');

        // Check if the search is not empty
        if ($request->has('search')) {
            // Get the columns that are sortable
            $sortableColumns = $rawColumns->filter(function ($column) {
                // If shortalbe is true, then we can sort it
                if ($column['sortable'] == true) {
                    return true;
                }
            });
            // Get the columns that are searchable
            $columnSearch = $sortableColumns->pluck('key');

            // Get the search value
            $model = $model->where(function ($query) use ($request, $columnSearch) {
                // Search using concatination
                foreach ($columnSearch as $column) {
                    $query->orWhere($column, 'like', '%' . $request->search . '%');
                }
            });
        }

        // Check if the sort is not empty
        if (!empty($request->sort)) {
            // Get the sort value
            $model = $model->orderBy($request->sort, $request->direction ?? 'asc');
        }

        // Get the data based on the columns if is date we need to format it
        $modelPaginated = $model->select($columns->toArray())->paginate($request->perPage ?? 10);

        // Collumns replacement (usefull for media and other things)
        $data = $builderHelper->columnReplacements($modelPaginated, $rawColumns);

        // Return the data using the pagination format
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
     * The store method for the table when the user try to create a new row.
     *
     * @param Request $request
     *
     * @return json [type]
     */
    public function store(Request $request)
    {
        DB::beginTransaction();

        $this->dynamicFieldValidation($request);

        // First check if the user has the permission to access
        $builderHelper = new BuilderHelper();

        // Check if the permission can be checked
        if (!empty($request->permission)) {
            // First check if the user has the permission to access
            $builderHelper->permissionCheck($request, 'store');
        }

        // Fist we need to decrypt the model and instantiate it
        $model = decrypt($request->model);
        $model = new $model();

        // Get the columns
        $rawColumns     = collect($request->data);
        $mediaRelations = [];
        $pivotRelations = [];
        // Create the model
        $model = new $model();

        // Loop the columns and set the value and validate acording to the type
        foreach ($rawColumns as $key => $column) {
            if ($column['type'] == 'media') {
                $mediaRelations[] = $column;
            } elseif ($column['type'] == 'pivot_model') {
                $pivotRelations[] = $column;
            } else {
                $model = $builderHelper->generictValidation($model, $column);
            }
        }

        // Save the model
        $model->save();

        // Link the media using a polymorphic relation
        foreach ($mediaRelations as $key => $mediaRelation) {
            foreach ($mediaRelation['value'] as $key => $item) {
                // Create the polymorphic relation with the media
                $model->media()->create([
                    'media_id' => $item['id'],
                ]);
            }
        }

        if (count($pivotRelations) > 0) {
            // Link the pivot model
            foreach ($pivotRelations as $key => $dynamicRelation) {
                // Detach all the relations
                $model->{$dynamicRelation['relation']}()->detach();
                $idsSync = collect($dynamicRelation['value'])->pluck('id');
                if ($idsSync->count() > 0) {
                    $model->{$dynamicRelation['relation']}()->attach($idsSync);
                }
            }
        }

        DB::commit();
        // Return the response
        return response()->json([
            'success' => true,
            'message' => 'Item created successfully',
        ]);
    }

    /**
     * This fuction is goin to update the datable that comes from the table.
     *
     * @param Request $request
     *
     * @return json [message]
     */
    public function update(Request $request)
    {
        DB::beginTransaction();
        $this->dynamicFieldValidation($request);

        // First check if the user has the permission to access
        $builderHelper = new BuilderHelper();
        // Check if the permission can be checked
        if (!empty($request->permission)) {
            // First check if the user has the permission to access
            $builderHelper->permissionCheck($request, 'update');
        }

        // Fist we need to decrypt the model and instantiate it
        $model = decrypt($request->model);
        $model = new $model();

        // Find the model item
        $model = $model->find($request->id);

        // Get the columns
        $rawColumns     = collect($request->data);
        $mediaRelations = [];
        $pivotRelations = [];

        // Loop the columns and set the value and validate acording to the type
        foreach ($rawColumns as $key => $column) {
            if ($column['type'] == 'media') {
                $mediaRelations[] = $column;
            } elseif ($column['type'] == 'pivot_model') {
                $pivotRelations[] = $column;
            } else {
                $model = $builderHelper->generictValidation($model, $column);
            }
        }

        // Save the model
        $model->save();

        // Handle the media polymorphic relation
        if ($mediaRelations) {
            // Delete the media relations
            $model->media()->delete();
            // Link the media using a polymorphic relation
            foreach ($mediaRelations as $key => $mediaRelation) {
                foreach ($mediaRelation['value'] as $key => $item) {
                    // Create the polymorphic relation with the media
                    $model->media()->create([
                        'media_id' => $item['id'],
                    ]);
                }
            }
        }

        if (count($pivotRelations) > 0) {
            // Link the pivot model
            foreach ($pivotRelations as $key => $dynamicRelation) {
                // Detach all the relations
                $model->{$dynamicRelation['relation']}()->detach();
                $idsSync = collect($dynamicRelation['value'])->pluck('id');
                if ($idsSync->count() > 0) {
                    $model->{$dynamicRelation['relation']}()->attach($idsSync);
                }
            }
        }

        DB::commit();
        // Return the response
        return response()->json([
            'success' => true,
            'message' => 'Item updated successfully',
        ]);
    }

    /**
     * Dynamic delete model item.
     *
     * @return json [$data]
     */
    public function delete(Request $request)
    {
        $request->validate([
            'model' => 'required',
            'id'    => 'required',
        ]);
        DB::beginTransaction();
        // First check if the user has the permission to access
        $builderHelper = new BuilderHelper();
        // Check if the permission can be checked
        if (!empty($request->permission)) {
            // First check if the user has the permission to access
            $builderHelper->permissionCheck($request, 'delete');
        }

        // Fist we need to decrypt the model and instantiate it
        $model = decrypt($request->model);
        $model = new $model();

        // Find the model item
        $modelItem = $model->find($request->id);

        // Get all the model relations
        $relations = $model->getRelations();

        // Loop the relations and delete them
        foreach ($relations as $relation) {
            $model->$relation()->delete();
        }

        // Delete
        $modelItem->delete();

        DB::commit();
        // Return the response
        return response()->json([
            'success' => true,
            'message' => 'Item deleted successfully',
        ]);
    }

    private function dynamicFieldValidation(Request $request)
    {

        $request->validate(
            [
                'model'        => 'required',
                'data'         => 'required',
            ],
            [
                'data'                  => 'Field are required',
            ]
        );

        // Loop individually the fields and validate them
        $errorMessages = [];
        foreach ($request->data as $key => $value) {
            if (empty($value['nullable'])) {
                if (empty($value['value'])) {
                    $errorMessages[] = 'The ' . $value['label'] . ' is required';
                }
            }
        }

        if (count($errorMessages) > 0) {
            throw ValidationException::withMessages($errorMessages);
        }
    }
}
