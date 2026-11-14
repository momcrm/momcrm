<?php

declare(strict_types=1);

namespace App\Shared\Abstracts;

use Illuminate\Database\Eloquent\Model;
use Mom\Data\AbstractData;
use Mom\Data\AbstractDate;
use Mom\Data\AbstractValue;

/**
 * A calendar date with no time of day, stored in a DATE column and sent to the
 * frontend as YYYY-MM-DD.
 */
abstract class AbstractDateOnly extends AbstractDate
{
    /**
     * @param  AbstractData<Model>  $data
     */
    public static function forDatabaseCreateValue(AbstractValue $value, AbstractData $data): ?string
    {
        return $value instanceof AbstractDate ? $value->toNullableCarbon()?->toDateString() : null;
    }

    /**
     * @param  AbstractData<Model>  $data
     */
    public static function forArrayValue(AbstractValue $value, AbstractData $data): ?string
    {
        return $value instanceof AbstractDate ? $value->toNullableCarbon()?->toDateString() : null;
    }

    public static function forEloquentFactoryValue(): string
    {
        return fake()->date();
    }
}
