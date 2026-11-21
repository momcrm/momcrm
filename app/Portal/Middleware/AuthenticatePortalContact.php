<?php

declare(strict_types=1);

namespace App\Portal\Middleware;

use App\Portal\PortalAccess;
use App\Portal\PortalAuth;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lets only a signed-in contact with portal access through, and makes them
 * the person every check in the request is about. A contact whose access was
 * turned off is signed out; staff are sent to their own dashboard.
 */
final class AuthenticatePortalContact
{
    /**
     * How often someone's "last seen in the portal" is brought up to date.
     */
    private const int SEEN_EVERY_MINUTES = 5;

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $contact = PortalAuth::contact();

        if ($contact === null) {
            return Auth::guard('web')->check()
                ? to_route('dashboard')
                : redirect()->guest(route('login'));
        }

        if (! $contact->portal_access) {
            Auth::guard(PortalAuth::GUARD)->logout();
            $request->session()->regenerateToken();

            return to_route('login')->withErrors(['email' => __('Your customer portal access has been turned off.')]);
        }

        Auth::shouldUse(PortalAuth::GUARD);

        if (! PortalAccess::isViewingAs() && ($contact->portal_last_seen_at === null || $contact->portal_last_seen_at->lt(CarbonImmutable::now()->subMinutes(self::SEEN_EVERY_MINUTES)))) {
            $contact->newQueryWithoutScopes()->whereKey($contact->id)->update(['portal_last_seen_at' => CarbonImmutable::now()]);
        }

        return $next($request);
    }
}
