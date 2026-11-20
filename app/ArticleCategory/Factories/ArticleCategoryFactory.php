<?php

declare(strict_types=1);

namespace App\ArticleCategory\Factories;

use App\ArticleCategory\ArticleCategoryEloquentModel;
use App\ArticleCategory\Properties\Name;
use App\ArticleCategory\Properties\ParentId;
use App\ArticleCategory\Properties\TeamId;
use App\Team\TeamEloquentModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ArticleCategoryEloquentModel>
 */
final class ArticleCategoryFactory extends Factory
{
    protected $model = ArticleCategoryEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            TeamId::getDatabaseTableColumnName() => TeamEloquentModel::factory(),
            Name::getDatabaseTableColumnName() => 'Category '.fake()->unique()->numerify('#####'),
        ];
    }

    public function under(ArticleCategoryEloquentModel $parent): self
    {
        return $this->state([
            TeamId::getDatabaseTableColumnName() => $parent->team_id,
            ParentId::getDatabaseTableColumnName() => $parent->id,
        ]);
    }
}
