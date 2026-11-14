<?php

declare(strict_types=1);

namespace App\TeamInvitation\Actions;

use App\TeamInvitation\TeamInvitation;
use App\TeamInvitation\TeamInvitationEloquentModel;

final readonly class RevokeTeamInvitation
{
    public function __construct(
        private TeamInvitation $invitation,
    ) {}

    public function __invoke(): void
    {
        TeamInvitationEloquentModel::query()
            ->withoutGlobalScopes()
            ->find($this->invitation->getId()->toString())
            ?->delete();
    }

    public static function fromTeamInvitation(TeamInvitation $invitation): void
    {
        new self($invitation)->handle();
    }

    public function handle(): void
    {
        $this->__invoke();
    }

    public function execute(): void
    {
        $this->__invoke();
    }
}
