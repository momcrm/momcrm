<?php

declare(strict_types=1);

namespace App\Team\Actions;

use App\Team\Team;
use App\User\User;
use Illuminate\Support\Str;
use Throwable;

final readonly class CreatePersonalTeam
{
    public function __construct(
        private User $user,
    ) {}

    /**
     * Create the user's personal team and make it their current team.
     *
     * @throws Throwable
     */
    public function __invoke(): Team
    {
        $team = CreateTeam::fromTeam(
            Team::new()
                ->setName(Str::of($this->user->getName()->toString())->before(' ')->append("'s Team")->toString())
                ->setIsPersonal(true)
                ->setOwnerId($this->user->getId()->toString()),
        );

        SwitchCurrentTeam::fromTeam(user: $this->user, team: $team);

        return $team;
    }

    /**
     * @throws Throwable
     */
    public static function fromUser(User $user): Team
    {
        return new self($user)->handle();
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
