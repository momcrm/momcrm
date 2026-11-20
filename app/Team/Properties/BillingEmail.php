<?php

declare(strict_types=1);

namespace App\Team\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractString;

/**
 * Where customers write about an invoice.
 */
final class BillingEmail extends AbstractString
{
    public static function getName(): string
    {
        return 'billing_email';
    }

    /**
     * @return array<int, string>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['nullable', 'string', 'email', 'max:255'];
    }
}
