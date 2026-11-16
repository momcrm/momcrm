<?php

declare(strict_types=1);

namespace App\CustomField\Properties;

use Illuminate\Database\Eloquent\Model;
use Mom\Data\AbstractCollection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * What a select field offers, in order: a list of {id, name, hue}. Records
 * store the option's id, so renaming an option keeps their value. Empty for
 * fields that are not selects.
 */
final class Options extends AbstractCollection
{
    public static function getName(): string
    {
        return 'options';
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

    /**
     * @return list<array{id: string, name: string, hue: ?int}>
     */
    public function toOptions(): array
    {
        $options = [];

        foreach ($this->toCollection() as $option) {
            if (is_array($option) && is_string($option['id'] ?? null) && is_string($option['name'] ?? null)) {
                $hue = $option['hue'] ?? null;
                $options[] = ['id' => $option['id'], 'name' => $option['name'], 'hue' => is_int($hue) ? $hue : null];
            }
        }

        return $options;
    }
}
