<?php

namespace App\Providers;

use App\Auth\Controllers\ShowContinueController;
use App\User\Actions\ResetUserPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        // Sign-up and log-in share one email-first page, as in Notion; /register is our own route.
        Fortify::loginView(fn (Request $request) => ShowContinueController::render($request, ShowContinueController::MODE_LOG_IN));

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/reset-password', [
            'email' => $request->email,
            'token' => $request->route('token'),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]));

        Fortify::requestPasswordResetLinkView(fn (Request $request) => Inertia::render('auth/forgot-password', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::verifyEmailView(fn (Request $request) => Inertia::render('auth/verify-email', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::twoFactorChallengeView(fn () => Inertia::render('auth/two-factor-challenge'));

        Fortify::confirmPasswordView(fn (Request $request) => Inertia::render('auth/confirm-password', [
            'hasPassword' => $request->user()?->password !== null,
            'email' => $request->user()?->email,
        ]));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->string(Fortify::username())->toString()).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('email-code-send', function (Request $request) {
            $email = $request->input('email') ?? $request->user()->email ?? $request->session()->get('auth.pending_email');
            $email = Str::transliterate(Str::lower(is_string($email) ? $email : ''));

            return [
                Limit::perMinute(3)->by('email|'.$email),
                Limit::perMinute(10)->by('ip|'.$request->ip()),
            ];
        });

        RateLimiter::for('email-code-verify', function (Request $request) {
            return Limit::perMinute(10)->by($request->session()->getId().'|'.$request->ip());
        });

        RateLimiter::for('passkeys', function (Request $request) {
            return Limit::perMinute(10)->by(
                ($request->string('credential.id')->toString() ?: $request->session()->getId()).'|'.$request->ip(),
            );
        });
    }
}
