<?php

declare(strict_types=1);

namespace App\TeamInvitation\Controllers;

use App\Team\Actions\FindTeam;
use App\TeamInvitation\Actions\FindTeamInvitation;
use App\TeamInvitation\Listeners\AcceptPendingTeamInvitation;
use App\User\Actions\FindUser;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The page an invitation link opens. Guests are asked to sign up or log in;
 * the invitation is remembered and accepted once they do.
 */
final class ShowTeamInvitationController
{
    public function __invoke(Request $request, string $token): Response
    {
        $invitation = FindTeamInvitation::byToken($token);

        if (! $invitation->isPending()) {
            return Inertia::render('auth/team-invitation', ['status' => 'expired']);
        }

        $user = $request->user();

        if ($user === null) {
            $request->session()->put(AcceptPendingTeamInvitation::SESSION_KEY, $token);
        }

        return Inertia::render('auth/team-invitation', [
            'status' => match (true) {
                $user === null => 'guest',
                $invitation->isForEmail((string) $user->email) => 'valid',
                default => 'wrong-account',
            },
            'token' => $token,
            'email' => $invitation->getEmail()->toString(),
            'teamName' => FindTeam::byId($invitation->getTeamId()->toString())->getName()->toString(),
            'inviterName' => FindUser::byId($invitation->getInvitedById()->toNullableString())->getName()->toNullableString(),
        ]);
    }
}
