<?php

declare(strict_types=1);

namespace App\Project\Properties;

use App\Project\ProjectStatusEnum;
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
        return ['required', Rule::enum(ProjectStatusEnum::class)];
    }

    public static function forEloquentFactoryValue(): string
    {
        return ProjectStatusEnum::Planning->value;
    }

    public function toNullableEnum(): ?ProjectStatusEnum
    {
        $value = $this->toValue();

        if ($value instanceof ProjectStatusEnum) {
            return $value;
        }

        return is_string($value) ? ProjectStatusEnum::tryFrom($value) : null;
    }

    public function toEnum(): ProjectStatusEnum
    {
        return $this->toNullableEnum() ?? ProjectStatusEnum::Planning;
    }
}
