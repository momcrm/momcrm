<?php

declare(strict_types=1);

namespace App\Message\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractString;

/**
 * What the message says.
 */
final class Body extends AbstractString
{
    public static function getName(): string
    {
        return 'body';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['required', 'string', 'max:5000'];
    }
}
