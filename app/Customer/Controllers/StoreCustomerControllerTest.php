<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('adds a customer to the current team and opens it', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();

    $response = actingAs($user)->post(route('customers.store'), [
        'name' => 'Acme IT',
        'email' => 'hello@acme.test',
        'phone' => '08 9000 0000',
        'website' => 'https://acme.test',
        'abn' => '12 345 678 901',
        'status' => 'active',
        'notes' => 'Prefers email.',
    ]);

    $customer = CustomerEloquentModel::query()->sole();
    $response->assertRedirectToRoute('customers.show', $customer->id);
    expect($customer)
        ->team_id->toBe($user->current_team_id)
        ->name->toBe('Acme IT')
        ->email->toBe('hello@acme.test')
        ->number->toBe(1)
        ->is_sample->toBeFalse();
});

it('numbers customers from 1 within each team', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    CustomerEloquentModel::factory()->create(['number' => 7]);
    CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'number' => 1]);

    actingAs($user)->post(route('customers.store'), ['name' => 'Second', 'status' => 'active']);

    expect(CustomerEloquentModel::query()->where('name', 'Second')->sole()->number)->toBe(2);
});

it('requires a name and a status', function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->post(route('customers.store'), [])
        ->assertSessionHasErrors([
            'name' => 'The name field is required.',
            'status' => 'The status field is required.',
        ]);

    expect(CustomerEloquentModel::query()->exists())->toBeFalse();
});

it('rejects an invalid email and website', function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->post(route('customers.store'), ['name' => 'Acme', 'status' => 'active', 'email' => 'nope', 'website' => 'acme'])
        ->assertSessionHasErrors([
            'email' => 'The email field must be a valid email address.',
            'website' => 'The website field must be a valid URL.',
        ]);
});
