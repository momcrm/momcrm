<?php

declare(strict_types=1);

namespace App\Customer\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractString;

final class Website extends AbstractString
{
    public static function getName(): string
    {
        return 'website';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['nullable', 'url', 'max:255'];
    }
}
