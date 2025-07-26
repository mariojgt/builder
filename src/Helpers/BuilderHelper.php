<?php

namespace Mariojgt\Builder\Helpers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Mariojgt\Builder\Enums\FieldTypes;
use Mariojgt\Magnifier\Resources\MediaResource;

class BuilderHelper
{
    /**
     * Check if the user has the necessary permission.
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
                'permission' => 'You don\'t have permission to '.$checkType.' this item',
            ]);
        }

        if (! $authorized) {
            throw ValidationException::withMessages([
                'permission' => 'You don\'t have permission to '.$checkType.' this item',
            ]);
        }

        return $authorized;
    }

    /**
     * Generic assignment and validation of data.
     */
    public function genericValidation($model, $column)
    {
        $value = $column['value'];
        $key = $column['key'];
        $type = $column['type'];
        $messages = [];

        if (! empty($column['rules'])) {
            $validationRules = [];

            foreach ($column['rules'] as $rule) {
                if ($rule['type'] === 'validator') {
                    try {
                        $validatorClass = decrypt($rule['class']);
                        if (! empty($rule['params'])) {
                            $validationRules[] = new $validatorClass(...$rule['params']);
                        } else {
                            $validationRules[] = new $validatorClass;
                        }
                    } catch (\Exception $e) {
                        throw ValidationException::withMessages([
                            $key => 'Invalid validation rule',
                        ]);
                    }
                } else {
                    $validationRules[] = $rule['value'];
                    if (! empty($column['messages'])) {
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
                        $errorMessage = $column['messages'][$key.'.unique'] ?? 'The slug must be unique.';
                        throw ValidationException::withMessages([$key => $errorMessage]);
                    }
                }
                $model->$key = Str::slug($value);
                break;

            case FieldTypes::EMAIL->value:
                if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errorMessage = $column['messages'][$key.'.email'] ?? 'Must be a valid email.';
                    throw ValidationException::withMessages([$key => $errorMessage]);
                }
                $model->$key = $value;
                break;

            case FieldTypes::DATE->value:
            case FieldTypes::TIMESTAMP->value:
                try {
                    $model->$key = Carbon::parse($value);
                } catch (\Exception $e) {
                    $errorMessage = $column['messages'][$key.'.date'] ?? 'Invalid date format.';
                    throw ValidationException::withMessages([$key => $errorMessage]);
                }
                break;

            case FieldTypes::NUMBER->value:
                if (! is_numeric($value)) {
                    $errorMessage = $column['messages'][$key.'.numeric'] ?? 'Must be a valid number.';
                    throw ValidationException::withMessages([$key => $errorMessage]);
                }
                $model->$key = $value;
                break;

            case FieldTypes::MODEL_SEARCH->value:
                $valueData = ! empty($value['id']) ? $value['id'] : collect($value)->pluck('id')->first();
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
     * Replace the column values with enhanced relationship support + SIMPLE LINK PROCESSING
     */
    public function columnReplacements($modelPaginated, $rawColumns)
    {
        // Create a clone of the collection to avoid modifying the original
        $collection = $modelPaginated->getCollection()->map(function ($item) use ($rawColumns) {
            foreach ($rawColumns as $column) {
                $key = $column['key'];

                // Handle dot notation relationships automatically (with fallback support)
                if (strpos($key, '.') !== false || strpos($key, '|') !== false) {
                    $item->$key = $this->getRelationshipValue($item, $key);

                    // ✨ SIMPLE: Add link if configured
                    if (isset($column['link'])) {
                        $item->{$key.'_link'} = $this->processSimpleLink($column['link'], $item);
                    }

                    continue;
                }

                // Handle existing field types for non-relationship fields
                if (! empty($column['type'])) {
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
                            $columnFilters = collect($column['columns'])->where('sortable', true)->pluck('key')->push(app($modelRelation)->getTable().'.id');

                            if ($column['singleSearch']) {
                                $item->$field = $modelRelation::where('id', $item->$field)->select($columnFilters->toArray())->first();
                            } else {
                                $ids = json_decode($item->$field);
                                if ($ids && is_array($ids) && count($ids) > 0) {
                                    $item->$field = $modelRelation::whereIn('id', $ids)
                                        ->select($columnFilters->toArray())
                                        ->get();
                                } else {
                                    $item->$field = collect();
                                }
                            }
                            break;

                        case FieldTypes::PIVOT_MODEL->value:
                            $field = $column['key'];
                            $relation = $item->{$column['relation']}();
                            $columnFilters = collect($column['columns'])->where('sortable', true)->pluck('key')->push($relation->getQuery()->from.'.id');
                            $item->$field = $relation->select($columnFilters->toArray())->get();
                            break;

                        default:
                            break;
                    }
                }

                // ✨ SIMPLE: Add link if configured for regular fields too
                if (isset($column['link'])) {
                    $item->{$key.'_link'} = $this->processSimpleLink($column['link'], $item);
                }
            }

            return $item;
        });

        return $collection;
    }

    /**
     * ✨ SIMPLE: Process link configuration
     */
    private function processSimpleLink(array $linkConfig, $item): ?array
    {
        // Handle field-based URLs
        if (isset($linkConfig['url_field'])) {
            $url = $this->getRelationshipValue($item, $linkConfig['url_field']);

            return $url ? [
                'url' => $url,
                'target' => $linkConfig['target'] ?? '_self',
                'style' => $linkConfig['style'] ?? 'default',
            ] : null;
        }

        // Handle template URLs
        if (empty($linkConfig['url'])) {
            return null;
        }

        $url = $linkConfig['url'];

        // Replace {value} with current field value (handled by frontend)
        // Replace {id} with item id
        $url = str_replace('{id}', $item->id ?? '', $url);

        // Replace other simple placeholders
        $url = preg_replace_callback('/\{(\w+)\}/', function ($matches) use ($item) {
            $field = $matches[1];

            return $item->$field ?? '';
        }, $url);

        // Replace relationship placeholders like {reportedData.comp_name}
        $url = preg_replace_callback('/\{(\w+\.\w+)\}/', function ($matches) use ($item) {
            $path = $matches[1];

            return $this->getRelationshipValue($item, $path) ?? '';
        }, $url);

        return [
            'url' => $url,
            'target' => $linkConfig['target'] ?? '_self',
            'style' => $linkConfig['style'] ?? 'default',
        ];
    }

    /**
     * Get relationship value with AUTOMATIC FALLBACK support
     */
    private function getRelationshipValue($item, $key)
    {
        try {
            // Handle fallback syntax with pipe "|"
            if (strpos($key, '|') !== false) {
                $keys = explode('|', $key);

                foreach ($keys as $tryKey) {
                    $tryKey = trim($tryKey);

                    // If it's a static value (no dots), return as-is
                    if (strpos($tryKey, '.') === false && ! property_exists($item, $tryKey)) {
                        return $tryKey;
                    }

                    // Try to get the relationship value
                    $value = data_get($item, $tryKey);

                    if (! empty($value)) {
                        return $this->formatValue($value);
                    }
                }

                return null;
            }

            // Regular single key
            $value = data_get($item, $key);

            return $this->formatValue($value);
        } catch (\Exception $e) {
            return $this->manualRelationshipTraversal($item, $key);
        }
    }

    /**
     * Format value for different data types
     */
    private function formatValue($value)
    {
        if ($value instanceof \Carbon\Carbon) {
            return $value->toISOString();
        }

        if ($value instanceof \Illuminate\Database\Eloquent\Model) {
            return $value->toArray();
        }

        if ($value instanceof \Illuminate\Support\Collection) {
            return $value->toArray();
        }

        return $value;
    }

    /**
     * Fallback method for edge cases where data_get might fail
     */
    private function manualRelationshipTraversal($item, $key)
    {
        try {
            $segments = explode('.', $key);
            $current = $item;

            foreach ($segments as $segment) {
                if (is_null($current)) {
                    return null;
                }

                if (is_object($current)) {
                    if ($current instanceof \Illuminate\Database\Eloquent\Model && $current->relationLoaded($segment)) {
                        $current = $current->getRelation($segment);
                    } else {
                        $current = $current->$segment ?? null;
                    }
                } elseif (is_array($current)) {
                    $current = $current[$segment] ?? null;
                } else {
                    return null;
                }
            }

            if ($current instanceof \Carbon\Carbon) {
                return $current->toISOString();
            }

            if ($current instanceof \Illuminate\Database\Eloquent\Model) {
                return $current->toArray();
            }

            if ($current instanceof \Illuminate\Support\Collection) {
                return $current->toArray();
            }

            return $current;
        } catch (\Exception $e) {
            \Log::warning("Error accessing relationship path '{$key}': ".$e->getMessage());

            return null;
        }
    }
}
