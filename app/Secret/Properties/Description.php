<?php

declare(strict_types=1);

namespace App\Secret\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractString;

final class Description extends AbstractString
{
    public static function getName(): string
    {
        return 'description';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['nullable', 'string', 'max:255'];
    }
}
