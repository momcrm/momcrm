<?php

declare(strict_types=1);

namespace App\AccessRule\Properties;

use Illuminate\Database\Eloquent\Model;
use Mom\Data\AbstractCollection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * The resources the rule covers, as AccessResourceEnum values. Empty covers every resource.
 */
final class Resources extends AbstractCollection
{
    public static function getName(): string
    {
        return 'resources';
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
