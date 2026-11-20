<?php

declare(strict_types=1);

namespace App\AccessRule\Properties;

use App\AccessRule\AccessAudienceEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Mom\Data\AbstractEnum;

/**
 * Whether the rule is about staff or customer contacts. Set when the rule is
 * added (staff unless said otherwise) and never changed.
 */
final class Audience extends AbstractEnum
{
    public static bool $includeInDatabaseUpdate = false;

    public static function getName(): string
    {
        return 'audience';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['sometimes', 'required', Rule::enum(AccessAudienceEnum::class)];
    }

    public static function forEloquentFactoryValue(): string
    {
        return AccessAudienceEnum::Staff->value;
    }

    public function toNullableEnum(): ?AccessAudienceEnum
    {
        $value = $this->toValue();

        if ($value instanceof AccessAudienceEnum) {
            return $value;
        }

        return is_string($value) ? AccessAudienceEnum::tryFrom($value) : null;
    }

    /**
     * Rules from before audiences existed are staff rules.
     */
    public function toEnum(): AccessAudienceEnum
    {
        return $this->toNullableEnum() ?? AccessAudienceEnum::Staff;
    }
}
