<?php

declare(strict_types=1);

namespace App\Service\Properties;

use App\Service\ServiceStatusEnum;
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
        return ['required', Rule::enum(ServiceStatusEnum::class)];
    }

    public static function forEloquentFactoryValue(): string
    {
        return ServiceStatusEnum::Active->value;
    }

    public function toNullableEnum(): ?ServiceStatusEnum
    {
        $value = $this->toValue();

        if ($value instanceof ServiceStatusEnum) {
            return $value;
        }

        return is_string($value) ? ServiceStatusEnum::tryFrom($value) : null;
    }

    public function toEnum(): ServiceStatusEnum
    {
        return $this->toNullableEnum() ?? ServiceStatusEnum::Active;
    }
}
