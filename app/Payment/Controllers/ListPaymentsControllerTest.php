<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\Payment\PaymentEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it("lists the team's payments, latest paid first", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Acme IT']);
    PaymentEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'number' => 1, 'paid_on' => '2026-10-01']);
    PaymentEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'number' => 2, 'paid_on' => '2026-11-01', 'amount_in_cents' => 5000]);
    PaymentEloquentModel::factory()->create();

    actingAs($user)
        ->get(route('payments.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('payments/index')
            ->where('payments.total', 2)
            ->where('payments.data.0.reference_number', 'PAY-0002')
            ->where('payments.data.0.customer_name', 'Acme IT')
            ->where('payments.data.0.amount_in_cents', 5000)
            ->where('payments.data.0.unallocated_in_cents', 5000)
            ->where('payments.data.1.reference_number', 'PAY-0001'),
        );
});

it('finds payments by reference, method and the dates they were paid', function (array $query, array $expected): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $payment = fn (array $attributes) => PaymentEloquentModel::factory()->create(['team_id' => $user->current_team_id, ...$attributes]);
    $payment(['number' => 1, 'paid_on' => '2026-10-15', 'method' => 'cash', 'reference' => null]);
    $payment(['number' => 2, 'paid_on' => '2026-11-05', 'method' => 'bank_transfer', 'reference' => 'EFT 7781']);
    $payment(['number' => 3, 'paid_on' => '2026-11-20', 'method' => 'cheque', 'reference' => null]);

    actingAs($user)
        ->get(route('payments.index', $query))
        ->assertInertia(fn (Assert $page) => $page
            ->where('payments.data', fn ($payments) => collect($payments)->pluck('number')->sort()->values()->all() === $expected));
})->with([
    'by reference' => [['search' => '7781'], [2]],
    'by number' => [['search' => 'PAY-0003'], [3]],
    'by method' => [['method' => 'cash'], [1]],
    'from a date' => [['from' => '2026-11-01'], [2, 3]],
    'up to a date' => [['to' => '2026-11-05'], [1, 2]],
]);

it('sorts by amount', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    PaymentEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'number' => 1, 'amount_in_cents' => 900]);
    PaymentEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'number' => 2, 'amount_in_cents' => 100]);

    actingAs($user)
        ->get(route('payments.index', ['sort' => 'amount_in_cents', 'direction' => 'asc']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('payments.data.0.number', 2)
            ->where('filters.sort', 'amount_in_cents')
            ->where('filters.direction', 'asc'));
});
