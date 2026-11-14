<?php

declare(strict_types=1);

namespace App\Auth\Controllers;

use App\Auth\AuthSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

final class ShowCreateAccountController
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        $email = $request->session()->get(AuthSession::VERIFIED_EMAIL);

        if (! is_string($email)) {
            return to_route('signup');
        }

        return Inertia::render('auth/create-account', [
            'email' => $email,
            'suggestedName' => $request->session()->get(AuthSession::SUGGESTED_NAME),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]);
    }
}
