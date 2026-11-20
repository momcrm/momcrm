<?php

declare(strict_types=1);

namespace App\Team\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractString;

/**
 * Where customers call about an invoice.
 */
final class BillingPhone extends AbstractString
{
    public static function getName(): string
    {
        return 'billing_phone';
    }

    /**
     * @return array<int, string>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['nullable', 'string', 'max:50'];
    }
}
