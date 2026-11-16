<?php

declare(strict_types=1);

namespace App\CustomField\Properties;

use App\CustomField\CustomFieldTargetEnum;
use Mom\Data\AbstractEnum;

/**
 * Which kind of record the field is on, such as tickets.
 */
final class Target extends AbstractEnum
{
    public static function getName(): string
    {
        return 'target';
    }

    public static function forEloquentFactoryValue(): string
    {
        return CustomFieldTargetEnum::Ticket->value;
    }

    public function toNullableEnum(): ?CustomFieldTargetEnum
    {
        $value = $this->toValue();

        if ($value instanceof CustomFieldTargetEnum) {
            return $value;
        }

        return is_string($value) ? CustomFieldTargetEnum::tryFrom($value) : null;
    }

    public function toEnum(): CustomFieldTargetEnum
    {
        return $this->toNullableEnum() ?? CustomFieldTargetEnum::Ticket;
    }
}
