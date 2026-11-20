<?php

declare(strict_types=1);

use App\BankAccount\BankAccountEloquentModel;
use App\BankTransaction\BankTransactionEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('finds the customer of pending lines that have none, leaving the rest alone', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $account = BankAccountEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $chosen = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Coastal Logistics']);
    $unmatched = BankTransactionEloquentModel::factory()->forAccount($account)->create(['description' => 'Direct Credit 062000 HARBOUR DENTAL']);
    $stillUnknown = BankTransactionEloquentModel::factory()->forAccount($account)->create(['description' => 'Direct Credit 062000 SOMEONE ELSE']);
    $picked = BankTransactionEloquentModel::factory()->forAccount($account)->create(['description' => 'HARBOUR DENTAL', 'customer_id' => $chosen->id, 'matched_by' => 'manual']);
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Harbour Dental']);

    actingAs($user)
        ->post(route('bank-feeds.match'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Found the customer for 1 of 2 lines.']);

    expect($unmatched->fresh())->customer_id->toBe($customer->id)->matched_by->toBe('name')
        ->and($stillUnknown->fresh()->customer_id)->toBeNull()
        ->and($picked->fresh()->customer_id)->toBe($chosen->id);
});
