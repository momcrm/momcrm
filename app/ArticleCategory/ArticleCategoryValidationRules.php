<?php

declare(strict_types=1);

namespace App\ArticleCategory;

use App\ArticleCategory\Actions\FindArticleCategory;
use App\ArticleCategory\Properties\Name;
use App\ArticleCategory\Properties\ParentId;
use Closure;

/**
 * A category's name is unique among the categories beside it, whatever the
 * letter case, and it can sit under any of the team's categories except
 * itself and the ones under it.
 */
final class ArticleCategoryValidationRules
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function for(string $teamId, mixed $parentId, ?ArticleCategory $current = null): array
    {
        $categories = FindArticleCategory::listForTeam($teamId);
        $currentId = $current?->getId()->toNullableString();

        return [
            Name::getNameForValidation() => [
                'required',
                'string',
                'max:100',
                function (string $attribute, mixed $value, Closure $fail) use ($categories, $parentId, $currentId): void {
                    $name = mb_strtolower(trim(is_string($value) ? $value : ''));
                    $isTaken = $categories->contains(fn (ArticleCategory $category): bool => $category->getId()->toString() !== $currentId
                        && $category->getParentId()->toNullableString() === (is_string($parentId) && $parentId !== '' ? $parentId : null)
                        && mb_strtolower($category->getName()->toString()) === $name);

                    if ($isTaken) {
                        $fail(__('There is already a category with that name there.'));
                    }
                },
            ],
            ParentId::getNameForValidation() => [
                'nullable',
                'uuid',
                function (string $attribute, mixed $value, Closure $fail) use ($categories, $currentId): void {
                    $isTeams = $categories->contains(fn (ArticleCategory $category): bool => $category->getId()->toString() === $value);

                    if (! $isTeams) {
                        $fail(__('Choose one of your categories.'));

                        return;
                    }

                    if ($currentId !== null && in_array($value, $categories->withDescendantIds($currentId), true)) {
                        $fail(__("A category can't go inside itself or a category under it."));
                    }
                },
            ],
        ];
    }
}
