<?php

declare(strict_types=1);

namespace App\Charge\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractInteger;

/**
 * What one costs, in cents of the charge's currency.
 */
final class UnitPriceInCents extends AbstractInteger
{
    public static function getName(): string
    {
        return 'unit_price_in_cents';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['required', 'integer', 'min:0', 'max:100000000'];
    }
}
