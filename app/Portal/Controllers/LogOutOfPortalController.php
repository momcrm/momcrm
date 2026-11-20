<?php

declare(strict_types=1);

namespace App\Portal\Controllers;

use App\Portal\PortalAuth;
use App\Portal\PortalSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Signs out of the customer portal only. The session lives on, as the same
 * browser may also be signed in to MomCRM as staff.
 */
final class LogOutOfPortalController
{
    public function __invoke(Request $request): RedirectResponse
    {
        Auth::guard(PortalAuth::GUARD)->logout();
        $request->session()->forget([PortalSession::VERIFIED_EMAIL, PortalSession::INTENDED]);
        $request->session()->regenerateToken();

        return to_route('portal.login');
    }
}
