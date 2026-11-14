<?php

declare(strict_types=1);

use App\Contact\ContactEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('shows the edit form for a contact', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $contact = ContactEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'first_name' => 'Sam']);

    actingAs($user)
        ->get(route('contacts.edit', $contact->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('contacts/edit')->where('contact.first_name', 'Sam'));
});

it("returns not found for another team's contact", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->get(route('contacts.edit', ContactEloquentModel::factory()->create()->id))
        ->assertNotFound();
});
