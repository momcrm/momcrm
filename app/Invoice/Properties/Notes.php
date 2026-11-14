<?php

declare(strict_types=1);

namespace App\Invoice\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractString;

final class Notes extends AbstractString
{
    public static function getName(): string
    {
        return 'notes';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['nullable', 'string', 'max:10000'];
    }
}
