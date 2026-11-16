<?php

declare(strict_types=1);

use App\Contract\ContractEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('lets members edit a contract under the default rules', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();
    $contract = ContractEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'status' => 'pending']);

    actingAs($member)
        ->put(route('contracts.update', $contract->id), ['title' => 'Support SLA', 'type' => 'sla', 'status' => 'active', 'ends_on' => null, 'starts_on' => null])
        ->assertSessionHasNoErrors();

    expect($contract->refresh())
        ->title->toBe('Support SLA')
        ->type->toBe('sla')
        ->status->toBe('active')
        ->ends_on->toBeNull();
});

it("returns not found for another team's contract", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->put(route('contracts.update', ContractEloquentModel::factory()->create()->id), ['title' => 'Mine', 'type' => 'nda', 'status' => 'draft'])
        ->assertNotFound();
});
