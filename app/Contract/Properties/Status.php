<?php

declare(strict_types=1);

namespace App\Contract\Properties;

use App\Contract\ContractStatusEnum;
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
        return ['required', Rule::enum(ContractStatusEnum::class)];
    }

    public static function forEloquentFactoryValue(): string
    {
        return ContractStatusEnum::Draft->value;
    }

    public function toNullableEnum(): ?ContractStatusEnum
    {
        $value = $this->toValue();

        if ($value instanceof ContractStatusEnum) {
            return $value;
        }

        return is_string($value) ? ContractStatusEnum::tryFrom($value) : null;
    }

    public function toEnum(): ContractStatusEnum
    {
        return $this->toNullableEnum() ?? ContractStatusEnum::Draft;
    }
}
