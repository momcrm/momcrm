<?php

declare(strict_types=1);

namespace App\Portal\Controllers;

use App\Auth\AuthSession;
use App\Portal\PortalAuth;
use App\Portal\PortalSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Signs out of the customer portal only. The session lives on, as a staff
 * member viewing the portal as a contact is still signed in as staff.
 */
final class LogOutOfPortalController
{
    public function __invoke(Request $request): RedirectResponse
    {
        Auth::guard(PortalAuth::GUARD)->logout();
        $request->session()->forget([AuthSession::PROVEN_EMAIL, PortalSession::VIEWING_AS]);
        $request->session()->regenerateToken();

        return to_route('login');
    }
}
