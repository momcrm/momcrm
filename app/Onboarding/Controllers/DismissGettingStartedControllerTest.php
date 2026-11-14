<?php

declare(strict_types=1);

use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('hides the getting-started checklist for good', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($user)
        ->from(route('dashboard'))
        ->post(route('getting-started.dismiss'))
        ->assertRedirect(route('dashboard'));

    expect($user->fresh()->getting_started_dismissed_at)->not->toBeNull();
    actingAs($user->fresh())->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page->where('gettingStarted', null));
});
