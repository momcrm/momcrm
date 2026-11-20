<?php

declare(strict_types=1);

namespace App\BankAccount\Properties;

use App\Shared\CurrencyEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Mom\Data\AbstractEnum;

/**
 * The currency the account holds. Its deposits can only pay customers billed
 * in it.
 */
final class Currency extends AbstractEnum
{
    public static function getName(): string
    {
        return 'currency';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['required', Rule::enum(CurrencyEnum::class)];
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
