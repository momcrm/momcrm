<?php

declare(strict_types=1);

namespace App\Auth\Controllers;

use App\Auth\Actions\LogInUser;
use App\Auth\AuthSession;
use App\EmailCode\Actions\CreateEmailCode;
use App\EmailCode\EmailCodePurposeEnum;
use App\Portal\Actions\FindPortalContacts;
use App\Portal\Actions\LogInContact;
use App\Portal\PortalSession;
use App\SocialAccount\Actions\FindSocialAccount;
use App\SocialAccount\Actions\LinkSocialAccount;
use App\SocialAccount\SocialProviderEnum;
use App\User\Properties\Email;
use App\User\UserEloquentModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Socialite\Contracts\User as SocialUser;
use Laravel\Socialite\Socialite;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Where Google and Microsoft send people back, staff and (when the portal sent
 * them) customer contacts alike. A linked account signs straight
 * in. Otherwise the email is only trusted when Google says it is verified; any
 * other address must be proven with an emailed code before it is used, so an
 * unverified provider email can never take over a MomCRM account.
 */
final class HandleProviderCallbackController
{
    public function __invoke(Request $request, string $provider): RedirectResponse
    {
        $found = SocialProviderEnum::tryFrom($provider);

        if ($found === null || ! $found->isConfigured()) {
            throw new NotFoundHttpException;
        }

        $isForPortal = $request->session()->pull(PortalSession::SOCIAL) === true;

        if (! $isForPortal && Auth::guard()->check()) {
            return to_route('dashboard');
        }

        try {
            $socialUser = Socialite::driver($found->value)->user();
        } catch (Throwable) {
            return self::failed($found, $isForPortal);
        }

        $email = Str::lower((string) $socialUser->getEmail());

        if ($email === '') {
            return self::failed($found, $isForPortal);
        }

        if ($isForPortal) {
            return self::signInToPortal($request, $found, $socialUser, $email);
        }

        $linkedUser = FindSocialAccount::userFor($found, (string) $socialUser->getId());

        if ($linkedUser->isNotNull()) {
            return LogInUser::andRedirect($request, UserEloquentModel::query()->findOrFail($linkedUser->getId()->toString()));
        }

        $request->session()->put(AuthSession::PENDING_SOCIAL, [
            'provider' => $found->value,
            'provider_user_id' => (string) $socialUser->getId(),
            'email' => $email,
        ]);
        $request->session()->put(AuthSession::SUGGESTED_NAME, $socialUser->getName());

        if (self::emailIsVerified($found, $socialUser)) {
            $user = UserEloquentModel::query()->where(Email::getDatabaseTableColumnName(), $email)->first();

            if ($user !== null) {
                LinkSocialAccount::fromSession($request->session(), $user);

                return LogInUser::andRedirect($request, $user);
            }

            $request->session()->put(AuthSession::VERIFIED_EMAIL, $email);

            return to_route('signup.profile');
        }

        CreateEmailCode::send($email, EmailCodePurposeEnum::SignIn);
        $request->session()->put(AuthSession::PENDING_EMAIL, $email);

        Inertia::flash('toast', ['type' => 'info', 'message' => __('To finish with :provider, enter the code we emailed to :email.', [
            'provider' => $found->label(),
            'email' => $email,
        ])]);

        return to_route('auth.code');
    }

    private static function emailIsVerified(SocialProviderEnum $provider, SocialUser $socialUser): bool
    {
        if ($provider !== SocialProviderEnum::Google || ! method_exists($socialUser, 'getRaw')) {
            return false;
        }

        $raw = $socialUser->getRaw();

        return is_array($raw) && ($raw['email_verified'] ?? false) === true;
    }

    /**
     * A contact signing in to the customer portal. As for staff, the address
     * is only trusted when Google says it is verified; otherwise a portal
     * code is emailed to it first, if it has portal access at all.
     */
    private static function signInToPortal(Request $request, SocialProviderEnum $provider, SocialUser $socialUser, string $email): RedirectResponse
    {
        if (self::emailIsVerified($provider, $socialUser)) {
            return LogInContact::forProvenEmail($request, $email);
        }

        if (! FindPortalContacts::existForEmail($email)) {
            return LogInContact::forProvenEmail($request, $email);
        }

        CreateEmailCode::send($email, EmailCodePurposeEnum::PortalSignIn);
        $request->session()->put(PortalSession::PENDING_EMAIL, $email);

        Inertia::flash('toast', ['type' => 'info', 'message' => __('To finish with :provider, enter the code we emailed to :email.', [
            'provider' => $provider->label(),
            'email' => $email,
        ])]);

        return to_route('portal.login.code');
    }

    private static function failed(SocialProviderEnum $provider, bool $isForPortal = false): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'error', 'message' => __("We couldn't sign you in with :provider. Try again, or continue with your email.", [
            'provider' => $provider->label(),
        ])]);

        return to_route($isForPortal ? 'portal.login' : 'login');
    }
}
