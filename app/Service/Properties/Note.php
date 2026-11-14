<?php

declare(strict_types=1);

namespace App\Service\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractString;

final class Note extends AbstractString
{
    public static function getName(): string
    {
        return 'note';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['nullable', 'string', 'max:10000'];
    }
}
