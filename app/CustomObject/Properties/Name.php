<?php

declare(strict_types=1);

namespace App\CustomObject\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractString;

/**
 * What one record of the object is called, such as "Vehicle".
 */
final class Name extends AbstractString
{
    public static function getName(): string
    {
        return 'name';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['required', 'string', 'max:50'];
    }
}
