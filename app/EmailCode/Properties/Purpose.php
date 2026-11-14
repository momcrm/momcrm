<?php

declare(strict_types=1);

namespace App\EmailCode\Properties;

use App\EmailCode\EmailCodePurposeEnum;
use Mom\Data\AbstractEnum;

final class Purpose extends AbstractEnum
{
    public static function getName(): string
    {
        return 'purpose';
    }

    public static function forEloquentFactoryValue(): string
    {
        return EmailCodePurposeEnum::SignIn->value;
    }

    public function toNullableEnum(): ?EmailCodePurposeEnum
    {
        $value = $this->toValue();

        if ($value instanceof EmailCodePurposeEnum) {
            return $value;
        }

        return is_string($value) ? EmailCodePurposeEnum::tryFrom($value) : null;
    }

    public function toEnum(): EmailCodePurposeEnum
    {
        return $this->toNullableEnum() ?? EmailCodePurposeEnum::SignIn;
    }
}
