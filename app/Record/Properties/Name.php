<?php

declare(strict_types=1);

namespace App\Record\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractString;

/**
 * What the record is called: its title in lists, links and search.
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
        return ['required', 'string', 'max:255'];
    }
}
