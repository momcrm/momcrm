<?php

declare(strict_types=1);

namespace App\Project\Factories;

use App\Project\ProjectEloquentModel;
use App\Project\ProjectStatusEnum;
use App\Project\ProjectVisibilityEnum;
use App\Project\Properties\ArchivedAt;
use App\Project\Properties\Color;
use App\Project\Properties\CustomerId;
use App\Project\Properties\Description;
use App\Project\Properties\DueOn;
use App\Project\Properties\GroupId;
use App\Project\Properties\Icon;
use App\Project\Properties\IsSample;
use App\Project\Properties\LeadId;
use App\Project\Properties\Name;
use App\Project\Properties\Number;
use App\Project\Properties\StartsOn;
use App\Project\Properties\Status;
use App\Project\Properties\TeamId;
use App\Project\Properties\Visibility;
use App\Shared\ColorEnum;
use App\Team\TeamEloquentModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectEloquentModel>
 */
final class ProjectFactory extends Factory
{
    protected $model = ProjectEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            TeamId::getDatabaseTableColumnName() => TeamEloquentModel::factory(),
            Number::getDatabaseTableColumnName() => fake()->unique()->numberBetween(1, 1000000),
            CustomerId::getDatabaseTableColumnName() => null,
            LeadId::getDatabaseTableColumnName() => null,
            GroupId::getDatabaseTableColumnName() => null,
            Name::getDatabaseTableColumnName() => fake()->sentence(3, false),
            Description::getDatabaseTableColumnName() => fake()->sentence(),
            Icon::getDatabaseTableColumnName() => null,
            Color::getDatabaseTableColumnName() => fake()->randomElement(ColorEnum::values()),
            Status::getDatabaseTableColumnName() => ProjectStatusEnum::InProgress->value,
            Visibility::getDatabaseTableColumnName() => ProjectVisibilityEnum::Open->value,
            StartsOn::getDatabaseTableColumnName() => null,
            DueOn::getDatabaseTableColumnName() => null,
            ArchivedAt::getDatabaseTableColumnName() => null,
            IsSample::getDatabaseTableColumnName() => false,
        ];
    }

    public function archived(): self
    {
        return $this->state([ArchivedAt::getDatabaseTableColumnName() => now()]);
    }
}
