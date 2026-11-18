<?php

declare(strict_types=1);

namespace App\Connector\Properties;

use App\Connector\ConnectorTypeEnum;
use Mom\Data\AbstractEnum;

final class Type extends AbstractEnum
{
    public static bool $includeInDatabaseUpdate = false;

    public static function getName(): string
    {
        return 'type';
    }

    public static function forEloquentFactoryValue(): string
    {
        return ConnectorTypeEnum::Microsoft->value;
    }

    public function toNullableEnum(): ?ConnectorTypeEnum
    {
        $value = $this->toValue();

        if ($value instanceof ConnectorTypeEnum) {
            return $value;
        }

        return is_string($value) ? ConnectorTypeEnum::tryFrom($value) : null;
    }

    public function toEnum(): ConnectorTypeEnum
    {
        return $this->toNullableEnum() ?? ConnectorTypeEnum::Microsoft;
    }
}
