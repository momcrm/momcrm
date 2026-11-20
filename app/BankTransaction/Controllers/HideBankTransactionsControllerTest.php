<?php

declare(strict_types=1);

use App\BankAccount\BankAccountEloquentModel;
use App\BankTransaction\BankTransactionEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('hides lines and brings them back to pending', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $account = BankAccountEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $lines = BankTransactionEloquentModel::factory()->forAccount($account)->count(2)->create();
    $ids = $lines->pluck('id')->all();

    actingAs($user)
        ->patch(route('bank-feeds.hide'), ['bank_transaction_ids' => $ids, 'hidden' => true])
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => '2 lines hidden.']);

    expect(BankTransactionEloquentModel::query()->whereNotNull('hidden_at')->count())->toBe(2);

    actingAs($user)
        ->patch(route('bank-feeds.hide'), ['bank_transaction_ids' => [$ids[0]], 'hidden' => false])
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => '1 line back in pending.']);

    expect($lines[0]->fresh()->hidden_at)->toBeNull()
        ->and($lines[1]->fresh()->hidden_at)->not->toBeNull();
});

it("changes nothing when one of the lines is another team's", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $mine = BankTransactionEloquentModel::factory()->forAccount(BankAccountEloquentModel::factory()->create(['team_id' => $user->current_team_id]))->create();
    $theirs = BankTransactionEloquentModel::factory()->forAccount(BankAccountEloquentModel::factory()->create())->create();

    actingAs($user)
        ->patch(route('bank-feeds.hide'), ['bank_transaction_ids' => [$mine->id, $theirs->id], 'hidden' => true])
        ->assertNotFound();

    expect($mine->fresh()->hidden_at)->toBeNull();
});
