<?php

declare(strict_types=1);

namespace App\Portal\Controllers;

use App\Portal\PortalSession;
use App\SocialAccount\SocialProviderEnum;
use Illuminate\Http\Request;
use Laravel\Socialite\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Sends a contact to Google or Microsoft to sign in to the portal. They come
 * back through the same callback as staff, which this marks as a portal
 * sign-in, so no new redirect address has to be registered.
 */
final class RedirectPortalToProviderController
{
    public function __invoke(Request $request, string $provider): RedirectResponse
    {
        $found = SocialProviderEnum::tryFrom($provider);

        if ($found === null || ! $found->isConfigured()) {
            throw new NotFoundHttpException;
        }

        $request->session()->put(PortalSession::SOCIAL, true);

        return Socialite::driver($found->value)->redirect();
    }
}
