<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it("shows the customer's statement as a PDF", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Acme IT']);

    $response = actingAs($user)->get(route('customers.statement.pdf', [$customer->id, 'from' => '2026-10-01', 'to' => '2026-10-31']));

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('Content-Disposition', 'inline; filename="Statement Acme IT 2026-10-31.pdf"');
    expect($response->getContent())->toStartWith('%PDF');
});

it("returns not found for another team's customer", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->get(route('customers.statement.pdf', CustomerEloquentModel::factory()->create()->id))
        ->assertNotFound();
});
