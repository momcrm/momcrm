<?php

declare(strict_types=1);

use App\Charge\ChargeEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Invoice\InvoiceEloquentModel;
use App\InvoiceItem\InvoiceItemEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertModelMissing;

it('deletes a charge waiting for an invoice', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $charge = ChargeEloquentModel::factory()->forCustomer(CustomerEloquentModel::factory()->create(['team_id' => $owner->current_team_id]))->create();

    actingAs($owner)->delete(route('charges.destroy', $charge->id))->assertSessionHasNoErrors();

    assertModelMissing($charge);
});

it('keeps a billed charge, and leaves deleting to owners and admins by default', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);
    $billed = ChargeEloquentModel::factory()->forCustomer($customer)->create();
    $invoice = InvoiceEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'customer_id' => $customer->id]);
    InvoiceItemEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'invoice_id' => $invoice->id, 'charge_id' => $billed->id]);
    $waiting = ChargeEloquentModel::factory()->forCustomer($customer)->create();

    actingAs($owner)->delete(route('charges.destroy', $billed->id))->assertSessionHasErrors('charge');
    actingAs($member)->delete(route('charges.destroy', $waiting->id))->assertForbidden();

    expect($billed->fresh())->not->toBeNull()
        ->and($waiting->fresh())->not->toBeNull();
});
