<?php

declare(strict_types=1);

namespace App\Customer\Properties;

use App\Customer\CustomerBillingTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Mom\Data\AbstractEnum;

final class BillingType extends AbstractEnum
{
    public static function getName(): string
    {
        return 'billing_type';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['sometimes', 'required', Rule::enum(CustomerBillingTypeEnum::class)];
    }

    public static function forEloquentFactoryValue(): string
    {
        return CustomerBillingTypeEnum::Manual->value;
    }

    public function toNullableEnum(): ?CustomerBillingTypeEnum
    {
        $value = $this->toValue();

        if ($value instanceof CustomerBillingTypeEnum) {
            return $value;
        }

        return is_string($value) ? CustomerBillingTypeEnum::tryFrom($value) : null;
    }

    public function toEnum(): CustomerBillingTypeEnum
    {
        return $this->toNullableEnum() ?? CustomerBillingTypeEnum::Manual;
    }
}
