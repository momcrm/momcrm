<?php

declare(strict_types=1);

namespace App\Milestone\Factories;

use App\Milestone\MilestoneEloquentModel;
use App\Milestone\Properties\CompletedAt;
use App\Milestone\Properties\DueOn;
use App\Milestone\Properties\Name;
use App\Milestone\Properties\ProjectId;
use App\Milestone\Properties\TeamId;
use App\Project\ProjectEloquentModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MilestoneEloquentModel>
 */
final class MilestoneFactory extends Factory
{
    protected $model = MilestoneEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            ProjectId::getDatabaseTableColumnName() => ProjectEloquentModel::factory(),
            TeamId::getDatabaseTableColumnName() => fn (array $attributes): string => (string) ProjectEloquentModel::query()
                ->withoutGlobalScopes()
                ->whereKey($attributes[ProjectId::getDatabaseTableColumnName()])
                ->value('team_id'),
            Name::getDatabaseTableColumnName() => fake()->words(2, true),
            DueOn::getDatabaseTableColumnName() => fake()->dateTimeBetween('now', '+2 months')->format('Y-m-d'),
            CompletedAt::getDatabaseTableColumnName() => null,
        ];
    }
}
