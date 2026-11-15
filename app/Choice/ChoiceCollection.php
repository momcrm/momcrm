<?php

declare(strict_types=1);

namespace App\Choice;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, Choice>
 */
final class ChoiceCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return Choice::class;
    }

    /**
     * Each choice as the frontend shows it, in order.
     *
     * @return list<array{id: string, name: string, color: string, icon: ?string, category: ?string, position: int, is_default: bool, is_archived: bool}>
     */
    public function toSummaries(): array
    {
        return array_values($this->map(fn (Choice $choice): array => $choice->toSummary())->all());
    }

    /**
     * Value/label pairs, for pickers that take an id, such as access rule
     * conditions. Archived choices say so.
     *
     * @return list<array{value: string, label: string}>
     */
    public function toOptions(): array
    {
        return array_values($this->map(fn (Choice $choice): array => [
            'value' => $choice->getId()->toString(),
            'label' => $choice->isArchived()
                ? __(':name (archived)', ['name' => $choice->getName()->toString()])
                : $choice->getName()->toString(),
        ])->all());
    }
}
