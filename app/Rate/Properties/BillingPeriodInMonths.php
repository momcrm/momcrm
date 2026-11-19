<?php

declare(strict_types=1);

namespace App\Rate\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractInteger;

/**
 * How many months a service on this rate is billed for at a time, unless the
 * service says otherwise.
 */
final class BillingPeriodInMonths extends AbstractInteger
{
    public const array ALLOWED = [1, 3, 6, 12, 24, 36];

    public static function getName(): string
    {
        return 'billing_period_in_months';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['required', 'integer', 'in:'.implode(',', self::ALLOWED)];
    }
}
