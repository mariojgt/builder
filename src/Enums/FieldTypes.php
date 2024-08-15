<?php

namespace Mariojgt\Builder\Enums;

use Mariojgt\SkeletonAdmin\Enums\EnumToArray;

enum FieldTypes: string
{
    use EnumToArray;
    case TEXT = 'text';
    case SLUG = 'slug';
    case EMAIL = 'email';
    case DATE = 'date';
    case NUMBER = 'number';
    case MODEL_SEARCH = 'model_search';
    case ICON = 'icon';
    case SELECT = 'select';
    case PASSWORD = 'password';
    case MEDIA = 'media';
    case PIVOT_MODEL = 'pivot_model';
    case BOOLEAN = 'boolean';
}
