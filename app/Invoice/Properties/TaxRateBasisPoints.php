<?php

declare(strict_types=1);

namespace App\Invoice\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractInteger;

final class TaxRateBasisPoints extends AbstractInteger
{
    public static function getName(): string
    {
        return 'tax_rate_basis_points';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['required', 'integer', 'min:0', 'max:10000'];
    }
}
