<?php

declare(strict_types=1);

use App\Contact\ContactEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Invoice\InvoiceEloquentModel;
use App\Onboarding\Actions\SeedSampleData;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;

it('adds sample customers with contacts, tickets and an invoice to the team', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();

    SeedSampleData::forTeam(teamId: $user->current_team_id, userId: $user->id);

    $customers = CustomerEloquentModel::query()->withoutGlobalScopes()->orderBy('number')->get();
    expect($customers->pluck('name')->all())->toBe(['Harbour Dental', 'Coastal Logistics', 'Bright Spark Electrical'])
        ->and($customers->every(fn (CustomerEloquentModel $customer): bool => $customer->is_sample && $customer->team_id === $user->current_team_id))->toBeTrue();
    expect(ContactEloquentModel::query()->withoutGlobalScopes()->count())->toBe(4);
    expect(TicketEloquentModel::query()->withoutGlobalScopes()->orderBy('number')->pluck('status')->all())
        ->toBe(['in_progress', 'new', 'waiting_on_customer', 'closed']);
    expect(InvoiceEloquentModel::query()->withoutGlobalScopes()->sole())
        ->status->toBe('sent')
        ->customer_id->toBe($customers->first()->id);
});
