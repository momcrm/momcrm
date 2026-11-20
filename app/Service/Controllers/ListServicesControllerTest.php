<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\Location\LocationEloquentModel;
use App\Rate\RateEloquentModel;
use App\Service\ServiceEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it("lists the team's services across customers, with their customer, location and price, leaving archived ones out", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $user->current_team_id];
    $customer = CustomerEloquentModel::factory()->create([...$team, 'name' => 'Acme']);
    $location = LocationEloquentModel::factory()->create([...$team, 'customer_id' => $customer->id, 'name' => 'Head office']);
    $rate = RateEloquentModel::factory()->priced(['AUD' => 1000])->create([...$team]);
    ServiceEloquentModel::factory()->create([...$team, 'customer_id' => $customer->id, 'location_id' => $location->id, 'rate_id' => $rate->id, 'name' => 'Backup', 'quantity' => 3]);
    ServiceEloquentModel::factory()->inactive()->create([...$team, 'name' => 'Antivirus']);
    ServiceEloquentModel::factory()->archived()->create([...$team, 'name' => 'Old']);
    ServiceEloquentModel::factory()->create(['name' => "Another team's"]);

    actingAs($user)
        ->get(route('services.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('services/index')
            ->has('services.data', 2)
            ->where('services.data.0.name', 'Antivirus')
            ->where('services.data.0.lifecycle', 'inactive')
            ->where('services.data.1.name', 'Backup')
            ->where('services.data.1.customer_name', 'Acme')
            ->where('services.data.1.location_name', 'Head office')
            ->where('services.data.1.rental_in_cents', 3000)
            ->where('filters.sort', 'name'),
        );
});

it('filters by status, rate, customer, auto-renew and search', function (array $query, array $names): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $user->current_team_id];
    $acme = CustomerEloquentModel::factory()->create([...$team, 'name' => 'Acme']);
    $rate = RateEloquentModel::factory()->create([...$team, 'name' => 'Premium']);
    ServiceEloquentModel::factory()->create([...$team, 'customer_id' => $acme->id, 'name' => 'Backup', 'short_name' => 'backup-acme']);
    ServiceEloquentModel::factory()->create([...$team, 'rate_id' => $rate->id, 'name' => 'Hosting', 'auto_renew' => true]);
    ServiceEloquentModel::factory()->inactive()->create([...$team, 'name' => 'Antivirus']);
    ServiceEloquentModel::factory()->archived()->create([...$team, 'name' => 'Old']);

    $query = array_map(fn (mixed $value): mixed => match ($value) {
        'RATE' => $rate->id,
        'ACME' => $acme->id,
        default => $value,
    }, $query);

    actingAs($user)
        ->get(route('services.index', $query))
        ->assertInertia(fn (Assert $page) => $page->where('services.data', fn ($rows): bool => collect($rows)->pluck('name')->all() === $names));
})->with([
    'active' => [['status' => 'active'], ['Backup', 'Hosting']],
    'inactive' => [['status' => 'inactive'], ['Antivirus']],
    'archived' => [['status' => 'archived'], ['Old']],
    'a rate' => [['rate' => 'RATE'], ['Hosting']],
    'a customer' => [['customer' => 'ACME'], ['Backup']],
    'renewing' => [['auto_renew' => '1'], ['Hosting']],
    'a short name' => [['search' => 'backup-a'], ['Backup']],
    'a customer name' => [['search' => 'acm'], ['Backup']],
]);

it('sorts by customer, newest change first, or the other way', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $user->current_team_id];
    ServiceEloquentModel::factory()->create([...$team, 'name' => 'A', 'customer_id' => CustomerEloquentModel::factory()->create([...$team, 'name' => 'Zed'])->id]);
    ServiceEloquentModel::factory()->create([...$team, 'name' => 'B', 'customer_id' => CustomerEloquentModel::factory()->create([...$team, 'name' => 'Amy'])->id]);

    actingAs($user)
        ->get(route('services.index', ['sort' => 'customer', 'direction' => 'desc']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('services.data.0.name', 'A')
            ->where('filters.direction', 'desc'));
});
