<?php

declare(strict_types=1);

use App\Team\TeamEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it("shows owners and admins the team's billing details", function (): void {
    $team = TeamEloquentModel::factory()->create(['name' => 'Cebugle', 'tax_id_label' => 'ABN', 'tax_id' => '12 345 678 901']);
    $admin = UserEloquentModel::factory()->inTeam($team->id, TeamRoleEnum::Admin)->create();

    actingAs($admin)
        ->get(route('billing.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/billing')
            ->where('teamName', 'Cebugle')
            ->where('billing.name', 'Cebugle')
            ->where('billing.tax_id_label', 'ABN')
            ->where('billing.tax_id', '12 345 678 901'),
        );
});

it('forbids members from seeing the billing details', function (): void {
    $team = TeamEloquentModel::factory()->create();

    actingAs(UserEloquentModel::factory()->inTeam($team->id)->create())
        ->get(route('billing.edit'))
        ->assertForbidden();
});
