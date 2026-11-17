<?php

declare(strict_types=1);

namespace App\CustomObject\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractString;

/**
 * What several records of the object are called, such as "Vehicles": the name
 * of its list and its sidebar entry.
 */
final class NamePlural extends AbstractString
{
    public static function getName(): string
    {
        return 'name_plural';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['required', 'string', 'max:50'];
    }
}
