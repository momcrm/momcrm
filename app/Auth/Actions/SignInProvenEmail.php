<?php

declare(strict_types=1);

namespace App\Auth\Actions;

use App\Auth\AuthSession;
use App\Portal\Actions\FindPortalContacts;
use App\Portal\Actions\LogInContact;
use App\SocialAccount\Actions\LinkSocialAccount;
use App\User\Properties\Email;
use App\User\UserEloquentModel;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Where someone goes once they have proven their email with a code, Google or
 * Microsoft. Staff go to their workspace and a customer's contact to the
 * portal; someone who is both chooses. A new address goes on to sign-up.
 */
final class SignInProvenEmail
{
    public static function andRedirect(Request $request, string $email): RedirectResponse
    {
        $email = mb_strtolower($email);
        $user = UserEloquentModel::query()->where(Email::getDatabaseTableColumnName(), $email)->first();
        $isContact = FindPortalContacts::existForEmail($email);

        if ($user === null && ! $isContact) {
            $request->session()->put(AuthSession::VERIFIED_EMAIL, $email);

            return to_route('signup.profile');
        }

        $request->session()->put(AuthSession::PROVEN_EMAIL, $email);

        if ($user === null) {
            return LogInContact::forProvenEmail($request, $email);
        }

        if ($user->email_verified_at === null) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        if ($isContact) {
            return to_route('portal.choose');
        }

        LinkSocialAccount::fromSession($request->session(), $user);

        return LogInUser::andRedirect($request, $user);
    }
}
