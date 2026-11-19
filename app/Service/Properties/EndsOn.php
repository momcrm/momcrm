<?php

declare(strict_types=1);

namespace App\Service\Properties;

use App\Shared\Abstracts\AbstractDateOnly;
use Illuminate\Foundation\Http\FormRequest;

/**
 * When the service expires or ends, if it does, such as a domain's expiry date.
 */
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
        return ['nullable', 'date', 'after_or_equal:billing_starts_on'];
    }
}
