<?php

declare(strict_types=1);

namespace App\Invoice\Properties;

use App\Shared\CurrencyEnum;
use Mom\Data\AbstractEnum;

/**
 * The currency every amount on the invoice is in: its customer's currency
 * when it was drafted.
 */
final class Currency extends AbstractEnum
{
    public static function getName(): string
    {
        return 'currency';
    }

    public static function forEloquentFactoryValue(): string
    {
        return CurrencyEnum::default()->value;
    }

    public function toNullableEnum(): ?CurrencyEnum
    {
        $value = $this->toValue();

        if ($value instanceof CurrencyEnum) {
            return $value;
        }

        return is_string($value) ? CurrencyEnum::tryFrom($value) : null;
    }

    public function toEnum(): CurrencyEnum
    {
        return $this->toNullableEnum() ?? CurrencyEnum::default();
    }
}
