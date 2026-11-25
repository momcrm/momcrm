<?php

declare(strict_types=1);

namespace App\User\Properties;

use Illuminate\Database\Eloquent\Model;
use Mom\Data\AbstractCollection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * The kinds of inbox notification the person is also emailed, as
 * InboxKindEnum values. Null until they choose, when the defaults apply; an
 * empty list means none.
 */
final class EmailedNotifications extends AbstractCollection
{
    public static function getName(): string
    {
        return 'emailed_notifications';
    }

    /**
     * An empty choice is kept as an empty list, not null, so it isn't
     * mistaken for not having chosen.
     *
     * @param  AbstractData<Model>  $data
     * @return array<int, mixed>|null
     */
    public static function forDatabaseCreateValue(AbstractValue $value, AbstractData $data): ?array
    {
        return $value instanceof AbstractCollection && $value->isNotNull() ? array_values($value->toArray()) : null;
    }

    /**
     * @param  AbstractData<Model>  $data
     * @return array<int, mixed>|null
     */
    public static function forDatabaseUpdateValue(AbstractValue $value, AbstractData $data): ?array
    {
        return self::forDatabaseCreateValue($value, $data);
    }
}
