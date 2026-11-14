<?php

declare(strict_types=1);

namespace App\Team\Actions;

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
     * Create the team and add its owner as a member with the owner role.
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
