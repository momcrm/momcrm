<?php

declare(strict_types=1);

use App\Customer\Customer;
use App\Customer\CustomerEloquentModel;
use App\Label\LabelCollection;
use App\Label\LabelEloquentModel;
use App\Ticket\Ticket;
use App\Ticket\TicketEloquentModel;

it('reads a loaded relation as its data object, and leaves an unloaded one empty', function (): void {
    $customer = CustomerEloquentModel::factory()->create(['name' => 'Acme IT']);
    $model = TicketEloquentModel::factory()->create(['team_id' => $customer->team_id, 'customer_id' => $customer->id]);

    $loaded = Ticket::fromEloquentModel($model->load('customer'));
    $unloaded = Ticket::fromEloquentModel($model->fresh());

    expect($loaded->getCustomer()->toData())->toBeInstanceOf(Customer::class)
        ->and($loaded->getCustomer()->toData()->getName()->toString())->toBe('Acme IT')
        ->and($unloaded->getCustomer()->isNull())->toBeTrue()
        ->and($unloaded->getCustomer()->toData()->isNull())->toBeTrue();
});

it('reads a loaded has-many relation as the domain collection', function (): void {
    $model = TicketEloquentModel::factory()->create();
    $model->labels()->attach(LabelEloquentModel::factory()->count(2)->create(['team_id' => $model->team_id])->modelKeys());

    $labels = Ticket::fromEloquentModel($model->load('labels'))->getLabels()->toDataCollection();

    expect($labels)->toBeInstanceOf(LabelCollection::class)->toHaveCount(2)
        ->and(Ticket::fromEloquentModel($model->fresh())->getLabels()->toDataCollection())->toBeInstanceOf(LabelCollection::class)->toBeEmpty();
});
