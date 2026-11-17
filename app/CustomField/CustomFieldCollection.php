<?php

declare(strict_types=1);

namespace App\CustomField;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, CustomField>
 */
final class CustomFieldCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return CustomField::class;
    }

    /**
     * Each field as the frontend shows it, in order.
     *
     * @return list<array{id: string, target: string, type: string, relation: ?string, name: string, description: ?string, options: list<array{id: string, name: string, hue: ?int}>, position: int, is_archived: bool}>
     */
    public function toSummaries(): array
    {
        return array_values($this->map(fn (CustomField $field): array => $field->toSummary())->all());
    }
}
