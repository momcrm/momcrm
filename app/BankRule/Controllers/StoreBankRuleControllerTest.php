<?php

declare(strict_types=1);

use App\BankRule\BankRuleEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('adds a match rule and a hide rule', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);

    actingAs($owner)->post(route('bank-rules.store'), ['kind' => 'match', 'contains' => 'HARBOUR DENT', 'customer_id' => $customer->id])->assertSessionHasNoErrors();
    actingAs($owner)->post(route('bank-rules.store'), ['kind' => 'hide', 'contains' => 'Transfer to savings'])->assertSessionHasNoErrors();

    expect(BankRuleEloquentModel::query()->orderBy('kind')->get(['team_id', 'kind', 'contains', 'customer_id'])->toArray())->toBe([
        ['team_id' => $owner->current_team_id, 'kind' => 'hide', 'contains' => 'Transfer to savings', 'customer_id' => null],
        ['team_id' => $owner->current_team_id, 'kind' => 'match', 'contains' => 'HARBOUR DENT', 'customer_id' => $customer->id],
    ]);
});

it('checks the fields', function (array $input, string $field): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);

    actingAs($owner)
        ->post(route('bank-rules.store'), [...['kind' => 'match', 'contains' => 'HARBOUR', 'customer_id' => $customer->id], ...$input])
        ->assertSessionHasErrors($field);
})->with([
    'a match with no customer' => [['customer_id' => null], 'customer_id'],
    'a hide naming a customer' => [['kind' => 'hide'], 'customer_id'],
    'text too short to mean anything' => [['contains' => 'ab'], 'contains'],
    'an unknown kind' => [['kind' => 'ignore'], 'kind'],
]);

it("refuses another team's customer", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->post(route('bank-rules.store'), ['kind' => 'match', 'contains' => 'HARBOUR', 'customer_id' => CustomerEloquentModel::factory()->create()->id])
        ->assertSessionHasErrors('customer_id');
});

it('leaves rules to owners and admins', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();

    actingAs($member)->post(route('bank-rules.store'), ['kind' => 'hide', 'contains' => 'Transfer'])->assertForbidden();
});
