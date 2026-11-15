<?php

declare(strict_types=1);

namespace App\AccessRule\Properties;

use Illuminate\Database\Eloquent\Model;
use Mom\Data\AbstractCollection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * What must all be true for the rule to apply: a list of {attribute, operator, value}.
 */
final class Conditions extends AbstractCollection
{
    public static function getName(): string
    {
        return 'conditions';
    }

    /**
     * Stored as an empty list rather than null when empty.
     *
     * @param  AbstractData<Model>  $data
     * @return array<int, mixed>
     */
    public static function forDatabaseCreateValue(AbstractValue $value, AbstractData $data): array
    {
        return $value instanceof AbstractCollection ? array_values($value->toArray()) : [];
    }

    /**
     * @param  AbstractData<Model>  $data
     * @return array<int, mixed>
     */
    public static function forDatabaseUpdateValue(AbstractValue $value, AbstractData $data): array
    {
        return self::forDatabaseCreateValue($value, $data);
    }
}
