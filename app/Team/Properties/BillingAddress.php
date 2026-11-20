<?php

declare(strict_types=1);

namespace App\Team\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractString;

/**
 * The team's postal address, as printed on its invoices.
 */
final class BillingAddress extends AbstractString
{
    public static function getName(): string
    {
        return 'billing_address';
    }

    /**
     * @return array<int, string>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['nullable', 'string', 'max:1000'];
    }
}
