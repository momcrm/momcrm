<?php

declare(strict_types=1);

namespace App\Payment\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractString;

final class Reference extends AbstractString
{
    public static function getName(): string
    {
        return 'reference';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['nullable', 'string', 'max:255'];
    }
}
