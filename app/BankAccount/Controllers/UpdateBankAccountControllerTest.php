<?php

declare(strict_types=1);

use App\BankAccount\BankAccountEloquentModel;
use App\BankTransaction\BankTransactionEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('renames an account and changes its currency while nothing is imported', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $account = BankAccountEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);

    actingAs($owner)
        ->put(route('bank-accounts.update', $account->id), ['name' => 'Westpac', 'currency' => 'USD'])
        ->assertSessionHasNoErrors();

    expect($account->fresh())->name->toBe('Westpac')->currency->toBe('USD');
});

it('keeps the currency once statements are imported', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $account = BankAccountEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);
    BankTransactionEloquentModel::factory()->forAccount($account)->create();

    actingAs($owner)
        ->put(route('bank-accounts.update', $account->id), ['name' => 'Westpac', 'currency' => 'USD'])
        ->assertSessionHasErrors('currency');

    expect($account->fresh()->currency)->toBe('AUD');
});

it("returns not found for another team's account", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->put(route('bank-accounts.update', BankAccountEloquentModel::factory()->create()->id), ['name' => 'Mine', 'currency' => 'AUD'])
        ->assertNotFound();
});
