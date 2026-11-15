<?php

declare(strict_types=1);

namespace App\AccessRule\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractBoolean;

final class IsEnabled extends AbstractBoolean
{
    public static function getName(): string
    {
        return 'is_enabled';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['required', 'boolean'];
    }

    public function default(): bool
    {
        return true;
    }
}
