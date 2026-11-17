<?php

declare(strict_types=1);

namespace App\CustomField;

use App\Shared\Traits\Enum;
use App\Shared\Traits\EnumOptions;
use Illuminate\Validation\Rule;

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

    /**
     * The rules for one value of a field of this type, keyed by what follows
     * the field's own key ('' for the value itself, '.*' for each item of a
     * multi-select). A select takes one of the field's options, or what the
     * record already holds, so a record keeps an option that was since removed.
     *
     * @param  list<string>  $optionIds
     * @return array<string, array<int, mixed>>
     */
    public function valueRules(array $optionIds = [], mixed $current = null): array
    {
        $allowed = [...$optionIds, ...array_filter(is_array($current) ? $current : [$current], is_string(...))];

        return match ($this) {
            self::Text => ['' => ['nullable', 'string', 'max:1000']],
            self::Number => ['' => ['nullable', 'numeric', 'between:-999999999999,999999999999']],
            self::Date => ['' => ['nullable', 'date_format:Y-m-d']],
            self::Checkbox => ['' => ['nullable', 'boolean']],
            self::Select => ['' => ['nullable', 'string', Rule::in($allowed)]],
            self::MultiSelect => [
                '' => ['nullable', 'array'],
                '.*' => ['string', 'distinct', Rule::in($allowed)],
            ],
        };
    }

    /**
     * A validated value as it is stored, or null when there is nothing to
     * keep: an empty text, an unticked checkbox, no options picked.
     */
    public function normalize(mixed $value): mixed
    {
        return match ($this) {
            self::Text => is_string($value) && trim($value) !== '' ? trim($value) : null,
            self::Number => is_numeric($value) ? $value + 0 : null,
            self::Date, self::Select => is_string($value) && $value !== '' ? $value : null,
            self::Checkbox => filter_var($value, FILTER_VALIDATE_BOOLEAN) ? true : null,
            self::MultiSelect => is_array($value) && $value !== [] ? array_values(array_filter($value, is_string(...))) : null,
        };
    }
}
