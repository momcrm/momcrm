<?php

declare(strict_types=1);

namespace App\User\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractString;

/**
 * Where someone works from, as an IANA name such as "Australia/Perth", so
 * teammates can see their local time.
 */
final class Timezone extends AbstractString
{
    public static function getName(): string
    {
        return 'timezone';
    }

    /**
     * @return array<int, string>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['nullable', 'string', 'timezone:all'];
    }
}
