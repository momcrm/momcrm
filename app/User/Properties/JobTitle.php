<?php

declare(strict_types=1);

namespace App\User\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractString;

/**
 * What someone does, e.g. "Head of Support", shown on their member page.
 */
final class JobTitle extends AbstractString
{
    public static function getName(): string
    {
        return 'job_title';
    }

    /**
     * @return array<int, string>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['nullable', 'string', 'max:100'];
    }
}
