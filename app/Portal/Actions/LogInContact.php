<?php

declare(strict_types=1);

namespace App\Portal\Actions;

use App\Contact\Contact;
use App\Contact\ContactEloquentModel;
use App\Portal\PortalAuth;
use App\Portal\PortalSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Signs someone in to the customer portal once their email is proven. With
 * one portal contact for the address they go straight in; with several they
 * pick which customer first; with none they are told so.
 */
final class LogInContact
{
    public static function forProvenEmail(Request $request, string $email): RedirectResponse
    {
        $contacts = FindPortalContacts::forEmail($email);

        if ($contacts->count() === 0) {
            return to_route('login')->withErrors([
                'email' => __("There's no customer portal access for :email. Ask your provider to turn it on.", ['email' => $email]),
            ]);
        }

        if ($contacts->count() === 1) {
            return self::as($request, $contacts->first());
        }

        return to_route('portal.choose');
    }

    /**
     * A browser is signed in as staff or as a customer's contact, never both,
     * so this signs it out of the app as staff.
     */
    public static function as(Request $request, Contact $contact): RedirectResponse
    {
        $model = ContactEloquentModel::query()->withoutGlobalScopes()->findOrFail($contact->getId()->toString());

        Auth::guard('web')->logout();
        Auth::guard(PortalAuth::GUARD)->login($model, remember: true);
        $request->session()->regenerate();
        $request->session()->forget(PortalSession::VIEWING_AS);

        return redirect()->intended(route('portal.home'));
    }
}
