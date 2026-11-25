<?php

declare(strict_types=1);

use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('shows mentions, assignments and messages as emailed until the person chooses', function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->get(route('notification-settings.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/notifications')
            ->where('emailed', ['mention', 'assignment', 'message']),
        );
});

it('shows what the person chose, even when it is nothing', function (array $chosen, array $shown): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['emailed_notifications' => $chosen]);

    actingAs($user)
        ->get(route('notification-settings.edit'))
        ->assertInertia(fn (Assert $page) => $page->where('emailed', $shown));
})->with([
    'some kinds' => [['update', 'comment'], ['comment', 'update']],
    'nothing' => [[], []],
]);

it('asks a guest to sign in', function (): void {
    get(route('notification-settings.edit'))->assertRedirectToRoute('login');
});
