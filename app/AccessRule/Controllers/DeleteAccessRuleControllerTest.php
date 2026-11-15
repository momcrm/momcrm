<?php

declare(strict_types=1);

use App\AccessRule\AccessRuleEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('deletes a rule', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $rule = AccessRuleEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'name' => 'Spare']);

    actingAs($owner)
        ->delete(route('access-rules.destroy', $rule->id))
        ->assertRedirectToRoute('access-rules.index')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Spare deleted.']);

    expect($rule->fresh())->toBeNull();
});

it('forbids members and hides other teams\' rules', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();
    $rule = AccessRuleEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);

    actingAs($member)->delete(route('access-rules.destroy', $rule->id))->assertForbidden();
    actingAs($owner)->delete(route('access-rules.destroy', AccessRuleEloquentModel::factory()->create()->id))->assertNotFound();

    expect($rule->fresh())->not->toBeNull();
});
