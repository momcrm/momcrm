<?php

declare(strict_types=1);

namespace App\Auth\Controllers;

use App\Auth\Actions\LogInUser;
use App\Auth\AuthSession;
use App\EmailCode\Actions\CreateEmailCode;
use App\EmailCode\EmailCodePurposeEnum;
use App\SocialAccount\Actions\FindSocialAccount;
use App\SocialAccount\Actions\LinkSocialAccount;
use App\SocialAccount\SocialProviderEnum;
use App\User\Properties\Email;
use App\User\UserEloquentModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Socialite\Contracts\User as SocialUser;
use Laravel\Socialite\Socialite;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Where Google and Microsoft send people back. A linked account signs straight
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

        try {
            $socialUser = Socialite::driver($found->value)->user();
        } catch (Throwable) {
            return self::failed($found);
        }

        $email = Str::lower((string) $socialUser->getEmail());

        if ($email === '') {
            return self::failed($found);
        }

        $linkedUser = FindSocialAccount::userFor($found, (string) $socialUser->getId());

        if ($linkedUser !== null) {
            return LogInUser::andRedirect($request, $linkedUser);
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

    private static function failed(SocialProviderEnum $provider): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'error', 'message' => __("We couldn't sign you in with :provider. Try again, or continue with your email.", [
            'provider' => $provider->label(),
        ])]);

        return to_route('login');
    }
}
