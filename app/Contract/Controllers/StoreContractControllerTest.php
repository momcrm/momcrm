<?php

declare(strict_types=1);

use App\Contract\ContractEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\CustomField\CustomFieldEloquentModel;
use App\CustomField\CustomFieldTargetEnum;
use App\CustomField\CustomFieldTypeEnum;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('adds a contract to a customer', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->post(route('contracts.store', $customer->id), [
            'title' => 'Managed services',
            'type' => 'msa',
            'status' => 'active',
            'signed_on' => '2026-01-10',
            'starts_on' => '2026-02-01',
            'ends_on' => '2027-01-31',
            'purchase_order' => 'PO-1234',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect(ContractEloquentModel::query()->sole())
        ->team_id->toBe($user->current_team_id)
        ->customer_id->toBe($customer->id)
        ->type->toBe('msa')
        ->status->toBe('active')
        ->ends_on->toBe('2027-01-31')
        ->purchase_order->toBe('PO-1234');
});

it('needs a title, type and status, and an end on or after the start', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->post(route('contracts.store', $customer->id), ['type' => 'lease', 'starts_on' => '2026-02-01', 'ends_on' => '2026-01-01'])
        ->assertSessionHasErrors(['title', 'type', 'status', 'ends_on' => "The end date can't be before the start date."]);

    expect(ContractEloquentModel::query()->exists())->toBeFalse();
});

it("returns not found for another team's customer", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->post(route('contracts.store', CustomerEloquentModel::factory()->create()->id), ['title' => 'NDA', 'type' => 'nda', 'status' => 'draft'])
        ->assertNotFound();
});

it('adds a contract with its custom field values, rejecting one of the wrong type', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $user->current_team_id];
    $customer = CustomerEloquentModel::factory()->create($team);
    $text = CustomFieldEloquentModel::factory()->on(CustomFieldTargetEnum::Contract)->create($team);
    $number = CustomFieldEloquentModel::factory()->on(CustomFieldTargetEnum::Contract)->ofType(CustomFieldTypeEnum::Number)->create([...$team, 'name' => 'Seats']);
    $contract = ['title' => 'Managed services', 'type' => 'msa', 'status' => 'active'];

    actingAs($user)
        ->post(route('contracts.store', $customer->id), [...$contract, 'custom_fields' => [$number->id => 'many']])
        ->assertSessionHasErrors(["custom_fields.{$number->id}" => 'The Seats field must be a number.']);

    actingAs($user)
        ->post(route('contracts.store', $customer->id), [...$contract, 'custom_fields' => [$text->id => 'North', $number->id => '25']])
        ->assertSessionHasNoErrors();

    expect(ContractEloquentModel::query()->withoutGlobalScopes()->sole()->custom_fields)->toEqual([$text->id => 'North', $number->id => 25]);
});
