<?php

declare(strict_types=1);

use App\BankAccount\BankAccountEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

it("lists the team's bank accounts by name", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    BankAccountEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'name' => 'Westpac']);
    BankAccountEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'name' => 'CBA', 'currency' => 'NZD']);
    BankAccountEloquentModel::factory()->create(['name' => 'Another team']);

    actingAs($owner)
        ->get(route('bank-accounts.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/bank-feeds')
            ->has('accounts', 2)
            ->where('accounts.0.name', 'CBA')
            ->where('accounts.0.currency', 'NZD')
            ->where('accounts.1.name', 'Westpac')
            ->has('currencies')
            ->has('kinds', 1));
});
