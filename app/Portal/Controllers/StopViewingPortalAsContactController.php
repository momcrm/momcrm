<?php

declare(strict_types=1);

namespace App\Portal\Controllers;

use App\Portal\PortalAuth;
use App\Portal\PortalSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Leaves the portal after viewing it as a contact, back to the contact in
 * the app.
 */
final class StopViewingPortalAsContactController
{
    public function __invoke(Request $request): RedirectResponse
    {
        $contact = PortalAuth::contact();

        Auth::guard(PortalAuth::GUARD)->logout();
        $request->session()->forget(PortalSession::VIEWING_AS);

        return $contact === null ? to_route('dashboard') : to_route('contacts.edit', $contact->id);
    }
}
