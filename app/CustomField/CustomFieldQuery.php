<?php

declare(strict_types=1);

namespace App\CustomField;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Filters and sorts a list of records by their custom field values, which
 * live in each record's `custom_fields` JSON column keyed by field id.
 */
final class CustomFieldQuery
{
    /**
     * The query string key filters arrive under: `cf[<field id>]=<value>`.
     */
    public const string FILTERS = 'cf';

    /**
     * What a sort by a custom field is called: `sort=cf:<field id>`.
     */
    public const string SORT_PREFIX = 'cf:';

    /**
     * Picks a record without a value for the field.
     */
    public const string NONE = 'none';

    private const string COLUMN = 'custom_fields';

    /**
     * The filters in the query string that name one of these fields and make
     * sense for its type, by field id. Anything else is ignored.
     *
     * @return array<string, string>
     */
    public static function filtersFromRequest(Request $request, CustomFieldCollection $fields): array
    {
        $sent = $request->query(self::FILTERS);
        $filters = [];

        foreach ($fields as $field) {
            $id = $field->getId()->toString();
            $value = is_array($sent) ? ($sent[$id] ?? null) : null;

            if (is_string($value) && self::accepts($field, $value)) {
                $filters[$id] = $value;
            }
        }

        return $filters;
    }

    /**
     * The field a `sort=cf:<id>` names, if it is one of these and can be
     * sorted by; empty otherwise.
     */
    public static function sortField(string $sort, CustomFieldCollection $fields): CustomField
    {
        if (! str_starts_with($sort, self::SORT_PREFIX)) {
            return CustomField::new();
        }

        $id = substr($sort, strlen(self::SORT_PREFIX));

        return $fields->first(fn (CustomField $field): bool => $field->getId()->toString() === $id && $field->getType()->toEnum()->isSortable());
    }

    /**
     * Narrows the query to records whose value for the field matches: the
     * picked option or linked record, a multi-select containing the option, a
     * ticked ("1") or unticked ("0") checkbox, or no value at all ("none").
     *
     * @param  Builder<covariant Model>  $query
     */
    public static function filter(Builder $query, CustomField $field, string $value): void
    {
        $column = self::column($field);

        if ($value === self::NONE || ($field->getType()->toEnum() === CustomFieldTypeEnum::Checkbox && $value === '0')) {
            $query->whereNull($column);

            return;
        }

        match ($field->getType()->toEnum()) {
            CustomFieldTypeEnum::Checkbox => $query->whereNotNull($column),
            CustomFieldTypeEnum::MultiSelect => $query->whereJsonContains($column, $value),
            default => $query->where($column, $value),
        };
    }

    /**
     * Orders the query by the field's value: numbers by size, selects by the
     * order of their options, ticked checkboxes last, the rest as text.
     * Records without a value come first when ascending.
     *
     * @param  Builder<covariant Model>  $query
     * @param  'asc'|'desc'  $direction
     */
    public static function sort(Builder $query, CustomField $field, string $direction): void
    {
        $isDescending = $direction === 'desc';
        $path = '$."'.$field->getId()->toString().'"';

        match ($field->getType()->toEnum()) {
            CustomFieldTypeEnum::Number => $query->orderByRaw(
                $isDescending
                    ? 'cast(json_extract(custom_fields, ?) as decimal(24, 6)) desc'
                    : 'cast(json_extract(custom_fields, ?) as decimal(24, 6)) asc',
                [$path],
            ),
            CustomFieldTypeEnum::Checkbox => $query->orderByRaw(
                $isDescending
                    ? '(json_extract(custom_fields, ?) is not null) desc'
                    : '(json_extract(custom_fields, ?) is not null) asc',
                [$path],
            ),
            CustomFieldTypeEnum::Select => self::sortByOption($query, $field, $path, $isDescending),
            default => $query->orderBy(self::column($field), $isDescending ? 'desc' : 'asc'),
        };
    }

    /**
     * Orders by where the record's option sits in the field's list of
     * options, with records that have none (or one since removed) first.
     *
     * @param  Builder<covariant Model>  $query
     */
    private static function sortByOption(Builder $query, CustomField $field, string $path, bool $isDescending): void
    {
        $ids = array_column($field->getOptions()->toOptions(), 'id');

        if ($ids === []) {
            return;
        }

        $bindings = [$path];

        foreach ($ids as $position => $id) {
            array_push($bindings, $id, $position);
        }

        $cases = str_repeat('when ? then ? ', count($ids));

        // SQLite's json_extract already returns bare text; MySQL's returns it JSON-quoted.
        $value = $query->getModel()->getConnection()->getDriverName() === 'sqlite'
            ? 'json_extract(custom_fields, ?)'
            : 'json_unquote(json_extract(custom_fields, ?))';

        $query->orderByRaw(
            $isDescending
                ? 'case '.$value.' '.$cases.'else -1 end desc'
                : 'case '.$value.' '.$cases.'else -1 end asc',
            $bindings,
        );
    }

    private static function accepts(CustomField $field, string $value): bool
    {
        return match ($field->getType()->toEnum()) {
            CustomFieldTypeEnum::Checkbox => in_array($value, ['0', '1'], true),
            CustomFieldTypeEnum::Select, CustomFieldTypeEnum::MultiSelect => $value === self::NONE || in_array($value, array_column($field->getOptions()->toOptions(), 'id'), true),
            CustomFieldTypeEnum::Relation => $value === self::NONE || Str::isUuid($value),
            default => false,
        };
    }

    /**
     * The field's place in the JSON column, as Laravel's `column->key` path.
     */
    private static function column(CustomField $field): string
    {
        return self::COLUMN.'->'.$field->getId()->toString();
    }
}
