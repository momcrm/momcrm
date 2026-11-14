<?php

declare(strict_types=1);

namespace App\AuditLog\Properties;

use App\AuditLog\AuditEventEnum;
use Mom\Data\AbstractEnum;

final class Event extends AbstractEnum
{
    public static function getName(): string
    {
        return 'event';
    }

    public static function forEloquentFactoryValue(): string
    {
        return AuditEventEnum::cases()[0]->value;
    }

    public function toNullableEnum(): ?AuditEventEnum
    {
        $value = $this->toValue();

        if ($value instanceof AuditEventEnum) {
            return $value;
        }

        return is_string($value) ? AuditEventEnum::tryFrom($value) : null;
    }

    public function toEnum(): AuditEventEnum
    {
        return $this->toNullableEnum() ?? AuditEventEnum::cases()[0];
    }
}
