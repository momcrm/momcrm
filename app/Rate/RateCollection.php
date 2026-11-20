<?php

declare(strict_types=1);

namespace App\Rate;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, Rate>
 */
final class RateCollection extends AbstractDataCollection
{
    public const string PATH_SEPARATOR = ' › ';

    public static function dataClass(): string
    {
        return Rate::class;
    }

    /**
     * Each rate as the frontend shows it, in tree order: every rate followed
     * by the rates under it, each level in the collection's order. A rate
     * whose parent isn't in the collection starts a tree of its own, as does
     * any caught in a loop of parents. Each one carries its depth and its
     * path, e.g. "Microsoft 365 › Business Standard".
     *
     * @return list<array{id: string, parent_id: ?string, name: string, description: ?string, prices: list<array{currency: string, price_in_cents: int, cost_in_cents: ?int}>, billing_period_in_months: int, prorates_first_month: bool, is_archived: bool, depth: int, path: string}>
     */
    public function toSummaries(): array
    {
        $byId = [];
        $children = [];

        foreach ($this->values() as $rate) {
            $byId[$rate->getId()->toString()] = $rate;
        }

        foreach ($byId as $id => $rate) {
            $parentId = $rate->getParentId()->toNullableString();
            $children[$parentId !== null && isset($byId[$parentId]) ? $parentId : ''][] = $id;
        }

        $order = [];
        $visited = [];

        foreach ([...$children[''] ?? [], ...array_keys($byId)] as $id) {
            self::walk($id, 0, [], $byId, $children, $order, $visited);
        }

        return array_map(fn (array $place): array => [...$byId[$place['id']]->toSummary(), 'depth' => $place['depth'], 'path' => $place['path']], $order);
    }

    /**
     * Places one rate in the tree order, then the rates under it.
     *
     * @param  list<string>  $ancestorNames
     * @param  array<string, Rate>  $byId
     * @param  array<string, list<string>>  $children
     * @param  list<array{id: string, depth: int, path: string}>  $order
     * @param  array<string, true>  $visited
     */
    private static function walk(string $id, int $depth, array $ancestorNames, array $byId, array $children, array &$order, array &$visited): void
    {
        if (isset($visited[$id])) {
            return;
        }

        $visited[$id] = true;
        $names = [...$ancestorNames, $byId[$id]->getName()->toString()];
        $order[] = ['id' => $id, 'depth' => $depth, 'path' => implode(self::PATH_SEPARATOR, $names)];

        foreach ($children[$id] ?? [] as $childId) {
            self::walk($childId, $depth + 1, $names, $byId, $children, $order, $visited);
        }
    }

    /**
     * Whether a rate sits anywhere under another, following parents up from
     * it. A loop in the stored parents ends the walk rather than spinning.
     */
    public function isUnder(string $rateId, string $ancestorId): bool
    {
        $parents = [];

        foreach ($this->values() as $rate) {
            $parents[$rate->getId()->toString()] = $rate->getParentId()->toNullableString();
        }

        $seen = [];
        $current = $parents[$rateId] ?? null;

        while ($current !== null && ! isset($seen[$current])) {
            if ($current === $ancestorId) {
                return true;
            }

            $seen[$current] = true;
            $current = $parents[$current] ?? null;
        }

        return false;
    }
}
