<?php

declare(strict_types=1);

namespace App\User\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractString;

/**
 * Someone's given name. With their last name it makes up the name teammates see.
 */
final class FirstName extends AbstractString
{
    public static function getName(): string
    {
        return 'first_name';
    }

    /**
     * @return array<int, string>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['required', 'string', 'max:100'];
    }
}
