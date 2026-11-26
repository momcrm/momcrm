<?php

declare(strict_types=1);

namespace App\Shared;

use Closure;
use Illuminate\Http\Request;

/**
 * Items that a package adds to the Settings menu, beside the app's own. The
 * app's items are built into the Settings menu; a package has no place
 * there, so it registers a contributor from its service provider:
 *
 *     $this->callAfterResolving(SettingsNavigation::class, fn (SettingsNavigation $navigation) => $navigation
 *         ->add(fn (Request $request): array => [
 *             ['section' => 'Workspace', 'title' => 'Subscription', 'href' => route('subscription.show'), 'icon' => 'credit-card'],
 *         ]));
 *
 * A contributor decides per request what to show, so it can leave an item
 * out for someone who may not open it. An item joins the section with that
 * title, or starts a new one. Its optional icon is one of the names the
 * Settings menu knows (`credit-card`, `users-round`); without one it gets
 * a puzzle piece.
 */
final class SettingsNavigation
{
    /**
     * @var list<Closure(Request): iterable<array{section: string, title: string, href: string, icon?: string}>>
     */
    private array $contributors = [];

    /**
     * @param  Closure(Request): iterable<array{section: string, title: string, href: string, icon?: string}>  $contributor
     */
    public function add(Closure $contributor): self
    {
        $this->contributors[] = $contributor;

        return $this;
    }

    /**
     * @return list<array{section: string, title: string, href: string, icon?: string}>
     */
    public function for(Request $request): array
    {
        $items = [];

        foreach ($this->contributors as $contributor) {
            foreach ($contributor($request) as $item) {
                $items[] = $item;
            }
        }

        return $items;
    }
}
