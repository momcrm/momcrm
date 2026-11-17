<?php

declare(strict_types=1);

namespace App\CustomField\Properties;

use App\CustomField\CustomFieldRelationEnum;
use Mom\Data\AbstractEnum;

/**
 * The kind of record a record-link field points at; null for every other
 * type of field. Fixed once the field exists, like its type.
 */
final class Relation extends AbstractEnum
{
    public static bool $includeInDatabaseUpdate = false;

    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'relation';
    }

    public static function forEloquentFactoryValue(): mixed
    {
        return null;
    }

    public function toNullableEnum(): ?CustomFieldRelationEnum
    {
        $value = $this->toValue();

        if ($value instanceof CustomFieldRelationEnum) {
            return $value;
        }

        return is_string($value) ? CustomFieldRelationEnum::tryFrom($value) : null;
    }

    public function toEnum(): CustomFieldRelationEnum
    {
        return $this->toNullableEnum() ?? CustomFieldRelationEnum::Member;
    }
}
