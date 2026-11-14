<?php

declare(strict_types=1);

namespace App\Auth\Actions;

use App\User\UserEloquentModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Features;

/**
 * Logs someone in after they proved who they are with an emailed code or a
 * Google/Microsoft account. Accounts with two-factor authentication still go
 * through Fortify's two-factor challenge, exactly as a password log-in does.
 */
final class LogInUser
{
    public static function andRedirect(Request $request, UserEloquentModel $user): RedirectResponse
    {
        if (Features::enabled(Features::twoFactorAuthentication())
            && $user->two_factor_secret !== null
            && $user->two_factor_confirmed_at !== null) {
            $request->session()->put([
                'login.id' => $user->getKey(),
                'login.remember' => true,
            ]);

            return to_route('two-factor.login');
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
