<?php

declare(strict_types=1);

namespace App\Contract\Properties;

use App\Shared\Abstracts\AbstractDateOnly;
use Illuminate\Foundation\Http\FormRequest;

final class EndsOn extends AbstractDateOnly
{
    public static function getName(): string
    {
        return 'ends_on';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['nullable', 'date', 'after_or_equal:starts_on'];
    }
}
