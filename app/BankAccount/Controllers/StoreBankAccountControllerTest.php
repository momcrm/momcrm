<?php

declare(strict_types=1);

use App\BankAccount\BankAccountEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('adds a bank account', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($owner)
        ->post(route('bank-accounts.store'), ['name' => 'CBA cheque', 'currency' => 'NZD'])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'CBA cheque added.']);

    expect(BankAccountEloquentModel::query()->sole())
        ->team_id->toBe($owner->current_team_id)
        ->name->toBe('CBA cheque')
        ->kind->toBe('statement')
        ->currency->toBe('NZD');
});

it('checks the fields', function (array $input, string $field): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->post(route('bank-accounts.store'), [...['name' => 'CBA', 'currency' => 'AUD'], ...$input])
        ->assertSessionHasErrors($field);
})->with([
    'no name' => [['name' => ''], 'name'],
    'a currency we do not bill in' => [['currency' => 'XYZ'], 'currency'],
    'a kind we do not read' => [['kind' => 'swift'], 'kind'],
]);

it('leaves bank accounts to owners and admins', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();

    actingAs($member)->post(route('bank-accounts.store'), ['name' => 'CBA', 'currency' => 'AUD'])->assertForbidden();
    actingAs($member)->get(route('bank-accounts.index'))->assertForbidden();
});
