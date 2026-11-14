<?php

declare(strict_types=1);

namespace App\User\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Mom\Data\AbstractString;

final class Password extends AbstractString
{
    public static bool $includeInArray = false;

    public static bool $includeInResponse = false;

    public static bool $includeInDatabaseUpdate = false;

    public static function getName(): string
    {
        return 'password';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['required', 'string', PasswordRule::default(), 'confirmed'];
    }
}
