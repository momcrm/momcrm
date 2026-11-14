<?php

declare(strict_types=1);

namespace App\Contact\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractString;

final class WorkPhone extends AbstractString
{
    public static function getName(): string
    {
        return 'work_phone';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['nullable', 'string', 'max:50'];
    }
}
