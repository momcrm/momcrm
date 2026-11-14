<?php

declare(strict_types=1);

namespace App\TeamUser\Actions;

use App\TeamUser\Properties\Id;
use App\TeamUser\TeamRoleEnum;
use App\TeamUser\TeamUser;
use App\TeamUser\TeamUserEloquentModel;

final readonly class CreateTeamUser
{
    public function __construct(
        private TeamUser $teamUser,
    ) {}

    public function __invoke(): TeamUser
    {
        if ($this->teamUser->getId()->isNull()) {
            $this->teamUser->setId(Id::generate());
        }

        $model = TeamUserEloquentModel::query()->create($this->teamUser->forDatabaseCreate());

        return TeamUser::fromEloquentModel($model);
    }

    public static function fromTeamUser(TeamUser $teamUser): TeamUser
    {
        return new self($teamUser)->handle();
    }

    public static function fromIds(string $teamId, string $userId, TeamRoleEnum $role = TeamRoleEnum::Member): TeamUser
    {
        $teamUser = TeamUser::new()
            ->setId(Id::generate())
            ->setTeamId($teamId)
            ->setUserId($userId)
            ->setRole($role);

        return self::fromTeamUser($teamUser);
    }

    public function handle(): TeamUser
    {
        return $this->__invoke();
    }

    public function execute(): TeamUser
    {
        return $this->__invoke();
    }
}
