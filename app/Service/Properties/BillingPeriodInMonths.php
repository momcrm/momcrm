<?php

declare(strict_types=1);

namespace App\Service\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractInteger;

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
        return ['required', 'integer', 'in:1,3,6,12'];
    }
}
