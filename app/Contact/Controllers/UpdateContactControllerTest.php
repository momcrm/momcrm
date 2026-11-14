<?php

declare(strict_types=1);

use App\Contact\ContactEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('saves changes to a contact', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $contact = ContactEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'is_primary' => true]);

    actingAs($user)
        ->put(route('contacts.update', $contact->id), ['customer_id' => $customer->id, 'first_name' => 'Renamed'])
        ->assertRedirectToRoute('customers.show', $customer->id);

    expect($contact->fresh())
        ->first_name->toBe('Renamed')
        ->is_primary->toBeFalse();
});

it("returns not found for another team's contact", function (): void {
    $other = ContactEloquentModel::factory()->create(['first_name' => 'Theirs']);

    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->put(route('contacts.update', $other->id), ['customer_id' => $other->customer_id, 'first_name' => 'Mine'])
        ->assertNotFound();

    expect($other->fresh()->first_name)->toBe('Theirs');
});
