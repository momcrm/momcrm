<?php

declare(strict_types=1);

namespace App\ArticleLink\Factories;

use App\Article\ArticleEloquentModel;
use App\ArticleLink\ArticleLinkEloquentModel;
use App\ArticleLink\ArticleLinkTargetEnum;
use App\ArticleLink\Properties\ArticleId;
use App\ArticleLink\Properties\LinkableId;
use App\ArticleLink\Properties\LinkableType;
use App\ArticleLink\Properties\TeamId;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<ArticleLinkEloquentModel>
 */
final class ArticleLinkFactory extends Factory
{
    protected $model = ArticleLinkEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            TeamId::getDatabaseTableColumnName() => fn (array $attributes): mixed => ArticleEloquentModel::query()->withoutGlobalScopes()->whereKey($attributes[ArticleId::getDatabaseTableColumnName()])->value('team_id'),
            ArticleId::getDatabaseTableColumnName() => ArticleEloquentModel::factory(),
            LinkableType::getDatabaseTableColumnName() => ArticleLinkTargetEnum::Ticket->value,
            LinkableId::getDatabaseTableColumnName() => fake()->uuid(),
        ];
    }

    /**
     * Attaches the article to a ticket, asset or service.
     */
    public function between(ArticleEloquentModel $article, ArticleLinkTargetEnum $target, Model $record): self
    {
        return $this->state([
            TeamId::getDatabaseTableColumnName() => $article->team_id,
            ArticleId::getDatabaseTableColumnName() => $article->id,
            LinkableType::getDatabaseTableColumnName() => $target->value,
            LinkableId::getDatabaseTableColumnName() => $record->getKey(),
        ]);
    }
}
