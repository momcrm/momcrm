<?php

declare(strict_types=1);

namespace App\User\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractString;

final class Name extends AbstractString
{
    public static function getName(): string
    {
        return 'name';
    }

    /**
     * @return array<int, string>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['required', 'string', 'max:255'];
    }
}
