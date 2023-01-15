<?php

namespace Mariojgt\Builder\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Mariojgt\Magnifier\Resources\MediaResource;

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
                // Make sure the slug is unique else trow an error validation message
                if ($column['unique']) {
                    $modelCheck = $model::class;
                    if ($model->id) {
                        $modelCheck = $modelCheck::where($key, Str::slug($value))->where('id', '!=', $model->id)->first();
                    } else {
                        $modelCheck = $modelCheck::where($key, Str::slug($value))->first();
                    }
                    if ($modelCheck) {
                        throw ValidationException::withMessages([$column['key'] => 'The slug must be unique.']);
                    }
                }
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
            case 'number':
                // Check if the value is numeric
                if (!is_numeric($value)) {
                    throw ValidationException::withMessages([$column['key'] => 'Must be a valid number.']);
                }
                // Cast the value to number
                $model->$key = (int) $value;
                break;
            case 'model_search':
                // Check if is a single relation else is a pivot table
                $valueData = collect($value)->pluck('id');
                if ($column['singleSearch']) {
                    $model->$key = $valueData->first();
                } else {
                    $model->$key = json_encode($valueData);
                }
                break;
            default:
                break;
        }

        return $model;
    }

    /**
     * Replace the column value.
     *
     * @param $modelPaginated // The model paginated
     * @param $rawColumns     // The raw columns
     *
     * @return [Collection]
     */
    public function columnReplacements($modelPaginated, $rawColumns)
    {
        return $modelPaginated->map(function ($item) use ($rawColumns) {
            // Loop the columns
            foreach ($rawColumns as $key => $column) {
                // Check if the column is media
                if (!empty($column['type'])) {
                    switch ($column['type']) {
                        case 'media':
                            $field = $column['key'];
                            // Check if the media is not empty
                            if ($item->media->isNotEmpty()) {
                                $mediaData = [];
                                foreach ($item->media as $mediaKey => $mediaItem) {
                                    $mediaData[] = new MediaResource($mediaItem->media);
                                }
                                $item->$field = collect($mediaData);
                            } else {
                                // Set the image
                                $item->$field = null;
                            }
                            break;
                        case 'model_search':
                            $field         = $column['key'];
                            $modelRelation = decrypt($column['model']);
                            $columnFilters = collect($column['columns'])->where('sortable', true)->pluck('key');

                            if ($column['singleSearch']) {
                                $modelData    = $modelRelation::where('id', $item->$field)
                                    ->select($columnFilters->toArray())
                                    ->first();
                                $item->$field = $modelData;
                            } else {
                                $modelData    = $modelRelation::whereIn('id', json_decode($item->$field))
                                    ->select($columnFilters->toArray())
                                    ->get();
                                $item->$field = $modelData;
                            }
                            break;
                        default:
                            # code...
                            break;
                    }
                }
            }
            return $item;
        });
    }
}
