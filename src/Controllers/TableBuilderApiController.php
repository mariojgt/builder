<?php

namespace Mariojgt\Builder\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Helpers\CastleHelper;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Mariojgt\Builder\Helpers\AutenticatorHandle;

/**
 * This controller will handle the crud for the table builder, note that this is a generic controller that will be use to create the forms more info check the documentation
 */
class TableBuilderApiController extends Controller
{

    /**
     * If the permission array is not empty then the user must have the permission to access else we need to check
     * @param Request $request
     * @param string $type // create | edit | delete |read
     *
     * @return bool [true|false]
     */
    private function permissionCheck(Request $request, $checkType)
    {
        // Decrypt the permission in order to avoid manupilation
        $request->request->add(['permission' => decrypt($request->permission)]); //add request
        // Get the user based in the guard
        $user        = Auth::guard($request->permission['guard'])->user();
        $type        = $request->permission['type'];
        $classMethod = '';
        // Type
        if ($type == 'permission') {
            $classMethod = 'hasPermissionTo';
        } else {
            $classMethod = 'hasRole';
        }

        // Check if the user has the permission
        try {
            $autorized = $user->$classMethod($request->permission['key'][$checkType]);
        } catch (\Throwable $th) {
            throw ValidationException::withMessages([
                'permission' => 'You don\'t have the permission to ' . $checkType . ' this item',
            ]);
        }

        return $autorized;
    }

    /**
     * This is the main table builder and will handle the data display to the table
     * @param Request $request
     *
     * @return [type]
     */
    public function index(Request $request)
    {
        $request->validate([
            'model'   => 'required',
            'columns' => 'required',
        ]);

        if (!empty($request->permission)) {
            // First check if the user has the permission to access
            $this->permissionCheck($request, 'index');
        }

        // Fist we need to decrypt the model and instantiate it
        $model = decrypt($request->model);
        $model = new $model;

        // Get the columns
        $rawColumns = collect($request->columns);
        $columns    = $rawColumns->pluck('key');

        // Check if the search is not empty
        if ($request->has('search')) {
            // Get the columns that are sortable
            $sortableColumns = $rawColumns->filter(function ($column) use ($model) {
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
        $data = $model->select($columns->toArray())->paginate($request->perPage ?? 10);

        return $data;
    }

    /**
     * The store method for the table when the user try to create a new row
     * @param Request $request
     *
     * @return json [type]
     */
    public function store(Request $request)
    {
        $request->validate(
            [
                'model'        => 'required',
                'data'         => 'required',
                'data.*.value' => 'required',
            ],
            [
                'data'                  => 'Field are required',
                'data.*.value.required' => 'The value is required',
            ]
        );

        if (!empty($request->permission)) {
            // First check if the user has the permission to access
            $this->permissionCheck($request, 'store');
        }

        // Fist we need to decrypt the model and instantiate it
        $model = decrypt($request->model);
        $model = new $model;

        // Get the columns
        $rawColumns = collect($request->data);

        // Create the model
        $model = new $model();

        // Loop the columns and set the value and validate acording to the type
        foreach ($rawColumns as $key => $column) {
            $model = $this->generictValidation($model, $column);
        }

        // Save the model
        $model->save();

        // Return the response
        return response()->json([
            'success' => true,
            'message' => 'Item created successfully',
        ]);
    }

    /**
     * This fuction is goin to update the datable that comes from the table
     * @param Request $request
     *
     * @return json [message]
     */
    public function update(Request $request)
    {
        $request->validate(
            [
                'model'        => 'required',
                'id'           => 'required',
                'data'         => 'required',
                'data.*.value' => 'required',
            ],
            [
                'data'                  => 'Field are required',
                'data.*.value.required' => 'The value is required',
            ]
        );

        if (!empty($request->permission)) {
            // First check if the user has the permission to access
            $this->permissionCheck($request, 'update');
        }

        // Fist we need to decrypt the model and instantiate it
        $model = decrypt($request->model);
        $model = new $model;

        // Find the model item
        $model = $model->find($request->id);

        // Get the columns
        $rawColumns = collect($request->data);

        // Loop the columns and set the value and validate acording to the type
        foreach ($rawColumns as $key => $column) {
            $model = $this->generictValidation($model, $column);
        }

        // Save the model
        $model->save();

        // Return the response
        return response()->json([
            'success' => true,
            'message' => 'Item updated successfully',
        ]);
    }

    /**
     * Genercit assing and validation the data inforamtion
     * @param mixed $type
     * @param mixed $model
     * @param mixed $key
     * @param mixed $value
     * @param mixed $column
     *
     * @return Model [model]
     */
    private function generictValidation($model, $column)
    {
        // Get the value
        $value = $column['value'];
        // Get the key
        $key   = $column['key'];
        // Get the type
        $type  = $column['type'];

        switch ($type) {
            case 'text':
                $model->$key = $value;
                break;
            case 'email':
                // Make sure the email is valid else trow an error validation message
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    throw ValidationException::withMessages([$column['key'] => 'Must be a valid email.']);
                } else {
                    $model->$key = $value;
                }
                break;
            case 'date':
                // Cast the value to date
                $model->$key = Carbon::parse($value);
                break;
            default:
                $model->$key = $value;
                break;
        }
        return $model;
    }

    /**
     * Dynamic delete model item.
     *
     * @param Request $request
     *
     */
    public function delete(Request $request)
    {
        $request->validate([
            'model' => 'required',
            'id'  => 'required',
        ]);

        if (!empty($request->permission)) {
            // First check if the user has the permission to access
            $this->permissionCheck($request, 'delete');
        }

        // Fist we need to decrypt the model and instantiate it
        $model = decrypt($request->model);
        $model = new $model;

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

        // Return the response
        return response()->json([
            'success' => true,
            'message' => 'Item deleted successfully',
        ]);
    }
}
