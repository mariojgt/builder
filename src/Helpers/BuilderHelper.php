<?php

namespace Mariojgt\Builder\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mariojgt\Builder\Enums\FieldTypes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Mariojgt\Magnifier\Resources\MediaResource;
use Illuminate\Support\Facades\Validator;

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
     * @param Model $model
     * @param array $column
     * @return Model
     * @throws ValidationException
     */
    public function genericValidation($model, $column)
    {
        $value = $column['value'];
        $key = $column['key'];
        $type = $column['type'];
        $messages = [];

        if (!empty($column['rules'])) {
            $validationRules = [];

            foreach ($column['rules'] as $rule) {
                if ($rule['type'] === 'validator') {
                    try {
                        $validatorClass = decrypt($rule['class']);
                        // Create new instance with parameters if they exist
                        if (!empty($rule['params'])) {
                            $validationRules[] = new $validatorClass(...$rule['params']);
                        } else {
                            $validationRules[] = new $validatorClass();
                        }
                    } catch (\Exception $e) {
                        throw ValidationException::withMessages([
                            $key => 'Invalid validation rule'
                        ]);
                    }
                } else {
                    $validationRules[] = $rule['value'];
                    // Format messages for this specific rule
                    if (!empty($column['messages'])) {
                        $ruleKey = explode(':', $rule['value'])[0];
                        $messageKey = "{$key}.{$ruleKey}";
                        if (isset($column['messages'][$messageKey])) {
                            $messages[$messageKey] = $column['messages'][$messageKey];
                        }
                    }
                }
            }

            $validator = Validator::make(
                [$key => $value],
                [$key => $validationRules],
                $messages
            );

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }
        }

        // Handle field type specific validation and assignment
        switch ($type) {
            case FieldTypes::TEXT->value:
            case FieldTypes::EDITOR->value:
                $model->$key = $value;
                break;

            case FieldTypes::SLUG->value:
                if (isset($column['unique']) && $column['unique']) {
                    $modelCheck = $model::class;
                    $modelCheck = $model->id ?
                        $modelCheck::where($key, Str::slug($value))->where('id', '!=', $model->id)->first() :
                        $modelCheck::where($key, Str::slug($value))->first();

                    if ($modelCheck) {
                        $errorMessage = $column['messages'][$key . '.unique'] ?? 'The slug must be unique.';
                        throw ValidationException::withMessages([$key => $errorMessage]);
                    }
                }
                $model->$key = Str::slug($value);
                break;

            case FieldTypes::EMAIL->value:
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errorMessage = $column['messages'][$key . '.email'] ?? 'Must be a valid email.';
                    throw ValidationException::withMessages([$key => $errorMessage]);
                }
                $model->$key = $value;
                break;

            case FieldTypes::DATE->value:
            case FieldTypes::TIMESTAMP->value:
                try {
                    $model->$key = Carbon::parse($value);
                } catch (\Exception $e) {
                    $errorMessage = $column['messages'][$key . '.date'] ?? 'Invalid date format.';
                    throw ValidationException::withMessages([$key => $errorMessage]);
                }
                break;

            case FieldTypes::NUMBER->value:
                if (!is_numeric($value)) {
                    $errorMessage = $column['messages'][$key . '.numeric'] ?? 'Must be a valid number.';
                    throw ValidationException::withMessages([$key => $errorMessage]);
                }
                $model->$key = $value;
                break;

            case FieldTypes::MODEL_SEARCH->value:
                $valueData = !empty($value['id']) ? $value['id'] : collect($value)->pluck('id')->first();
                $model->$key = $column['singleSearch'] ? $valueData : json_encode($valueData);
                break;

            case FieldTypes::PASSWORD->value:
                $model->$key = bcrypt($value);
                break;

            case FieldTypes::BOOLEAN->value:
            case FieldTypes::ICON->value:
            case FieldTypes::SELECT->value:
            case FieldTypes::CHIPS->value:
                $model->$key = $value;
                break;
        }

        return $model;
    }

    /**
     * Replace the column values.
     *
     * @param mixed $modelPaginated
     * @param mixed $rawColumns
     *
     * @return mixed
     */
    public function columnReplacements($modelPaginated, $rawColumns)
    {
        // Create a clone of the collection to avoid modifying the original
        $collection = $modelPaginated->getCollection()->map(function ($item) use ($rawColumns) {
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

                            // Fix for MODEL_SEARCH
                            if ($column['singleSearch']) {
                                $item->$field = $modelRelation::where('id', $item->$field)->select($columnFilters->toArray())->first();
                            } else {
                                // Make sure we have a valid array of IDs
                                $ids = json_decode($item->$field);
                                if ($ids && is_array($ids) && count($ids) > 0) {
                                    $item->$field = $modelRelation::whereIn('id', $ids)
                                        ->select($columnFilters->toArray())
                                        ->get();
                                } else {
                                    $item->$field = collect(); // Return empty collection if no valid IDs
                                }
                            }
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

        return $collection;
    }
}
