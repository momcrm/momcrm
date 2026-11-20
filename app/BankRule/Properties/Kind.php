<?php

declare(strict_types=1);

namespace App\BankRule\Properties;

use App\BankRule\BankRuleKindEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Mom\Data\AbstractEnum;

final class Kind extends AbstractEnum
{
    public static function getName(): string
    {
        return 'kind';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['required', Rule::enum(BankRuleKindEnum::class)];
    }

    public static function forEloquentFactoryValue(): string
    {
        return BankRuleKindEnum::Match->value;
    }

    public function toNullableEnum(): ?BankRuleKindEnum
    {
        $value = $this->toValue();

        if ($value instanceof BankRuleKindEnum) {
            return $value;
        }

        return is_string($value) ? BankRuleKindEnum::tryFrom($value) : null;
    }

    public function toEnum(): BankRuleKindEnum
    {
        return $this->toNullableEnum() ?? BankRuleKindEnum::Match;
    }
}
