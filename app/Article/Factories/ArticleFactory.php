<?php

declare(strict_types=1);

namespace App\Article\Factories;

use App\Article\ArticleClassificationEnum;
use App\Article\ArticleEloquentModel;
use App\Article\ArticleStatusEnum;
use App\Article\Properties\ArchivedAt;
use App\Article\Properties\Body;
use App\Article\Properties\BodyText;
use App\Article\Properties\Classification;
use App\Article\Properties\PublishedAt;
use App\Article\Properties\Status;
use App\Article\Properties\TeamId;
use App\Article\Properties\Title;
use App\Team\TeamEloquentModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ArticleEloquentModel>
 */
final class ArticleFactory extends Factory
{
    protected $model = ArticleEloquentModel::class;

    /**
     * An internal draft with a short body.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sentence = fake()->sentence();

        return [
            TeamId::getDatabaseTableColumnName() => TeamEloquentModel::factory(),
            Title::getDatabaseTableColumnName() => 'How to '.fake()->unique()->numerify('fix thing ####'),
            Body::getDatabaseTableColumnName() => "<p>{$sentence}</p>",
            BodyText::getDatabaseTableColumnName() => $sentence,
            Status::getDatabaseTableColumnName() => ArticleStatusEnum::Draft->value,
            Classification::getDatabaseTableColumnName() => ArticleClassificationEnum::Internal->value,
        ];
    }

    public function published(): self
    {
        return $this->state([
            Status::getDatabaseTableColumnName() => ArticleStatusEnum::Published->value,
            PublishedAt::getDatabaseTableColumnName() => now(),
        ]);
    }

    public function classified(ArticleClassificationEnum $classification): self
    {
        return $this->state([Classification::getDatabaseTableColumnName() => $classification->value]);
    }

    public function archived(): self
    {
        return $this->state([ArchivedAt::getDatabaseTableColumnName() => now()]);
    }
}
