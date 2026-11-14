<?php

declare(strict_types=1);

namespace App\Customer\Properties;

use App\Customer\CustomerStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Mom\Data\AbstractEnum;

final class Status extends AbstractEnum
{
    public static function getName(): string
    {
        return 'status';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['required', Rule::enum(CustomerStatusEnum::class)];
    }

    public static function forEloquentFactoryValue(): string
    {
        return CustomerStatusEnum::Active->value;
    }

    public function toNullableEnum(): ?CustomerStatusEnum
    {
        $value = $this->toValue();

        if ($value instanceof CustomerStatusEnum) {
            return $value;
        }

        return is_string($value) ? CustomerStatusEnum::tryFrom($value) : null;
    }

    public function toEnum(): CustomerStatusEnum
    {
        return $this->toNullableEnum() ?? CustomerStatusEnum::Active;
    }
}
