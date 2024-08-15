<?php

namespace Mariojgt\Builder\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mariojgt\Builder\Enums\FieldTypes;
use Illuminate\Validation\ValidationException;
use Mariojgt\Magnifier\Resources\MediaResource;

class BuilderHelper
{
    /**
     * Check if the user has the necessary permission.
     *
     * @param Request $request
     * @param string  $checkType // create | edit | delete | read
     *
     * @return bool
     */
    public function permissionCheck(Request $request, $checkType)
    {
        $request->request->add(['permission' => decrypt($request->permission)]);
        $user = Auth::guard($request->permission['guard'])->user();
        $type = $request->permission['type'];
        $classMethod = ($type == 'permission') ? 'hasPermissionTo' : 'hasRole';

        try {
            $authorized = $user->$classMethod($request->permission['key'][$checkType]);
        } catch (\Throwable $th) {
            throw ValidationException::withMessages([
                'permission' => 'You don\'t have permission to ' . $checkType . ' this item',
            ]);
        }

        if (!$authorized) {
            throw ValidationException::withMessages([
                'permission' => 'You don\'t have permission to ' . $checkType . ' this item',
            ]);
        }

        return $authorized;
    }

    /**
     * Generic assignment and validation of data.
     *
     * @param mixed $model
     * @param mixed $column
     *
     * @return Model
     */
    public function genericValidation($model, $column)
    {
        $value = $column['value'];
        $key = $column['key'];
        $type = $column['type'];

        switch ($type) {
            case FieldTypes::TEXT->value:
                $model->$key = $value;
                break;
            case FieldTypes::SLUG->value:
                if (isset($column['unique']) && $column['unique']) {
                    $modelCheck = $model::class;
                    $modelCheck = $model->id ?
                        $modelCheck::where($key, Str::slug($value))->where('id', '!=', $model->id)->first() :
                        $modelCheck::where($key, Str::slug($value))->first();
                    if ($modelCheck) {
                        throw ValidationException::withMessages([$column['key'] => 'The slug must be unique.']);
                    }
                }
                $model->$key = Str::slug($value);
                break;
            case FieldTypes::EMAIL->value:
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    throw ValidationException::withMessages([$column['key'] => 'Must be a valid email.']);
                }
                $model->$key = $value;
                break;
            case FieldTypes::DATE->value:
                $model->$key = Carbon::parse($value);
                break;
            case FieldTypes::NUMBER->value:
                if (!is_numeric($value)) {
                    throw ValidationException::withMessages([$column['key'] => 'Must be a valid number.']);
                }
                $model->$key = (int) $value;
                break;
            case FieldTypes::MODEL_SEARCH->value:
                $valueData = !empty($value['id']) ? $value['id'] : collect($value)->pluck('id')->first();
                $model->$key = $column['singleSearch'] ? $valueData : json_encode($valueData);
                break;
            case FieldTypes::ICON->value:
            case FieldTypes::SELECT->value:
                $model->$key = $value;
                break;
            case FieldTypes::PASSWORD->value:
                $model->$key = bcrypt($value);
                break;
            case FieldTypes::BOOLEAN->value:
                $model->$key = $value;
                break;
            default:
                break;
        }

        return $model;
    }

    /**
     * Replace the column values.
     *
     * @param $modelPaginated
     * @param $rawColumns
     *
     * @return Collection
     */
    public function columnReplacements($modelPaginated, $rawColumns)
    {
        return $modelPaginated->map(function ($item) use ($rawColumns) {
            foreach ($rawColumns as $column) {
                if (!empty($column['type'])) {
                    switch ($column['type']) {
                        case FieldTypes::MEDIA->value:
                            $field = $column['key'];
                            $item->$field = $item->media->isNotEmpty() ? collect($item->media->map(function ($mediaItem) {
                                return new MediaResource($mediaItem->media);
                            })) : null;
                            break;
                        case FieldTypes::MODEL_SEARCH->value:
                            $field = $column['key'];
                            $modelRelation = decrypt($column['model']);
                            $columnFilters = collect($column['columns'])->where('sortable', true)->pluck('key')->push(app($modelRelation)->getTable() . '.id');
                            $item->$field = $column['singleSearch'] ?
                                $modelRelation::where('id', $item->$field)->select($columnFilters->toArray())->first() :
                                $modelRelation::whereIn('id', json_decode($item->$field))->select($columnFilters->toArray())->get();
                            break;
                        case FieldTypes::PIVOT_MODEL->value:
                            $field = $column['key'];
                            $relation = $item->{$column['relation']}();
                            $columnFilters = collect($column['columns'])->where('sortable', true)->pluck('key')->push($relation->getQuery()->from . '.id');
                            $item->$field = $relation->select($columnFilters->toArray())->get();
                            break;
                        default:
                            break;
                    }
                }
            }
            return $item;
        });
    }
}
