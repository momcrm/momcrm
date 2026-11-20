<?php

declare(strict_types=1);

namespace App\Portal\Middleware;

use App\Portal\PortalAuth;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends a contact who is already signed in from the portal's sign-in pages to
 * the portal itself.
 */
final class RedirectIfInPortal
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard(PortalAuth::GUARD)->check()) {
            return to_route('portal.home');
        }

        return $next($request);
    }
}
