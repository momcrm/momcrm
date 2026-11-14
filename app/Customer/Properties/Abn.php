<?php

declare(strict_types=1);

namespace App\Customer\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractString;

final class Abn extends AbstractString
{
    public static function getName(): string
    {
        return 'abn';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['nullable', 'string', 'max:20'];
    }
}
