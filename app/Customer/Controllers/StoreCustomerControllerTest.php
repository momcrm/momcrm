<?php

declare(strict_types=1);

use App\Choice\ChoiceEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\CustomField\CustomFieldEloquentModel;
use App\CustomField\CustomFieldTargetEnum;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('adds a customer to the current team and opens it', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();

    $response = actingAs($user)->post(route('customers.store'), [
        'name' => 'Acme IT',
        'email' => 'hello@acme.test',
        'phone' => '08 9000 0000',
        'website' => 'https://acme.test',
        'tax_id_label' => 'VAT',
        'tax_id' => 'GB 123 4567 89',
        'status' => 'active',
        'notes' => 'Prefers email.',
    ]);

    $customer = CustomerEloquentModel::query()->sole();
    $response->assertRedirectToRoute('customers.show', $customer->id);
    expect($customer)
        ->team_id->toBe($user->current_team_id)
        ->name->toBe('Acme IT')
        ->email->toBe('hello@acme.test')
        ->tax_id_label->toBe('VAT')
        ->tax_id->toBe('GB 123 4567 89')
        ->number->toBe(1)
        ->is_sample->toBeFalse();
});

it('asks what a tax ID is called', function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->post(route('customers.store'), ['name' => 'Acme IT', 'status' => 'active', 'tax_id' => '12 345 678 901'])
        ->assertSessionHasErrors(['tax_id_label' => 'Say what their tax ID is called, e.g. ABN or VAT.']);

    expect(CustomerEloquentModel::query()->count())->toBe(0);
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

it('stores the type, billing type and prefix in capitals', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $type = ChoiceEloquentModel::factory()->customerType()->create(['team_id' => $user->current_team_id]);

    actingAs($user)->post(route('customers.store'), [
        'name' => 'Acme IT',
        'status' => 'active',
        'type_id' => $type->id,
        'billing_type' => 'automatic',
        'prefix' => ' acme ',
    ])->assertSessionHasNoErrors();

    expect(CustomerEloquentModel::query()->sole())
        ->type_id->toBe($type->id)
        ->billing_type->toBe('automatic')
        ->prefix->toBe('ACME');
});

it('bills new customers manually unless told otherwise', function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->post(route('customers.store'), ['name' => 'Acme IT', 'status' => 'active']);

    expect(CustomerEloquentModel::query()->sole()->billing_type)->toBe('manual');
});

it('keeps prefixes unique within the team, whatever their case', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'prefix' => 'ACME']);
    CustomerEloquentModel::factory()->create(['prefix' => 'ZETA']);

    actingAs($user)
        ->post(route('customers.store'), ['name' => 'Copy', 'status' => 'active', 'prefix' => 'acme'])
        ->assertSessionHasErrors(['prefix' => 'Another customer already uses this prefix.']);

    actingAs($user)
        ->post(route('customers.store'), ['name' => 'Zeta', 'status' => 'active', 'prefix' => 'zeta'])
        ->assertSessionHasNoErrors();
});

it('rejects a prefix that is not just letters', function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->post(route('customers.store'), ['name' => 'Acme', 'status' => 'active', 'prefix' => 'AC-1'])
        ->assertSessionHasErrors(['prefix' => 'Use letters only.']);
});

it("rejects another team's customer type and a ticket type", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $otherTeams = ChoiceEloquentModel::factory()->customerType()->create();
    $ticketType = ChoiceEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    foreach ([$otherTeams, $ticketType] as $choice) {
        actingAs($user)
            ->post(route('customers.store'), ['name' => 'Acme', 'status' => 'active', 'type_id' => $choice->id])
            ->assertSessionHasErrors(['type_id' => "Choose one of your team's customer types."]);
    }

    expect(CustomerEloquentModel::query()->exists())->toBeFalse();
});

it('adds a customer with its custom field values', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $teamId = $user->current_team_id;
    $field = CustomFieldEloquentModel::factory()->on(CustomFieldTargetEnum::Customer)->create(['team_id' => $teamId]);
    $blank = CustomFieldEloquentModel::factory()->on(CustomFieldTargetEnum::Customer)->create(['team_id' => $teamId]);

    actingAs($user)
        ->post(route('customers.store'), ['name' => 'Acme IT', 'status' => 'active', 'custom_fields' => [$field->id => 'North', $blank->id => '']])
        ->assertSessionHasNoErrors();

    expect(CustomerEloquentModel::query()->where('name', 'Acme IT')->sole()->custom_fields)->toBe([$field->id => 'North']);
});
