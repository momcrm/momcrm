<?php

declare(strict_types=1);

namespace App\Portal\Controllers;

use App\Auth\Actions\FindProvenEmail;
use App\Portal\Actions\FindPortalContacts;
use App\Portal\PortalAuth;
use App\User\Properties\Email;
use App\User\UserEloquentModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Where to?": for an address that can sign in to more than one place, its
 * staff workspace or the portal of any customer it is a contact at, just
 * after signing in or to switch later.
 */
final class ShowPortalChoiceController
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        $email = FindProvenEmail::forRequest($request);
        $choices = FindPortalContacts::choicesForEmail($email);
        $user = $email === null
            ? null
            : UserEloquentModel::query()->where(Email::getDatabaseTableColumnName(), $email)->first();

        if ($choices === [] && $user === null) {
            return to_route('login');
        }

        $isStaff = Auth::guard('web')->check();

        return Inertia::render('portal/auth/choose', [
            'choices' => $choices,
            'workspace' => $user === null ? null : ['name' => $user->name, 'email' => $user->email],
            'currentId' => $isStaff ? null : PortalAuth::contact()?->id,
            'isInWorkspace' => $isStaff,
        ]);
    }
}
