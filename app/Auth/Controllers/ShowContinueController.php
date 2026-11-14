<?php

declare(strict_types=1);

namespace App\Auth\Controllers;

use App\SocialAccount\SocialProviderEnum;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

/**
 * The email-first page for both signing up and logging in, as in Notion:
 * one email field, then a code is emailed.
 */
final class ShowContinueController
{
    public const string MODE_SIGN_UP = 'sign_up';

    public const string MODE_LOG_IN = 'log_in';

    public function __invoke(Request $request): Response
    {
        return self::render($request, self::MODE_SIGN_UP);
    }

    public static function render(Request $request, string $mode): Response
    {
        return Inertia::render('auth/continue', [
            'mode' => $mode,
            'email' => $request->string('email')->toString() ?: null,
            'status' => $request->session()->get('status'),
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'socialProviders' => SocialProviderEnum::configured(),
        ]);
    }
}
