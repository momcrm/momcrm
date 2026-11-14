<?php

declare(strict_types=1);

namespace App\Team\Actions;

use App\Team\Properties\Id;
use App\Team\Team;
use App\Team\TeamEloquentModel;

final readonly class UpdateTeam
{
    public function __construct(
        private Team $team,
    ) {}

    public function __invoke(): Team
    {
        TeamEloquentModel::query()
            ->where(Id::getDatabaseTableColumnName(), $this->team->getId()->toString())
            ->first()
            ?->update($this->team->forDatabaseUpdate());

        return $this->team;
    }

    public static function fromTeam(Team $team): Team
    {
        return new self($team)->handle();
    }

    public function handle(): Team
    {
        return $this->__invoke();
    }

    public function execute(): Team
    {
        return $this->__invoke();
    }
}
