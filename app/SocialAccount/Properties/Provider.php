<?php

declare(strict_types=1);

namespace App\SocialAccount\Properties;

use App\SocialAccount\SocialProviderEnum;
use Mom\Data\AbstractEnum;

final class Provider extends AbstractEnum
{
    public static bool $includeInDatabaseUpdate = false;

    public static function getName(): string
    {
        return 'provider';
    }

    public static function forEloquentFactoryValue(): string
    {
        return SocialProviderEnum::Google->value;
    }

    public function toNullableEnum(): ?SocialProviderEnum
    {
        $value = $this->toValue();

        if ($value instanceof SocialProviderEnum) {
            return $value;
        }

        return is_string($value) ? SocialProviderEnum::tryFrom($value) : null;
    }

    public function toEnum(): SocialProviderEnum
    {
        return $this->toNullableEnum() ?? SocialProviderEnum::Google;
    }
}
