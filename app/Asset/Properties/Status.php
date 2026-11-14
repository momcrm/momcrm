<?php

declare(strict_types=1);

namespace App\Asset\Properties;

use App\Asset\AssetStatusEnum;
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
        return ['required', Rule::enum(AssetStatusEnum::class)];
    }

    public static function forEloquentFactoryValue(): string
    {
        return AssetStatusEnum::Active->value;
    }

    public function toNullableEnum(): ?AssetStatusEnum
    {
        $value = $this->toValue();

        if ($value instanceof AssetStatusEnum) {
            return $value;
        }

        return is_string($value) ? AssetStatusEnum::tryFrom($value) : null;
    }

    public function toEnum(): AssetStatusEnum
    {
        return $this->toNullableEnum() ?? AssetStatusEnum::Active;
    }
}
