<?php

declare(strict_types=1);

use App\BankAccount\BankAccountEloquentModel;
use App\BankTransaction\BankTransactionEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertModelMissing;

it('deletes an account with the lines imported into it', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $account = BankAccountEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'name' => 'Old account']);
    $line = BankTransactionEloquentModel::factory()->forAccount($account)->create();

    actingAs($owner)
        ->delete(route('bank-accounts.destroy', $account->id))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Old account deleted.']);

    assertModelMissing($account);
    assertModelMissing($line);
});
