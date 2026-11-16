<?php

declare(strict_types=1);

use App\Choice\ChoiceEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('sets the type, billing type and status of the picked customers', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $type = ChoiceEloquentModel::factory()->customerType()->create(['team_id' => $user->current_team_id]);
    $picked = CustomerEloquentModel::factory()->count(2)->create(['team_id' => $user->current_team_id]);
    $left = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->patch(route('customers.bulk.update'), [
            'customer_ids' => $picked->pluck('id')->all(),
            'type_id' => $type->id,
            'billing_type' => 'excluded',
            'status' => 'inactive',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    foreach ($picked as $customer) {
        expect($customer->refresh())
            ->type_id->toBe($type->id)
            ->billing_type->toBe('excluded')
            ->status->toBe('inactive');
    }

    expect($left->refresh())->type_id->toBeNull()->billing_type->toBe('manual');
});

it('clears the type when asked to', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $type = ChoiceEloquentModel::factory()->customerType()->create(['team_id' => $user->current_team_id]);
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'type_id' => $type->id]);

    actingAs($user)
        ->patch(route('customers.bulk.update'), ['customer_ids' => [$customer->id], 'type_id' => null])
        ->assertSessionHasNoErrors();

    expect($customer->refresh()->type_id)->toBeNull();
});

it("changes nothing when one of the customers is another team's", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ours = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $theirs = CustomerEloquentModel::factory()->create();

    actingAs($user)
        ->patch(route('customers.bulk.update'), ['customer_ids' => [$ours->id, $theirs->id], 'status' => 'inactive'])
        ->assertNotFound();

    expect($ours->refresh()->status)->toBe('active')
        ->and($theirs->refresh()->status)->toBe('active');
});

it('asks what to change', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->patch(route('customers.bulk.update'), ['customer_ids' => [$customer->id]])
        ->assertSessionHasErrors(['customer_ids' => 'Choose what to change on the selected customers.']);
});
