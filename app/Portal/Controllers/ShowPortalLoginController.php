<?php

declare(strict_types=1);

namespace App\Portal\Controllers;

use App\SocialAccount\SocialProviderEnum;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The customer portal's sign-in page: an email field, which gets a code, or
 * Google or Microsoft.
 */
final class ShowPortalLoginController
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('portal/auth/login', [
            'email' => $request->string('email')->toString() ?: null,
            'socialProviders' => SocialProviderEnum::configured(),
        ]);
    }
}
