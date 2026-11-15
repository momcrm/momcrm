<?php

declare(strict_types=1);

namespace App\Choice\Properties;

use App\Shared\ColorEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Mom\Data\AbstractEnum;

final class Color extends AbstractEnum
{
    public static function getName(): string
    {
        return 'color';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['required', Rule::enum(ColorEnum::class)];
    }

    public static function forEloquentFactoryValue(): string
    {
        return ColorEnum::Indigo->value;
    }

    public function toNullableEnum(): ?ColorEnum
    {
        $value = $this->toValue();

        if ($value instanceof ColorEnum) {
            return $value;
        }

        return is_string($value) ? ColorEnum::tryFrom($value) : null;
    }

    public function toEnum(): ColorEnum
    {
        return $this->toNullableEnum() ?? ColorEnum::Indigo;
    }
}
