<?php

declare(strict_types=1);

namespace App\Team\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractString;

/**
 * How customers pay, e.g. bank details, printed at the foot of each invoice.
 */
final class PaymentDetails extends AbstractString
{
    public static function getName(): string
    {
        return 'payment_details';
    }

    /**
     * @return array<int, string>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['nullable', 'string', 'max:2000'];
    }
}
