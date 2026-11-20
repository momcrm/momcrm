<?php

declare(strict_types=1);

use App\BankAccount\BankAccountEloquentModel;
use App\BankTransaction\BankTransactionEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Payment\PaymentEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

it('lists pending lines by default, newest first, with their account', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $account = BankAccountEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'CBA cheque']);
    BankTransactionEloquentModel::factory()->forAccount($account)->create(['posted_on' => '2025-11-03', 'description' => 'Older']);
    BankTransactionEloquentModel::factory()->forAccount($account)->create(['posted_on' => '2025-11-04', 'description' => 'Newer', 'amount_in_cents' => 25000]);
    BankTransactionEloquentModel::factory()->forAccount($account)->hidden()->create(['description' => 'Transfer']);
    BankTransactionEloquentModel::factory()->forAccount(BankAccountEloquentModel::factory()->create())->create(['description' => 'Another team']);

    actingAs($user)
        ->get(route('bank-feeds.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('bank-feeds/index')
            ->has('transactions.data', 2)
            ->where('transactions.data.0.description', 'Newer')
            ->where('transactions.data.0.bank_account_name', 'CBA cheque')
            ->where('transactions.data.0.currency', 'AUD')
            ->where('transactions.data.0.amount_in_cents', 25000)
            ->where('transactions.data.0.status', 'pending')
            ->where('filters.status', 'pending')
            ->where('accounts.0.name', 'CBA cheque')
            ->where('can.import', true));
});

it('shows hidden lines, or all, and searches descriptions, customers and amounts', function (array $query, array $descriptions): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $account = BankAccountEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Harbour Dental']);
    BankTransactionEloquentModel::factory()->forAccount($account)->create(['posted_on' => '2025-11-05', 'description' => 'Pending one', 'customer_id' => $customer->id, 'amount_in_cents' => 125000]);
    BankTransactionEloquentModel::factory()->forAccount($account)->hidden()->create(['posted_on' => '2025-11-04', 'description' => 'Hidden one', 'amount_in_cents' => 999]);
    BankTransactionEloquentModel::factory()->forAccount($account)->create(['posted_on' => '2025-11-03', 'description' => 'Applied one', 'amount_in_cents' => 998, 'payment_id' => PaymentEloquentModel::factory()->create(['team_id' => $user->current_team_id])->id]);

    actingAs($user)
        ->get(route('bank-feeds.index', $query))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('transactions.data', fn ($rows): bool => collect($rows)->pluck('description')->all() === $descriptions));
})->with([
    'hidden' => [['status' => 'hidden'], ['Hidden one']],
    'applied' => [['status' => 'applied'], ['Applied one']],
    'all' => [['status' => 'all'], ['Pending one', 'Hidden one', 'Applied one']],
    'by customer' => [['status' => 'all', 'search' => 'harbour'], ['Pending one']],
    'by amount' => [['status' => 'all', 'search' => '$1,250'], ['Pending one']],
    'by date' => [['status' => 'all', 'from' => '2025-11-04', 'to' => '2025-11-04'], ['Hidden one']],
]);

it('sends the customers to pick from, with their currency, only when asked', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Harbour Dental', 'currency' => 'NZD']);

    actingAs($user)
        ->get(route('bank-feeds.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->missing('customers')
            ->reloadOnly('customers', fn (AssertableInertia $reload) => $reload
                ->where('customers', fn ($customers): bool => collect($customers)->contains(fn (array $customer): bool => $customer['name'] === 'Harbour Dental' && $customer['currency'] === 'NZD'))));
});
