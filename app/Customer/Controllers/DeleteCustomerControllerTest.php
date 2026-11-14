<?php

declare(strict_types=1);

use App\Contact\ContactEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertModelMissing;

it('deletes a customer and their contacts', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $contact = ContactEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id]);

    actingAs($user)
        ->delete(route('customers.destroy', $customer->id))
        ->assertRedirectToRoute('customers.index');

    assertModelMissing($customer);
    assertModelMissing($contact);
});

it("returns not found for another team's customer", function (): void {
    $other = CustomerEloquentModel::factory()->create();

    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->delete(route('customers.destroy', $other->id))
        ->assertNotFound();

    expect(CustomerEloquentModel::query()->withoutGlobalScopes()->whereKey($other->id)->exists())->toBeTrue();
});
