<?php

declare(strict_types=1);

namespace App\CustomField\Properties;

use App\CustomField\CustomFieldTypeEnum;
use Mom\Data\AbstractEnum;

/**
 * What kind of value the field holds, such as a date. Fixed once the field
 * exists, so stored values never change meaning.
 */
final class Type extends AbstractEnum
{
    public static bool $includeInDatabaseUpdate = false;

    public static function getName(): string
    {
        return 'type';
    }

    public static function forEloquentFactoryValue(): string
    {
        return CustomFieldTypeEnum::Text->value;
    }

    public function toNullableEnum(): ?CustomFieldTypeEnum
    {
        $value = $this->toValue();

        if ($value instanceof CustomFieldTypeEnum) {
            return $value;
        }

        return is_string($value) ? CustomFieldTypeEnum::tryFrom($value) : null;
    }

    public function toEnum(): CustomFieldTypeEnum
    {
        return $this->toNullableEnum() ?? CustomFieldTypeEnum::Text;
    }
}
