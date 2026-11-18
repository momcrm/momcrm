<?php

declare(strict_types=1);

namespace App\Ticket\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractString;

final class Title extends AbstractString
{
    public const int MAX_LENGTH = 255;

    public static function getName(): string
    {
        return 'title';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['required', 'string', 'max:'.self::MAX_LENGTH];
    }
}
