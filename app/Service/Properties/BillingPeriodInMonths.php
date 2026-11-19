<?php

declare(strict_types=1);

namespace App\Service\Properties;

use App\Rate\Properties\BillingPeriodInMonths as RateBillingPeriodInMonths;
use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractInteger;

/**
 * How many months the service is billed for at a time, or empty to follow
 * its rate.
 */
final class BillingPeriodInMonths extends AbstractInteger
{
    public static function getName(): string
    {
        return 'billing_period_in_months';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['nullable', 'integer', 'in:'.implode(',', RateBillingPeriodInMonths::ALLOWED)];
    }
}
