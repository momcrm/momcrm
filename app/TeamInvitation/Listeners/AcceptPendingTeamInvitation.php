<?php

declare(strict_types=1);

namespace App\TeamInvitation\Listeners;

use App\TeamInvitation\Actions\AcceptTeamInvitation;
use App\TeamInvitation\Actions\FindTeamInvitation;
use App\User\User;
use App\User\UserEloquentModel;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;

/**
 * Accepts the invitation a guest opened before they signed up or logged in.
 */
final class AcceptPendingTeamInvitation
{
    public const string SESSION_KEY = 'team_invitation_token';

    /**
     * @var array<int, class-string>
     */
    public const array EVENTS = [
        Login::class,
        Registered::class,
    ];

    public function handle(Login|Registered $event): void
    {
        if (! $event->user instanceof UserEloquentModel || ! app()->bound('session.store')) {
            return;
        }

        $token = session()->pull(self::SESSION_KEY);

        if (! is_string($token)) {
            return;
        }

        $invitation = FindTeamInvitation::byToken($token);

        if ($invitation->isPending() && $invitation->isForEmail($event->user->email)) {
            AcceptTeamInvitation::forUser($invitation, User::fromEloquentModel($event->user));
        }
    }
}
