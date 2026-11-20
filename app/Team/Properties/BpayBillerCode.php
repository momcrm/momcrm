<?php

declare(strict_types=1);

namespace App\Team\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractString;

/**
 * The team's BPAY biller code, given by its bank. With it, invoices show how
 * to pay by BPAY: this code and the customer's reference number.
 */
final class BpayBillerCode extends AbstractString
{
    public static function getName(): string
    {
        return 'bpay_biller_code';
    }

    /**
     * @return array<int, string>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['nullable', 'string', 'regex:/^\d{3,10}$/'];
    }
}
