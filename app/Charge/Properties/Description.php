<?php

declare(strict_types=1);

namespace App\Charge\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractString;

/**
 * What the charge is for, as its invoice line will read.
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
        return ['required', 'string', 'max:255'];
    }
}
