<?php

declare(strict_types=1);

namespace App\Connector\Properties;

use App\Connector\ConnectorStatusEnum;
use Mom\Data\AbstractEnum;

final class Status extends AbstractEnum
{
    public static function getName(): string
    {
        return 'status';
    }

    public static function forEloquentFactoryValue(): string
    {
        return ConnectorStatusEnum::Connected->value;
    }

    public function toNullableEnum(): ?ConnectorStatusEnum
    {
        $value = $this->toValue();

        if ($value instanceof ConnectorStatusEnum) {
            return $value;
        }

        return is_string($value) ? ConnectorStatusEnum::tryFrom($value) : null;
    }

    /**
     * An unreadable status counts as needing a reconnect, so a broken
     * connector is never used.
     */
    public function toEnum(): ConnectorStatusEnum
    {
        return $this->toNullableEnum() ?? ConnectorStatusEnum::NeedsReconnect;
    }
}
