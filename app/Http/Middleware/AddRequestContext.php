<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Shared\RequestContext;
use App\User\UserEloquentModel;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds a request id, the user and their team to Laravel's Context, so every
 * log line (and job dispatched from this request) carries them. The request
 * id is returned in the X-Request-Id header and stored on audit/activity rows.
 */
final class AddRequestContext
{
    public const string HEADER = 'X-Request-Id';

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $incoming = $request->headers->get(self::HEADER);
        $requestId = is_string($incoming) && Str::isUuid($incoming) ? $incoming : Str::uuid7()->toString();

        Context::add(RequestContext::REQUEST_ID, $requestId);
        Context::add('url', $request->fullUrl());
        Context::add('method', $request->method());

        $user = $request->user();

        if ($user instanceof UserEloquentModel) {
            Context::add(RequestContext::USER_ID, $user->id);
            Context::add(RequestContext::TEAM_ID, $user->current_team_id);
        }

        $response = $next($request);

        $response->headers->set(self::HEADER, $requestId);

        return $response;
    }
}
