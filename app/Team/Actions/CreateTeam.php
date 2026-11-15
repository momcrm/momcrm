<?php

declare(strict_types=1);

namespace App\Team\Actions;

use App\AccessRule\Actions\SeedDefaultAccessRules;
use App\Choice\Actions\SeedDefaultChoices;
use App\Team\Properties\Id;
use App\Team\Team;
use App\Team\TeamEloquentModel;
use App\TeamUser\Actions\CreateTeamUser;
use App\TeamUser\TeamRoleEnum;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class CreateTeam
{
    public function __construct(
        private Team $team,
    ) {}

    /**
     * Create the team, add its owner as a member with the owner role and give
     * it the default access rules and choices.
     *
     * @throws Throwable
     */
    public function __invoke(): Team
    {
        if ($this->team->getId()->isNull()) {
            $this->team->setId(Id::generate());
        }

        return DB::transaction(function (): Team {
            $model = TeamEloquentModel::query()->create($this->team->forDatabaseCreate());

            CreateTeamUser::fromIds(
                teamId: $model->id,
                userId: $model->owner_id,
                role: TeamRoleEnum::Owner,
            );

            SeedDefaultAccessRules::forTeam($model->id);
            SeedDefaultChoices::forTeam($model->id);

            return Team::fromEloquentModel($model);
        });
    }

    /**
     * @throws Throwable
     */
    public static function fromTeam(Team $team): Team
    {
        return new self($team)->handle();
    }

    /**
     * @throws Throwable
     */
    public function handle(): Team
    {
        return $this->__invoke();
    }

    /**
     * @throws Throwable
     */
    public function execute(): Team
    {
        return $this->__invoke();
    }
}
