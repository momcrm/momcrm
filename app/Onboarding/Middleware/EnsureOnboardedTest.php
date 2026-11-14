<?php

declare(strict_types=1);

use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('sends users who have not finished onboarding to its first step', function (): void {
    actingAs(UserEloquentModel::factory()->notOnboarded()->withPersonalTeam()->create())
        ->get(route('dashboard'))
        ->assertRedirectToRoute('onboarding.about');
});

it('lets onboarded users through', function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->get(route('dashboard'))
        ->assertOk();
});
