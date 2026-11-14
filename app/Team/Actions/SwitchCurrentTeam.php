<?php

declare(strict_types=1);

namespace App\Team\Actions;

use App\Team\Team;
use App\User\Actions\UpdateUser;
use App\User\User;

final readonly class SwitchCurrentTeam
{
    public function __construct(
        private User $user,
        private Team $team,
    ) {}

    /**
     * Make the team the user's current team. Callers must check membership first.
     */
    public function __invoke(): User
    {
        return UpdateUser::fromUser(
            $this->user->setCurrentTeamId($this->team->getId()->toString()),
        );
    }

    public static function fromTeam(User $user, Team $team): User
    {
        return new self($user, $team)->handle();
    }

    public function handle(): User
    {
        return $this->__invoke();
    }

    public function execute(): User
    {
        return $this->__invoke();
    }
}
