<?php

declare(strict_types=1);

namespace App\CustomField;

use App\Shared\Traits\Enum;
use App\Shared\Traits\EnumOptions;

/**
 * The kinds of value a custom field can hold.
 */
enum CustomFieldTypeEnum: string
{
    use Enum;
    use EnumOptions;

    case Text = 'text';
    case Number = 'number';
    case Date = 'date';
    case Checkbox = 'checkbox';
    case Select = 'select';
    case MultiSelect = 'multi_select';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Text',
            self::Number => 'Number',
            self::Date => 'Date',
            self::Checkbox => 'Checkbox',
            self::Select => 'Select',
            self::MultiSelect => 'Multi-select',
        };
    }

    /**
     * Whether the field offers a list of options to pick from.
     */
    public function hasOptions(): bool
    {
        return in_array($this, [self::Select, self::MultiSelect], true);
    }
}
