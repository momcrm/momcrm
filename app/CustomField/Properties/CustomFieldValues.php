<?php

declare(strict_types=1);

namespace App\CustomField\Properties;

use Mom\Data\AbstractCollection;

/**
 * What a record holds for its team's custom fields, keyed by field id. A
 * field the record has no value for has no key. Shared by every kind of
 * record that takes custom fields.
 */
final class CustomFieldValues extends AbstractCollection
{
    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'custom_fields';
    }

    /**
     * @return array<string, mixed>
     */
    public function toValues(): array
    {
        $values = [];

        foreach ($this->toCollection() as $fieldId => $value) {
            if (is_string($fieldId) && $value !== null) {
                $values[$fieldId] = $value;
            }
        }

        return $values;
    }
}
