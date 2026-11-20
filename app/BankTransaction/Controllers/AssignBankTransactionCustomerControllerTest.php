<?php

declare(strict_types=1);

use App\BankAccount\BankAccountEloquentModel;
use App\BankTransaction\BankTransactionEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Payment\PaymentEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('picks the customer a line is from, and clears it', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $line = BankTransactionEloquentModel::factory()->forAccount(BankAccountEloquentModel::factory()->create(['team_id' => $user->current_team_id]))->create();

    actingAs($user)
        ->patch(route('bank-feeds.customer', $line->id), ['customer_id' => $customer->id])
        ->assertSessionHasNoErrors();

    expect($line->fresh())->customer_id->toBe($customer->id)->matched_by->toBe('manual');

    actingAs($user)
        ->patch(route('bank-feeds.customer', $line->id), ['customer_id' => null])
        ->assertSessionHasNoErrors();

    expect($line->fresh())->customer_id->toBeNull()->matched_by->toBeNull();
});

it('refuses a customer billed in another currency, another team\'s customer and an applied line', function (array $customer, array $line): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $payer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id, ...$customer]);
    $bankLine = BankTransactionEloquentModel::factory()->forAccount(BankAccountEloquentModel::factory()->create(['team_id' => $user->current_team_id]))->create($line);

    actingAs($user)
        ->patch(route('bank-feeds.customer', $bankLine->id), ['customer_id' => $payer->id])
        ->assertSessionHasErrors('customer_id');

    expect($bankLine->fresh()->customer_id)->toBeNull();
})->with([
    'another currency' => [['currency' => 'USD'], []],
    'another team' => [['team_id' => fn () => UserEloquentModel::factory()->withPersonalTeam()->create()->current_team_id], []],
    'applied' => [[], ['payment_id' => fn () => PaymentEloquentModel::factory()->create()->id]],
]);
