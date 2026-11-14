<?php

declare(strict_types=1);

namespace App\Project\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractString;

final class Icon extends AbstractString
{
    public static function getName(): string
    {
        return 'icon';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['nullable', 'string', 'max:32'];
    }
}
