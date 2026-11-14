<?php

declare(strict_types=1);

use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('saves the answers and moves on to the workspace step', function (): void {
    $user = UserEloquentModel::factory()->notOnboarded()->withPersonalTeam()->create();

    $response = actingAs($user)->put(route('onboarding.about.update'), [
        'onboarding_use_case' => 'managed_services',
        'onboarding_role' => 'technician',
        'onboarding_team_size' => '2-10',
    ]);

    $response->assertRedirectToRoute('onboarding.workspace');
    expect($user->fresh())
        ->onboarding_use_case->toBe('managed_services')
        ->onboarding_role->toBe('technician')
        ->onboarding_team_size->toBe('2-10');
});

it('leaves skipped questions unanswered', function (): void {
    $user = UserEloquentModel::factory()->notOnboarded()->withPersonalTeam()->create([
        'onboarding_role' => 'manager',
    ]);

    actingAs($user)->put(route('onboarding.about.update'), [
        'onboarding_use_case' => 'agency',
        'onboarding_role' => null,
        'onboarding_team_size' => null,
    ])->assertRedirectToRoute('onboarding.workspace');

    expect($user->fresh())
        ->onboarding_use_case->toBe('agency')
        ->onboarding_role->toBeNull()
        ->onboarding_team_size->toBeNull();
});

it('rejects an answer that is not one of the options', function (): void {
    $user = UserEloquentModel::factory()->notOnboarded()->withPersonalTeam()->create();

    actingAs($user)
        ->put(route('onboarding.about.update'), ['onboarding_use_case' => 'astronaut'])
        ->assertSessionHasErrors(['onboarding_use_case' => 'The selected onboarding use case is invalid.']);

    expect($user->fresh()->onboarding_use_case)->toBeNull();
});
