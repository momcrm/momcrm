<?php

declare(strict_types=1);

namespace App\Portal\Controllers;

use App\Portal\Middleware\AuthenticatePortalContact;
use App\Portal\PortalAuth;
use App\Portal\StaffOrContact;
use App\Shared\Types;
use Illuminate\Http\Request;
use Illuminate\Routing\Pipeline;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use LogicException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves an address staff and customers' contacts share (see
 * StaffOrContact): the portal's page to a contact signed in to the portal,
 * the staff page to everyone else, each behind its own middleware.
 */
final class ServeStaffOrContactController
{
    public function __invoke(Request $request, Router $router): Response
    {
        $route = $request->route();

        if (! $route instanceof Route) {
            throw new LogicException('A shared address is always served from a route.');
        }

        $isContact = PortalAuth::isSignedInAsContact();

        [$class, $method] = self::controller($route->getAction($isContact ? StaffOrContact::CONTACT : StaffOrContact::STAFF));

        $middleware = $isContact
            ? [AuthenticatePortalContact::class]
            : Types::stringList($route->getAction(StaffOrContact::STAFF_ONLY_MIDDLEWARE));

        $response = new Pipeline(app())
            ->send($request)
            ->through($router->resolveMiddleware($middleware))
            ->then(fn (Request $request): Response => Router::toResponse(
                $request,
                $route->controllerDispatcher()->dispatch($route, app($class), $method),
            ));

        return Router::toResponse($request, $response);
    }

    /**
     * @return array{class-string, string}
     */
    private static function controller(mixed $action): array
    {
        if (is_string($action) && class_exists($action)) {
            return [$action, '__invoke'];
        }

        if (is_array($action) && is_string($action[0] ?? null) && class_exists($action[0]) && is_string($action[1] ?? null)) {
            return [$action[0], $action[1]];
        }

        throw new LogicException('A shared address names a controller for staff and for contacts.');
    }
}
