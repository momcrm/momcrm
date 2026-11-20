<?php

declare(strict_types=1);

namespace App\Customer\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractInteger;

/**
 * How many days after an invoice is issued this customer has to pay it,
 * when it isn't the team's usual. Empty means the team's.
 */
final class DaysToPay extends AbstractInteger
{
    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'days_to_pay';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['sometimes', 'nullable', 'integer', 'min:0', 'max:365'];
    }
}
