<?php

declare(strict_types=1);

namespace App\Portal\Actions;

use App\Contact\Contact;
use App\Contact\ContactEloquentModel;
use App\Portal\PortalAuth;
use App\Portal\PortalSession;
use App\Shared\Types;
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
        $request->session()->forget(PortalSession::PENDING_EMAIL);
        if (! FindPortalContacts::existForEmail($email)) {
            return to_route('portal.login')->withErrors([
                'email' => __("There's no customer portal access for :email. Ask your provider to turn it on.", ['email' => $email]),
            ]);
        }

        $contacts = FindPortalContacts::forEmail($email);

        if ($contacts->count() === 1) {
            return self::as($request, $contacts->first());
        }

        $request->session()->put(PortalSession::VERIFIED_EMAIL, mb_strtolower($email));

        return to_route('portal.choose');
    }

    /**
     * The address someone has proven for the portal: the signed-in contact's,
     * or one just proven by a code or Google while they choose a customer.
     */
    public static function provenEmail(Request $request): ?string
    {
        $contact = PortalAuth::contact();

        if ($contact !== null) {
            return $contact->email;
        }

        return Types::nullableString($request->session()->get(PortalSession::VERIFIED_EMAIL));
    }

    public static function as(Request $request, Contact $contact): RedirectResponse
    {
        $model = ContactEloquentModel::query()->withoutGlobalScopes()->findOrFail($contact->getId()->toString());

        Auth::guard(PortalAuth::GUARD)->login($model, remember: true);
        $request->session()->regenerate();
        $request->session()->put(PortalSession::VERIFIED_EMAIL, mb_strtolower((string) $model->email));

        $intended = Types::nullableString($request->session()->pull(PortalSession::INTENDED));

        return redirect()->to($intended ?? route('portal.home'));
    }
}
