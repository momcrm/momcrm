<?php

declare(strict_types=1);

use App\Shared\SettingsNavigation;
use App\User\UserEloquentModel;
use Illuminate\Http\Request;

use function Pest\Laravel\actingAs;

// Start from an empty menu, whatever packages this installation has: one that
// adds its own pages would otherwise show up beside the ones added here.
beforeEach(fn () => app()->instance(SettingsNavigation::class, new SettingsNavigation));

it('shares the settings pages a package adds with every page', function (): void {
    resolve(SettingsNavigation::class)->add(fn (Request $request): array => [
        ['section' => 'Workspace', 'title' => 'Subscription', 'href' => '/settings/subscription'],
    ]);

    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->get(route('team.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('settingsNavigation', [
            ['section' => 'Workspace', 'title' => 'Subscription', 'href' => '/settings/subscription'],
        ]));
});

it('lets a package decide per request whether to show its page', function (): void {
    resolve(SettingsNavigation::class)->add(fn (Request $request): array => $request->user()?->is_platform_admin === true
        ? [['section' => 'Installation', 'title' => 'Teams', 'href' => '/settings/teams']]
        : []);

    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->get(route('team.edit'))
        ->assertInertia(fn ($page) => $page->where('settingsNavigation', []));

    actingAs(UserEloquentModel::factory()->withPersonalTeam()->platformAdmin()->create())
        ->get(route('team.edit'))
        ->assertInertia(fn ($page) => $page->where('settingsNavigation', [
            ['section' => 'Installation', 'title' => 'Teams', 'href' => '/settings/teams'],
        ]));
});
