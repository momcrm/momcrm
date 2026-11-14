<?php

declare(strict_types=1);

namespace App\Location\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractString;

final class Country extends AbstractString
{
    public static function getName(): string
    {
        return 'country';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['nullable', 'string', 'max:100'];
    }
}
