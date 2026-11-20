<?php

declare(strict_types=1);

namespace App\Customer\Properties;

use App\Shared\CurrencyEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Mom\Data\AbstractEnum;

/**
 * The currency the customer is billed in. Their services are priced at their
 * rates' prices in it, and their invoices are in it.
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
        return ['sometimes', 'required', Rule::enum(CurrencyEnum::class)];
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
