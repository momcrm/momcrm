<?php

declare(strict_types=1);

namespace App\BankAccount\Properties;

use App\BankAccount\BankAccountKindEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Mom\Data\AbstractEnum;

/**
 * What files the account's statements come in.
 */
final class Kind extends AbstractEnum
{
    public static bool $includeInDatabaseUpdate = false;

    public static function getName(): string
    {
        return 'kind';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['sometimes', 'required', Rule::enum(BankAccountKindEnum::class)];
    }

    public static function forEloquentFactoryValue(): string
    {
        return BankAccountKindEnum::Statement->value;
    }

    public function toNullableEnum(): ?BankAccountKindEnum
    {
        $value = $this->toValue();

        if ($value instanceof BankAccountKindEnum) {
            return $value;
        }

        return is_string($value) ? BankAccountKindEnum::tryFrom($value) : null;
    }

    public function toEnum(): BankAccountKindEnum
    {
        return $this->toNullableEnum() ?? BankAccountKindEnum::Statement;
    }
}
