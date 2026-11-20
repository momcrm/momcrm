<?php

declare(strict_types=1);

namespace App\Portal\Controllers;

use App\Portal\Actions\FindPortalContacts;
use App\Portal\Actions\LogInContact;
use App\Portal\PortalAuth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Which customer?": for someone whose email is a portal contact at several
 * customers, just after signing in or to switch later.
 */
final class ShowPortalChoiceController
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        $current = PortalAuth::contact();
        $choices = FindPortalContacts::choicesForEmail(LogInContact::provenEmail($request));

        if ($choices === []) {
            return to_route('portal.login');
        }

        return Inertia::render('portal/auth/choose', [
            'choices' => $choices,
            'currentId' => $current?->id,
        ]);
    }
}
