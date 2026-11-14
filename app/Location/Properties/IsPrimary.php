<?php

declare(strict_types=1);

namespace App\Location\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractBoolean;

final class IsPrimary extends AbstractBoolean
{
    public static function getName(): string
    {
        return 'is_primary';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['boolean'];
    }

    public function default(): bool
    {
        return false;
    }
}
