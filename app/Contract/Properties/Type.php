<?php

declare(strict_types=1);

namespace App\Contract\Properties;

use App\Contract\ContractTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Mom\Data\AbstractEnum;

final class Type extends AbstractEnum
{
    public static function getName(): string
    {
        return 'type';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['required', Rule::enum(ContractTypeEnum::class)];
    }

    public static function forEloquentFactoryValue(): string
    {
        return ContractTypeEnum::Msa->value;
    }

    public function toNullableEnum(): ?ContractTypeEnum
    {
        $value = $this->toValue();

        if ($value instanceof ContractTypeEnum) {
            return $value;
        }

        return is_string($value) ? ContractTypeEnum::tryFrom($value) : null;
    }

    public function toEnum(): ContractTypeEnum
    {
        return $this->toNullableEnum() ?? ContractTypeEnum::Msa;
    }
}
