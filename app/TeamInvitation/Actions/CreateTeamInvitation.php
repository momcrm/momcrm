<?php

declare(strict_types=1);

namespace App\TeamInvitation\Actions;

use App\Activity\Actions\RecordActivity;
use App\Activity\ActivityTypeEnum;
use App\Team\Team;
use App\TeamInvitation\Notifications\TeamInvitationNotification;
use App\TeamInvitation\Properties\Id;
use App\TeamInvitation\Properties\TokenHash;
use App\TeamInvitation\TeamInvitation;
use App\TeamInvitation\TeamInvitationEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\User\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * Invites an email address to a team and emails them a link to join.
 *
 * Inviting an address again replaces its earlier link, role and group and
 * restarts the 7 days.
 */
final readonly class CreateTeamInvitation
{
    public const int DAYS_VALID = 7;

    public function __construct(
        private Team $team,
        private string $email,
        private TeamRoleEnum $role,
        private User $inviter,
        private ?string $groupId = null,
    ) {}

    public function __invoke(): TeamInvitation
    {
        $token = Str::random(40);

        $invitation = FindTeamInvitation::byEmailForTeam(email: $this->email, teamId: $this->team->getId()->toString());
        $isNew = $invitation->isNull();

        $invitation
            ->setTeamId($this->team->getId())
            ->setEmail($this->email)
            ->setRole($this->role)
            ->setGroupId($this->groupId)
            ->setTokenHash(FindTeamInvitation::hashToken($token))
            ->setInvitedById($this->inviter->getId())
            ->setAcceptedAt(null)
            ->setExpiresAt(CarbonImmutable::now()->addDays(self::DAYS_VALID));

        if ($isNew) {
            $invitation->setId(Id::generate());
            TeamInvitationEloquentModel::query()->create($invitation->forDatabaseCreate());
        } else {
            TeamInvitationEloquentModel::query()
                ->withoutGlobalScopes()
                ->findOrFail($invitation->getId()->toString())
                ->update([...$invitation->forDatabaseUpdate(), TokenHash::getDatabaseTableColumnName() => FindTeamInvitation::hashToken($token)]);
        }

        Notification::route('mail', $this->email)->notify(new TeamInvitationNotification(
            teamName: $this->team->getName()->toString(),
            inviterName: $this->inviter->getName()->toString(),
            acceptUrl: route('team-invitations.show', $token),
        ));

        RecordActivity::for(
            type: ActivityTypeEnum::InvitationSent,
            userId: $this->inviter->getId()->toString(),
            teamId: $this->team->getId()->toString(),
            details: array_filter(['email' => $this->email, 'role' => $this->role->value, 'group_id' => $this->groupId]),
        );

        return $invitation;
    }

    public static function forTeam(Team $team, string $email, TeamRoleEnum $role, User $inviter, ?string $groupId = null): TeamInvitation
    {
        return new self($team, $email, $role, $inviter, $groupId)->handle();
    }

    public function handle(): TeamInvitation
    {
        return $this->__invoke();
    }

    public function execute(): TeamInvitation
    {
        return $this->__invoke();
    }
}
