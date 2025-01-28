<?php

namespace Mariojgt\Builder\Enums;

use Mariojgt\Builder\Trailt\EnumToArray;

enum FieldTypes: string
{
    use EnumToArray;
    case TEXT = 'text';
    case SLUG = 'slug';
    case EMAIL = 'email';
    case DATE = 'date';
    case EDITOR = 'editor';
    case NUMBER = 'number';
    case MODEL_SEARCH = 'model_search';
    case ICON = 'icon';
    case SELECT = 'select';
    case PASSWORD = 'password';
    case MEDIA = 'media';
    case PIVOT_MODEL = 'pivot_model';
    case BOOLEAN = 'boolean';
    case CHIPS = 'chips';
    case TIMESTAMP = 'timestamp';
}
