<?php

declare(strict_types=1);

namespace App\Team\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractString;

/**
 * The team's business or tax registration number, printed on its invoices.
 */
final class TaxId extends AbstractString
{
    public static function getName(): string
    {
        return 'tax_id';
    }

    /**
     * @return array<int, string>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['nullable', 'string', 'max:50'];
    }
}
