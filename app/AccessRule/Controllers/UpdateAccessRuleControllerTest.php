<?php

declare(strict_types=1);

use App\AccessRule\AccessRuleEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('edits a rule and can switch it off', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $rule = AccessRuleEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'name' => 'Old']);

    actingAs($owner)
        ->put(route('access-rules.update', $rule->id), [
            'name' => 'Members close urgent tickets',
            'effect' => 'deny',
            'is_enabled' => false,
            'resources' => ['ticket'],
            'actions' => ['close'],
            'conditions' => [['attribute' => 'ticket.priority', 'operator' => 'in', 'value' => ['urgent']]],
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirectToRoute('access-rules.index');

    expect($rule->fresh())
        ->name->toBe('Members close urgent tickets')
        ->effect->toBe('deny')
        ->is_enabled->toBeFalse()
        ->conditions->toBe([['attribute' => 'ticket.priority', 'operator' => 'in', 'value' => ['urgent']]]);
});

it("returns not found for another team's rule", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->put(route('access-rules.update', AccessRuleEloquentModel::factory()->create()->id), ['name' => 'X', 'effect' => 'allow', 'resources' => [], 'actions' => [], 'conditions' => []])
        ->assertNotFound();
});
