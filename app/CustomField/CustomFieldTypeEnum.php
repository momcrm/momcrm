<?php

declare(strict_types=1);

namespace App\CustomField;

use App\Shared\Traits\Enum;
use App\Shared\Traits\EnumOptions;
use Closure;
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
    case Relation = 'relation';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Text',
            self::Number => 'Number',
            self::Date => 'Date',
            self::Checkbox => 'Checkbox',
            self::Select => 'Select',
            self::MultiSelect => 'Multi-select',
            self::Relation => 'Record link',
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
     * Whether a list can be narrowed to one value of the field: types with a
     * short, fixed set of values to pick from.
     */
    public function isFilterable(): bool
    {
        return in_array($this, [self::Checkbox, self::Select, self::MultiSelect, self::Relation], true);
    }

    /**
     * Whether a list can be ordered by the field: types whose values have an
     * order of their own.
     */
    public function isSortable(): bool
    {
        return in_array($this, [self::Text, self::Number, self::Date, self::Checkbox, self::Select], true);
    }

    /**
     * The rules for one value of a field, keyed by what follows the field's
     * own key ('' for the value itself, '.*' for each item of a multi-select).
     * A select takes one of the field's options and a record link one of the
     * team's records of the linked kind. Either may also keep what the record
     * already holds, so a record keeps an option or a link that has since
     * been removed.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function valueRules(CustomField $field, string $teamId, mixed $current = null): array
    {
        $allowed = [
            ...array_column($field->getOptions()->toOptions(), 'id'),
            ...array_filter(is_array($current) ? $current : [$current], is_string(...)),
        ];

        return match ($field->getType()->toEnum()) {
            self::Text => ['' => ['nullable', 'string', 'max:1000']],
            self::Number => ['' => ['nullable', 'numeric', 'between:-999999999999,999999999999']],
            self::Date => ['' => ['nullable', 'date_format:Y-m-d']],
            self::Checkbox => ['' => ['nullable', 'boolean']],
            self::Select => ['' => ['nullable', 'string', Rule::in($allowed)]],
            self::MultiSelect => [
                '' => ['nullable', 'array'],
                '.*' => ['string', 'distinct', Rule::in($allowed)],
            ],
            self::Relation => ['' => ['nullable', 'uuid', function (string $attribute, mixed $value, Closure $fail) use ($field, $teamId, $current): void {
                if ($value !== $current && (! is_string($value) || $field->getRelation()->toEnum()->names($teamId, [$value], $field->getRelationObjectId()->toNullableString()) === [])) {
                    $fail(__('validation.exists'));
                }
            }]],
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
            self::Date, self::Select, self::Relation => is_string($value) && $value !== '' ? $value : null,
            self::Checkbox => filter_var($value, FILTER_VALIDATE_BOOLEAN) ? true : null,
            self::MultiSelect => is_array($value) && $value !== [] ? array_values(array_filter($value, is_string(...))) : null,
        };
    }
}
