<?php

declare(strict_types=1);

namespace App\BankTransaction\Properties;

use App\BankTransaction\BankTransactionMatchEnum;
use Mom\Data\AbstractEnum;

/**
 * How the line's customer was found, when it has one.
 */
final class MatchedBy extends AbstractEnum
{
    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'matched_by';
    }

    public static function forEloquentFactoryValue(): ?string
    {
        return null;
    }

    public function toNullableEnum(): ?BankTransactionMatchEnum
    {
        $value = $this->toValue();

        if ($value instanceof BankTransactionMatchEnum) {
            return $value;
        }

        return is_string($value) ? BankTransactionMatchEnum::tryFrom($value) : null;
    }

    public function toEnum(): BankTransactionMatchEnum
    {
        return $this->toNullableEnum() ?? BankTransactionMatchEnum::Manual;
    }
}
