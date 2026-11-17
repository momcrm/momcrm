<?php

declare(strict_types=1);

use App\Contract\ContractEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\CustomField\CustomFieldEloquentModel;
use App\CustomField\CustomFieldTargetEnum;
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

it('changes a contract custom field value, keeping the ones not sent', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $user->current_team_id];
    $kept = CustomFieldEloquentModel::factory()->on(CustomFieldTargetEnum::Contract)->create($team);
    $set = CustomFieldEloquentModel::factory()->on(CustomFieldTargetEnum::Contract)->create($team);
    $contract = ContractEloquentModel::factory()->create([...$team, 'customer_id' => CustomerEloquentModel::factory()->create($team)->id, 'custom_fields' => [$kept->id => 'kept']]);

    Pest\Laravel\actingAs($user)
        ->put(route('contracts.update', $contract->id), ['title' => 'Managed services', 'type' => 'msa', 'status' => 'active', 'custom_fields' => [$set->id => 'new']])
        ->assertSessionHasNoErrors();

    expect($contract->fresh()->custom_fields)->toEqual([$kept->id => 'kept', $set->id => 'new']);
});
