<?php

declare(strict_types=1);

namespace App\Team\Actions;

use App\Team\Team;
use Carbon\CarbonImmutable;

/**
 * Suspends a team, or reactivates it. A suspended team is read-only: its
 * members still sign in and see everything, but no request may change it.
 * Nothing is deleted, so reactivating puts it back exactly as it was.
 */
final readonly class SuspendTeam
{
    public function __construct(
        private Team $team,
        private bool $suspend = true,
    ) {}

    public function __invoke(): Team
    {
        if ($this->suspend === $this->team->isSuspended()) {
            return $this->team;
        }

        return UpdateTeam::fromTeam($this->team->setSuspendedAt($this->suspend ? CarbonImmutable::now() : null));
    }

    public static function suspend(Team $team): Team
    {
        return new self($team, suspend: true)->handle();
    }

    public static function reactivate(Team $team): Team
    {
        return new self($team, suspend: false)->handle();
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
