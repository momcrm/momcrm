<?php

declare(strict_types=1);

namespace App\CustomObject;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, CustomObject>
 */
final class CustomObjectCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return CustomObject::class;
    }

    /**
     * Each object as the frontend shows it, in order.
     *
     * @return list<array{id: string, name: string, name_plural: string, description: ?string, icon: ?string, hue: ?int, position: int, is_archived: bool}>
     */
    public function toSummaries(): array
    {
        return array_values($this->map(fn (CustomObject $object): array => $object->toSummary())->all());
    }
}
