<?php

declare(strict_types=1);

namespace App\Service\Properties;

use App\Shared\Abstracts\AbstractDateOnly;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The day the service is first billed from.
 */
final class BillingStartsOn extends AbstractDateOnly
{
    public static function getName(): string
    {
        return 'billing_starts_on';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['required', 'date'];
    }
}
