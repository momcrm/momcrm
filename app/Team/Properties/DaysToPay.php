<?php

declare(strict_types=1);

namespace App\Team\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractInteger;

/**
 * How many days after an invoice is issued customers have to pay it, unless
 * a customer has their own.
 */
final class DaysToPay extends AbstractInteger
{
    public const int DEFAULT = 14;

    /**
     * A new team starts with the column's default.
     */
    public static bool $includeInDatabaseCreate = false;

    public static function getName(): string
    {
        return 'days_to_pay';
    }

    /**
     * @return array<int, string>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['sometimes', 'required', 'integer', 'min:0', 'max:365'];
    }

    public static function forEloquentFactoryValue(): int
    {
        return self::DEFAULT;
    }
}
