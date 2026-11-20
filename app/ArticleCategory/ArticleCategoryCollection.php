<?php

declare(strict_types=1);

namespace App\ArticleCategory;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, ArticleCategory>
 */
final class ArticleCategoryCollection extends AbstractDataCollection
{
    public const string PATH_SEPARATOR = ' › ';

    public static function dataClass(): string
    {
        return ArticleCategory::class;
    }

    /**
     * Each category in tree order: every category followed by the ones under
     * it, each level in the collection's order. One whose parent isn't in the
     * collection, or that is caught in a loop of parents, starts a tree of its
     * own. Each carries its depth and its path, e.g. "Networking › Routers".
     *
     * @return list<array{id: string, parent_id: ?string, name: string, depth: int, path: string}>
     */
    public function toSummaries(): array
    {
        $byId = [];
        $children = [];

        foreach ($this->values() as $category) {
            $byId[$category->getId()->toString()] = $category;
        }

        foreach ($byId as $id => $category) {
            $parentId = $category->getParentId()->toNullableString();
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
     * The category's id and the ids of every category under it, however deep.
     *
     * @return list<string>
     */
    public function withDescendantIds(string $categoryId): array
    {
        $ids = [$categoryId];
        $seen = [$categoryId => true];

        for ($index = 0; $index < count($ids); $index++) {
            foreach ($this->values() as $category) {
                $id = $category->getId()->toString();

                if ($category->getParentId()->toNullableString() === $ids[$index] && ! isset($seen[$id])) {
                    $seen[$id] = true;
                    $ids[] = $id;
                }
            }
        }

        return $ids;
    }

    /**
     * @param  list<string>  $ancestorNames
     * @param  array<string, ArticleCategory>  $byId
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
}
