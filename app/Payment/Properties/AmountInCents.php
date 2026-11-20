<?php

declare(strict_types=1);

namespace App\Payment\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractInteger;

final class AmountInCents extends AbstractInteger
{
    public static function getName(): string
    {
        return 'amount_in_cents';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['required', 'integer', 'min:1', 'max:100000000000'];
    }
}
