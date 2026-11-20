<?php

declare(strict_types=1);

namespace App\Customer\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractString;

/**
 * The customer's business or tax registration number, printed on what they
 * are billed.
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
