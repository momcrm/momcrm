<?php

declare(strict_types=1);

namespace App\Group\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractInteger;

/**
 * The hue (0–359 on the OKLCH colour wheel) it is tagged with, or null for
 * neutral grey. See App\Shared\HuePalette.
 */
final class Hue extends AbstractInteger
{
    public static function getName(): string
    {
        return 'hue';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['nullable', 'integer', 'between:0,359'];
    }
}
