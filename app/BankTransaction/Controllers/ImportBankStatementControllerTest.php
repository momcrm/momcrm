<?php

declare(strict_types=1);

use App\AccessRule\AccessRuleEloquentModel;
use App\BankAccount\BankAccountEloquentModel;
use App\BankTransaction\BankTransactionEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;
use Illuminate\Http\UploadedFile;

use function Pest\Laravel\actingAs;

function bank_statement(string $csv, string $name = 'statement.csv'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, $csv);
}

it('imports the money that came in, skips money out, and skips what is already in when imported again', function (): void {
    $member = UserEloquentModel::factory()->withPersonalTeam()->create();
    $account = BankAccountEloquentModel::factory()->create(['team_id' => $member->current_team_id]);
    $csv = "Date,Description,Amount\n13/11/2025,Direct Credit 062000 HARBOUR DENTAL,250.00\n14/11/2025,Card purchase,-20.00\n";

    actingAs($member)
        ->post(route('bank-feeds.import'), ['bank_account_id' => $account->id, 'files' => [bank_statement($csv)]])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => '1 new deposit imported. 1 payment out skipped.']);

    expect(BankTransactionEloquentModel::query()->sole())
        ->team_id->toBe($member->current_team_id)
        ->bank_account_id->toBe($account->id)
        ->amount_in_cents->toBe(25000)
        ->description->toBe('Direct Credit 062000 HARBOUR DENTAL')
        ->and($account->fresh()->last_imported_at)->not->toBeNull();

    actingAs($member)
        ->post(route('bank-feeds.import'), ['bank_account_id' => $account->id, 'files' => [bank_statement($csv."15/11/2025,Deposit,40.00\n")]])
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => '1 new deposit imported. 1 was already in; 1 payment out skipped.']);

    expect(BankTransactionEloquentModel::query()->count())->toBe(2);
});

it('asks for the columns of a file it cannot read alone, and keeps them for the next file', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $account = BankAccountEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);
    $csv = "13/11/2025,\"+250.00\",\"Direct Credit HARBOUR DENTAL\",\"+1250.00\"\n";

    actingAs($owner)
        ->post(route('bank-feeds.import'), ['bank_account_id' => $account->id, 'files' => [bank_statement($csv, 'cba.csv')]])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('statementColumns.file', 'cba.csv');

    expect(BankTransactionEloquentModel::query()->count())->toBe(0);

    $columns = ['has_header' => false, 'date' => 0, 'description' => [2], 'amount' => 1, 'credit' => null, 'debit' => null, 'date_order' => 'dmy'];

    actingAs($owner)
        ->post(route('bank-feeds.import'), ['bank_account_id' => $account->id, 'files' => [bank_statement($csv, 'cba.csv')], 'columns' => $columns])
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => '1 new deposit imported.']);

    expect($account->fresh()->csv_columns)->toMatchArray([...$columns, 'layout' => 'columns:4']);

    actingAs($owner)
        ->post(route('bank-feeds.import'), ['bank_account_id' => $account->id, 'files' => [bank_statement("14/11/2025,\"+40.00\",\"Deposit\",\"+1290.00\"\n")]])
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => '1 new deposit imported.']);
});

it('imports nothing when one of the files cannot be read', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $account = BankAccountEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);

    actingAs($owner)
        ->post(route('bank-feeds.import'), ['bank_account_id' => $account->id, 'files' => [
            bank_statement("Date,Description,Amount\n13/11/2025,Deposit,10.00\n"),
            bank_statement("Date,Description,Amount\n", 'empty.csv'),
        ]])
        ->assertSessionHasErrors(['files' => 'empty.csv: no transactions were found in it. Check it is a statement exported as CSV.']);

    expect(BankTransactionEloquentModel::query()->count())->toBe(0);
});

it("refuses another team's account and files that aren't statements", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $account = BankAccountEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);

    actingAs($owner)
        ->post(route('bank-feeds.import'), ['bank_account_id' => BankAccountEloquentModel::factory()->create()->id, 'files' => [bank_statement("Date,Description,Amount\n")]])
        ->assertSessionHasErrors('bank_account_id');

    actingAs($owner)
        ->post(route('bank-feeds.import'), ['bank_account_id' => $account->id, 'files' => [UploadedFile::fake()->create('statement.pdf', 10)]])
        ->assertSessionHasErrors('files.0');
});

it('lets members import by default, and refuses them when a rule says so', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();
    $account = BankAccountEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);

    actingAs($member)
        ->post(route('bank-feeds.import'), ['bank_account_id' => $account->id, 'files' => [bank_statement("Date,Description,Amount\n13/11/2025,Deposit,10.00\n")]])
        ->assertSessionHasNoErrors();

    expect(BankTransactionEloquentModel::query()->count())->toBe(1);

    AccessRuleEloquentModel::factory()->create([
        'team_id' => $owner->current_team_id,
        'name' => 'Only admins import bank feeds',
        'effect' => 'deny',
        'resources' => ['bank_transaction'],
        'actions' => ['import'],
        'conditions' => [['attribute' => 'subject.team_role', 'operator' => 'equals', 'value' => 'member']],
    ]);

    actingAs($member)
        ->post(route('bank-feeds.import'), ['bank_account_id' => $account->id, 'files' => [bank_statement("Date,Description,Amount\n14/11/2025,Deposit,10.00\n")]])
        ->assertForbidden();
});
