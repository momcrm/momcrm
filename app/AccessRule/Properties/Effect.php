<?php

declare(strict_types=1);

namespace App\AccessRule\Properties;

use App\AccessRule\AccessEffectEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Mom\Data\AbstractEnum;

final class Effect extends AbstractEnum
{
    public static function getName(): string
    {
        return 'effect';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['required', Rule::enum(AccessEffectEnum::class)];
    }

    public static function forEloquentFactoryValue(): string
    {
        return AccessEffectEnum::Allow->value;
    }

    public function toNullableEnum(): ?AccessEffectEnum
    {
        $value = $this->toValue();

        if ($value instanceof AccessEffectEnum) {
            return $value;
        }

        return is_string($value) ? AccessEffectEnum::tryFrom($value) : null;
    }

    /**
     * An unreadable effect counts as a deny, so a broken rule never grants access.
     */
    public function toEnum(): AccessEffectEnum
    {
        return $this->toNullableEnum() ?? AccessEffectEnum::Deny;
    }
}
