<?php

declare(strict_types=1);

namespace App\User\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractString;

final class CurrentPassword extends AbstractString
{
    public static bool $hasDBColumn = false;

    public static bool $includeInArray = false;

    public static bool $includeInResponse = false;

    public static function getName(): string
    {
        return 'current_password';
    }

    /**
     * @return array<int, string>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['required', 'string', 'current_password'];
    }
}
