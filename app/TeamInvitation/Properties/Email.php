<?php

declare(strict_types=1);

namespace App\TeamInvitation\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractString;

final class Email extends AbstractString
{
    public static function getName(): string
    {
        return 'email';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['required', 'string', 'lowercase', 'email', 'max:255'];
    }
}
