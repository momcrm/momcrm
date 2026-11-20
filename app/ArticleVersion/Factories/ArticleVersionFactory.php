<?php

declare(strict_types=1);

namespace App\ArticleVersion\Factories;

use App\Article\ArticleEloquentModel;
use App\ArticleVersion\ArticleVersionEloquentModel;
use App\ArticleVersion\Properties\ArticleId;
use App\ArticleVersion\Properties\Body;
use App\ArticleVersion\Properties\BodyText;
use App\ArticleVersion\Properties\Classification;
use App\ArticleVersion\Properties\SavedAt;
use App\ArticleVersion\Properties\TeamId;
use App\ArticleVersion\Properties\Title;
use App\ArticleVersion\Properties\VersionMajor;
use App\ArticleVersion\Properties\VersionMinor;
use App\Shared\Types;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ArticleVersionEloquentModel>
 */
final class ArticleVersionFactory extends Factory
{
    protected $model = ArticleVersionEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            TeamId::getDatabaseTableColumnName() => fn (array $attributes): string => ArticleEloquentModel::query()->withoutGlobalScopes()->whereKey(Types::string($attributes[ArticleId::getDatabaseTableColumnName()]))->firstOrFail()->team_id,
            ArticleId::getDatabaseTableColumnName() => ArticleEloquentModel::factory()->published(),
            VersionMajor::getDatabaseTableColumnName() => 1,
            VersionMinor::getDatabaseTableColumnName() => 0,
            Title::getDatabaseTableColumnName() => 'An older title',
            Body::getDatabaseTableColumnName() => '<p>Older words.</p>',
            BodyText::getDatabaseTableColumnName() => 'Older words.',
            Classification::getDatabaseTableColumnName() => 'internal',
            SavedAt::getDatabaseTableColumnName() => now()->subDay(),
        ];
    }

    public function of(ArticleEloquentModel $article): self
    {
        return $this->state([
            ArticleId::getDatabaseTableColumnName() => $article->id,
            TeamId::getDatabaseTableColumnName() => $article->team_id,
        ]);
    }
}
