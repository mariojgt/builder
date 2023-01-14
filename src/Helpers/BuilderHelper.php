<?php

namespace Mariojgt\Builder\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * This class will handle the autenticator 2fa authentication.
 */
class BuilderHelper
{
    /**
     * If the permission array is not empty then the user must have the permission to access else we need to check.
     *
     * @param Request $request
     * @param string  $type    // create | edit | delete |read
     *
     * @return bool [true|false]
     */
    public function permissionCheck(Request $request, $checkType)
    {
        // Decrypt the permission in order to avoid manupilation
        $request->request->add(['permission' => decrypt($request->permission)]); //add request
        // Get the user based in the guard
        $user = Auth::guard($request->permission['guard'])->user();
        $type = $request->permission['type'];
        $classMethod = '';
        // Type
        if ($type == 'permission') {
            $classMethod = 'hasPermissionTo';
        } else {
            $classMethod = 'hasRole';
        }

        // Start the permission check default as false
        $autorized = false;
        // Try to get the user permission if the model don't have the permission then we need to check
        try {
            $autorized = $user->$classMethod($request->permission['key'][$checkType]);
        } catch (\Throwable $th) {
            throw ValidationException::withMessages([
                'permission' => 'You don\'t have the permission to ' . $checkType . ' this item',
            ]);
        }

        // Check if the user has the permission
        if (!$autorized) {
            throw ValidationException::withMessages([
                'permission' => 'You don\'t have the permission to ' . $checkType . ' this item',
            ]);
        }
        // If is authorized return true
        return $autorized;
    }

    /**
     * Generict assing and validation the data information.
     *
     * @param mixed $type
     * @param mixed $model
     * @param mixed $key
     * @param mixed $value
     * @param mixed $column
     *
     * @return Model [model]
     */
    public function generictValidation($model, $column)
    {
        // Get the value
        $value = $column['value'];
        // Get the key
        $key = $column['key'];
        // Get the type
        $type = $column['type'];

        switch ($type) {
            case 'text':
                $model->$key = $value;
                break;
            case 'slug':
                $model->$key = Str::slug($value);
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
}
