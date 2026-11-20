<?php

declare(strict_types=1);

namespace App\Charge\Properties;

use App\Shared\CurrencyEnum;
use Mom\Data\AbstractEnum;

/**
 * The currency the charge is in: its customer's, so it goes on their invoices.
 */
final class Currency extends AbstractEnum
{
    public static bool $includeInDatabaseUpdate = false;

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
