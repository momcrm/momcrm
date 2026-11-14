<?php

declare(strict_types=1);

use App\Contact\ContactEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertModelMissing;

it('deletes a contact', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $contact = ContactEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->delete(route('contacts.destroy', $contact->id))
        ->assertRedirectToRoute('customers.show', $contact->customer_id);

    assertModelMissing($contact);
});

it("returns not found for another team's contact", function (): void {
    $other = ContactEloquentModel::factory()->create();

    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->delete(route('contacts.destroy', $other->id))
        ->assertNotFound();
});
