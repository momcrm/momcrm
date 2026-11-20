<?php

declare(strict_types=1);

use App\BankAccount\BankAccountEloquentModel;
use App\BankTransaction\BankTransactionEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertModelMissing;

it('deletes lines', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $line = BankTransactionEloquentModel::factory()->forAccount(BankAccountEloquentModel::factory()->create(['team_id' => $owner->current_team_id]))->create();

    actingAs($owner)
        ->delete(route('bank-feeds.destroy'), ['bank_transaction_ids' => [$line->id]])
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => '1 line deleted.']);

    assertModelMissing($line);
});

it('leaves deleting lines to owners and admins by default', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();
    $line = BankTransactionEloquentModel::factory()->forAccount(BankAccountEloquentModel::factory()->create(['team_id' => $owner->current_team_id]))->create();

    actingAs($member)
        ->delete(route('bank-feeds.destroy'), ['bank_transaction_ids' => [$line->id]])
        ->assertForbidden();

    expect($line->fresh())->not->toBeNull();
});
