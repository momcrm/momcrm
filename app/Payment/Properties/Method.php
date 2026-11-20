<?php

declare(strict_types=1);

namespace App\Payment\Properties;

use App\Payment\PaymentMethodEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Mom\Data\AbstractEnum;

final class Method extends AbstractEnum
{
    public static function getName(): string
    {
        return 'method';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['required', Rule::enum(PaymentMethodEnum::class)];
    }

    public static function forEloquentFactoryValue(): string
    {
        return PaymentMethodEnum::BankTransfer->value;
    }

    public function toNullableEnum(): ?PaymentMethodEnum
    {
        $value = $this->toValue();

        if ($value instanceof PaymentMethodEnum) {
            return $value;
        }

        return is_string($value) ? PaymentMethodEnum::tryFrom($value) : null;
    }

    public function toEnum(): PaymentMethodEnum
    {
        return $this->toNullableEnum() ?? PaymentMethodEnum::BankTransfer;
    }
}
