<?php

declare(strict_types=1);

namespace App\CustomField;

use App\CustomField\Actions\FindCustomField;

/**
 * The names of the records that record-link fields point at, so a page can
 * show "Acme IT" where a record only stores the customer's id.
 */
final class CustomFieldRelationLabels
{
    /**
     * The names behind every link in these records' custom field values, by
     * record id, read with one query per kind of linked record. Links to
     * records that no longer exist have no name.
     *
     * @param  array<string, mixed>  ...$valueMaps  Each record's values by field id.
     * @return array<string, string>
     */
    public static function for(string $teamId, CustomFieldTargetEnum $target, array ...$valueMaps): array
    {
        return self::forFields($teamId, FindCustomField::listForTeam($teamId, $target), $valueMaps);
    }

    /**
     * The same, for records of one custom object.
     *
     * @param  array<string, mixed>  ...$valueMaps
     * @return array<string, string>
     */
    public static function forObject(string $teamId, string $objectId, array ...$valueMaps): array
    {
        return self::forFields($teamId, FindCustomField::listForTeam($teamId, CustomFieldTargetEnum::Record, objectId: $objectId), $valueMaps);
    }

    /**
     * @param  array<array-key, array<string, mixed>>  $valueMaps
     * @return array<string, string>
     */
    private static function forFields(string $teamId, CustomFieldCollection $fields, array $valueMaps): array
    {
        $idsByKind = [];

        foreach ($fields as $field) {
            $relation = $field->getRelation()->toNullableEnum();

            if ($relation === null) {
                continue;
            }

            // Links to different custom objects are looked up separately.
            $kind = $relation->value.':'.$field->getRelationObjectId()->toNullableString();

            foreach ($valueMaps as $values) {
                $id = $values[$field->getId()->toString()] ?? null;

                if (is_string($id)) {
                    $idsByKind[$kind][$id] = $id;
                }
            }
        }

        $names = [];

        foreach ($idsByKind as $kind => $ids) {
            [$relation, $objectId] = explode(':', $kind, 2);

            $names += CustomFieldRelationEnum::from($relation)->names($teamId, array_values($ids), $objectId === '' ? null : $objectId);
        }

        return $names;
    }

    /**
     * The names one record's values need, out of those read for a whole list.
     *
     * @param  array<string, string>  $labels
     * @param  array<string, mixed>  $values
     * @return array<string, string>
     */
    public static function only(array $labels, array $values): array
    {
        return array_intersect_key($labels, array_flip(array_filter($values, is_string(...))));
    }
}
