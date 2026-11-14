<?php

declare(strict_types=1);

namespace App\Onboarding\Middleware;

use App\User\UserEloquentModel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RedirectIfOnboarded
{
    /**
     * Keep users who have finished onboarding out of it.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof UserEloquentModel && $user->onboarded_at !== null) {
            return to_route('dashboard');
        }

        return $next($request);
    }
}
