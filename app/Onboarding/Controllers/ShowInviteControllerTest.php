<?php

declare(strict_types=1);

use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('shows the invite step', function (): void {
    actingAs(UserEloquentModel::factory()->notOnboarded()->withPersonalTeam()->create())
        ->get(route('onboarding.invite'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('onboarding/invite'));
});
