<?php

declare(strict_types=1);

namespace App\CustomField\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractString;

/**
 * A hint shown under the field's input, saying what belongs in it.
 */
final class Description extends AbstractString
{
    public static function getName(): string
    {
        return 'description';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['nullable', 'string', 'max:255'];
    }
}
