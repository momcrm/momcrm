<?php

declare(strict_types=1);

namespace App\AccessRule\Properties;

use Illuminate\Database\Eloquent\Model;
use Mom\Data\AbstractCollection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * The actions the rule covers, as AccessActionEnum values. Empty covers every action.
 */
final class Actions extends AbstractCollection
{
    public static function getName(): string
    {
        return 'actions';
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
