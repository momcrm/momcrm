<?php

declare(strict_types=1);

namespace App\Team\Factories;

use App\AccessRule\Actions\SeedDefaultAccessRules;
use App\Choice\Actions\SeedDefaultChoices;
use App\Team\Properties\IsPersonal;
use App\Team\Properties\Name;
use App\Team\Properties\OwnerId;
use App\Team\TeamEloquentModel;
use App\TeamUser\Actions\CreateTeamUser;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeamEloquentModel>
 */
final class TeamFactory extends Factory
{
    protected $model = TeamEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            OwnerId::getDatabaseTableColumnName() => UserEloquentModel::factory(),
            Name::getDatabaseTableColumnName() => fake()->company(),
            IsPersonal::getDatabaseTableColumnName() => false,
        ];
    }

    /**
     * Add the owner to the team as a member with the owner role, and give the
     * team the default access rules and choices.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (TeamEloquentModel $team): void {
            CreateTeamUser::fromIds(
                teamId: $team->id,
                userId: $team->owner_id,
                role: TeamRoleEnum::Owner,
            );

            SeedDefaultAccessRules::forTeam($team->id);
            SeedDefaultChoices::forTeam($team->id);
        });
    }
}
