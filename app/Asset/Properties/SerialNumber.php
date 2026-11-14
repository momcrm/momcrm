<?php

declare(strict_types=1);

namespace App\Asset\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractString;

final class SerialNumber extends AbstractString
{
    public static function getName(): string
    {
        return 'serial_number';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['nullable', 'string', 'max:255'];
    }
}
