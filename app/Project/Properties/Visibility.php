<?php

declare(strict_types=1);

namespace App\Project\Properties;

use App\Project\ProjectVisibilityEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Mom\Data\AbstractEnum;

final class Visibility extends AbstractEnum
{
    public static function getName(): string
    {
        return 'visibility';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['required', Rule::enum(ProjectVisibilityEnum::class)];
    }

    public static function forEloquentFactoryValue(): string
    {
        return ProjectVisibilityEnum::Open->value;
    }

    public function toNullableEnum(): ?ProjectVisibilityEnum
    {
        $value = $this->toValue();

        if ($value instanceof ProjectVisibilityEnum) {
            return $value;
        }

        return is_string($value) ? ProjectVisibilityEnum::tryFrom($value) : null;
    }

    public function toEnum(): ProjectVisibilityEnum
    {
        return $this->toNullableEnum() ?? ProjectVisibilityEnum::Open;
    }
}
