<?php

declare(strict_types=1);

use App\BankRule\BankRuleEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('turns a match rule into a hide rule, dropping its customer', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);
    $rule = BankRuleEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'kind' => 'match', 'contains' => 'HARBOUR', 'customer_id' => $customer->id]);

    actingAs($owner)
        ->put(route('bank-rules.update', $rule->id), ['kind' => 'hide', 'contains' => 'Savings'])
        ->assertSessionHasNoErrors();

    expect($rule->fresh())->kind->toBe('hide')->contains->toBe('Savings')->customer_id->toBeNull();
});

it("returns not found for another team's rule", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->put(route('bank-rules.update', BankRuleEloquentModel::factory()->create()->id), ['kind' => 'hide', 'contains' => 'Savings'])
        ->assertNotFound();
});
