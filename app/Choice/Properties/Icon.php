<?php

declare(strict_types=1);

namespace App\Choice\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractString;

/**
 * The name of the icon shown beside the choice, such as `chevrons-up`, or null for none.
 */
final class Icon extends AbstractString
{
    public static function getName(): string
    {
        return 'icon';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['nullable', 'string', 'max:32'];
    }
}
