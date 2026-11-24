<?php

declare(strict_types=1);

namespace App\Team;

use Closure;
use Illuminate\Http\Request;

/**
 * What someone can do about their team being suspended, shown as a link in
 * the banner that tells them it is. The app itself has nothing to offer:
 * whatever suspends teams, such as billing on a hosted install, says where
 * to go from its service provider:
 *
 *     $this->callAfterResolving(SuspendedTeamAction::class, fn (SuspendedTeamAction $action) => $action
 *         ->using(fn (Request $request, Team $team): array => [
 *             'label' => 'Subscribe',
 *             'href' => route('subscription.show'),
 *         ]));
 *
 * It decides per request, so it can offer the owner something else than a
 * member, or nothing at all.
 */
final class SuspendedTeamAction
{
    /**
     * @var (Closure(Request, Team): ?array{label: string, href: string})|null
     */
    private ?Closure $resolver = null;

    /**
     * @param  Closure(Request, Team): ?array{label: string, href: string}  $resolver
     */
    public function using(Closure $resolver): self
    {
        $this->resolver = $resolver;

        return $this;
    }

    /**
     * @return ?array{label: string, href: string} Null when the team is not suspended, or there is nothing to do.
     */
    public function for(Request $request, Team $team): ?array
    {
        if ($this->resolver === null || ! $team->isSuspended()) {
            return null;
        }

        return ($this->resolver)($request, $team);
    }
}
