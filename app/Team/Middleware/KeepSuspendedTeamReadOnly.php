<?php

declare(strict_types=1);

namespace App\Team\Middleware;

use App\Portal\PortalAuth;
use App\Team\Actions\FindTeam;
use App\Team\Team;
use App\User\UserEloquentModel;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Refuses every request that would change a suspended team, from its members
 * and from its customers' contacts in the portal. Reading still works.
 *
 * Requests about the person's own account are let through: they can still
 * sign out, switch to another team, join one, and look after their profile and
 * sign-in. So are the installation's own pages, which belong to no team.
 */
final class KeepSuspendedTeamReadOnly
{
    /**
     * Route names that change the account or the installation, not the team.
     *
     * @var list<string>
     */
    public const array UNAFFECTED_ROUTES = [
        'logout',
        'current-team.update',
        'choose.workspace',
        'team-invitations.accept',
        'profile.*',
        'user-password.update',
        'password.confirm.*',
        'verification.send',
        'two-factor.*',
        'passkey.*',
        'portal.choose.store',
        'portal.view-as.stop',
        'environment.*',
    ];

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethodSafe() || $request->routeIs(...self::UNAFFECTED_ROUTES) || ! $this->team($request)->isSuspended()) {
            return $next($request);
        }

        $message = __('This workspace is suspended, so nothing in it can be changed.');

        if ($request->expectsJson() && $request->header('X-Inertia') === null) {
            abort(Response::HTTP_FORBIDDEN, $message);
        }

        Inertia::flash('toast', ['type' => 'error', 'message' => $message]);

        return back();
    }

    /**
     * The team the request would change: the contact's when the portal is
     * being served, otherwise the signed-in member's current team.
     */
    private function team(Request $request): Team
    {
        if (PortalAuth::isServingContactPage($request)) {
            return FindTeam::byId(PortalAuth::contact()?->team_id);
        }

        $user = $request->user();

        return FindTeam::byId($user instanceof UserEloquentModel ? $user->current_team_id : null);
    }
}
