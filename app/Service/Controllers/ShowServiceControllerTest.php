<?php

declare(strict_types=1);

use App\Contact\ContactEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Location\LocationEloquentModel;
use App\Rate\RateEloquentModel;
use App\Service\ServiceEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it("shows a service's overview with its billing, customer, location and contact", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $user->current_team_id];
    $customer = CustomerEloquentModel::factory()->create([...$team, 'name' => 'Acme']);
    $location = LocationEloquentModel::factory()->create([...$team, 'customer_id' => $customer->id, 'name' => 'Head office']);
    $contact = ContactEloquentModel::factory()->create([...$team, 'customer_id' => $customer->id, 'first_name' => 'Pat', 'last_name' => 'Lee']);
    $rate = RateEloquentModel::factory()->priced(['AUD' => 9500])->create([...$team, 'name' => 'Support']);
    $service = ServiceEloquentModel::factory()->create([...$team, 'customer_id' => $customer->id, 'rate_id' => $rate->id, 'location_id' => $location->id, 'contact_id' => $contact->id, 'quantity' => 4, 'billing_starts_on' => '2026-12-01']);

    actingAs($user)
        ->get(route('services.show', $service->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('services/workspace/overview')
            ->where('workspace.service.rental_in_cents', 38000)
            ->where('workspace.service.next_invoice', ['starts_on' => '2026-12-01', 'ends_on' => '2026-12-31', 'unit_price_in_cents' => 9500, 'total_in_cents' => 38000])
            ->where('workspace.customer', ['id' => $customer->id, 'name' => 'Acme'])
            ->where('workspace.location', ['id' => $location->id, 'name' => 'Head office'])
            ->where('workspace.contact', ['id' => $contact->id, 'name' => 'Pat Lee'])
            ->where('workspace.counts', ['linked' => 0, 'invoices' => 0])
            ->has('dates.created_at'),
        );
});

it("returns not found for another team's service", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->get(route('services.show', ServiceEloquentModel::factory()->create()->id))
        ->assertNotFound();
});
