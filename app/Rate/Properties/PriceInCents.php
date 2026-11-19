<?php

declare(strict_types=1);

namespace App\Rate\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractInteger;

/**
 * What one unit costs the customer for one month, before tax.
 */
final class PriceInCents extends AbstractInteger
{
    public static function getName(): string
    {
        return 'price_in_cents';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['required', 'integer', 'min:0', 'max:100000000'];
    }
}
