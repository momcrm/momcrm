<?php

declare(strict_types=1);

namespace App\CustomField;

use App\CustomField\Actions\FindCustomField;
use App\CustomField\Properties\CustomFieldValues;

/**
 * Checks and stores the custom field values sent with a record, for any kind
 * of record that takes them. Values arrive as `custom_fields[<field id>]`.
 */
final class CustomFieldValueRules
{
    /**
     * Only the team's live fields on this kind of record may be set, each
     * with a value of its type.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function for(string $teamId, CustomFieldTargetEnum $target, ?CustomFieldValues $current = null): array
    {
        $key = CustomFieldValues::getNameForValidation();
        $fields = FindCustomField::listForTeam($teamId, $target);
        $held = $current?->toValues() ?? [];
        $ids = $fields->ids();
        $rules = [$key => $ids === [] ? ['sometimes', 'array', 'max:0'] : ['sometimes', 'array:'.implode(',', $ids)]];

        foreach ($fields as $field) {
            $id = $field->getId()->toString();
            $valueRules = CustomFieldTypeEnum::valueRules($field, $teamId, $held[$id] ?? null);

            foreach ($valueRules as $suffix => $rule) {
                $rules["{$key}.{$id}{$suffix}"] = $rule;
            }
        }

        return $rules;
    }

    /**
     * The fields' names, so a message says "The Serial number field…" and
     * not the field's id.
     *
     * @return array<string, string>
     */
    public static function attributes(string $teamId, CustomFieldTargetEnum $target): array
    {
        $key = CustomFieldValues::getNameForValidation();
        $attributes = [];

        foreach (FindCustomField::listForTeam($teamId, $target) as $field) {
            $id = $field->getId()->toString();
            $attributes["{$key}.{$id}"] = $field->getName()->toString();
            $attributes["{$key}.{$id}.*"] = $field->getName()->toString();
        }

        return $attributes;
    }

    /**
     * What the record holds once the validated changes are applied. A field
     * left out of the changes keeps its value, an emptied one loses it, and
     * values of fields that have since been deleted are dropped. Archived
     * fields keep theirs, so restoring the field brings them back.
     *
     * @return array<string, mixed>
     */
    public static function merge(string $teamId, CustomFieldTargetEnum $target, CustomFieldValues $current, mixed $changes): array
    {
        $changes = is_array($changes) ? $changes : [];
        $held = $current->toValues();
        $values = [];

        foreach (FindCustomField::listForTeam($teamId, $target, withArchived: true) as $field) {
            $id = $field->getId()->toString();
            $value = array_key_exists($id, $changes) && ! $field->isArchived()
                ? $field->getType()->toEnum()->normalize($changes[$id])
                : $held[$id] ?? null;

            if ($value !== null) {
                $values[$id] = $value;
            }
        }

        return $values;
    }
}
