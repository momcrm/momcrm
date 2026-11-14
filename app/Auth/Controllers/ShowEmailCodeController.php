<?php

declare(strict_types=1);

namespace App\Auth\Controllers;

use App\Auth\AuthSession;
use App\EmailCode\Actions\CreateEmailCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

final class ShowEmailCodeController
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        $email = $request->session()->get(AuthSession::PENDING_EMAIL);

        if (! is_string($email)) {
            return to_route('register');
        }

        return Inertia::render('auth/code', [
            'email' => $email,
            'minutesValid' => CreateEmailCode::MINUTES_VALID,
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
        ]);
    }
}
