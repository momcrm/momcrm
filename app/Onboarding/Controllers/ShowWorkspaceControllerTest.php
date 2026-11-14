<?php

declare(strict_types=1);

use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('shows the current team so it can be named', function (): void {
    $user = UserEloquentModel::factory()->notOnboarded()->withPersonalTeam()->create(['name' => 'Jane Smith']);

    actingAs($user)
        ->get(route('onboarding.workspace'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('onboarding/workspace')
            ->where('team.id', $user->current_team_id)
            ->where('team.name', "Jane's Team"),
        );
});

it('returns not found when the user has no team', function (): void {
    actingAs(UserEloquentModel::factory()->notOnboarded()->create())
        ->get(route('onboarding.workspace'))
        ->assertNotFound();
});
