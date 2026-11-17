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
        $idsByRelation = [];

        foreach (FindCustomField::listForTeam($teamId, $target) as $field) {
            $relation = $field->getRelation()->toNullableEnum();

            if ($relation === null) {
                continue;
            }

            foreach ($valueMaps as $values) {
                $id = $values[$field->getId()->toString()] ?? null;

                if (is_string($id)) {
                    $idsByRelation[$relation->value][$id] = $id;
                }
            }
        }

        $names = [];

        foreach ($idsByRelation as $relation => $ids) {
            $names += CustomFieldRelationEnum::from($relation)->names($teamId, array_values($ids));
        }

        return $names;
    }
}
