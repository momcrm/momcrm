<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

it("returns the team's customers for the new-contact dialog", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Acme IT']);
    CustomerEloquentModel::factory()->create();

    actingAs($user)
        ->getJson(route('contacts.form-options'))
        ->assertOk()
        ->assertJsonPath('customers', [['id' => $customer->id, 'name' => 'Acme IT']]);
});

it('needs a signed-in person', function (): void {
    getJson(route('contacts.form-options'))->assertUnauthorized();
});
