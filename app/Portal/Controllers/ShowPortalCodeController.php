<?php

declare(strict_types=1);

namespace App\Portal\Controllers;

use App\EmailCode\Actions\CreateEmailCode;
use App\Portal\PortalSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Check your email" for the portal: where the emailed code is typed.
 */
final class ShowPortalCodeController
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        $email = $request->session()->get(PortalSession::PENDING_EMAIL);

        if (! is_string($email)) {
            return to_route('portal.login');
        }

        return Inertia::render('portal/auth/code', [
            'email' => $email,
            'minutesValid' => CreateEmailCode::MINUTES_VALID,
        ]);
    }
}
