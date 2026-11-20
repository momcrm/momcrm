<?php

declare(strict_types=1);

use App\Choice\ChoiceEloquentModel;
use App\Contact\ContactEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Group\GroupEloquentModel;
use App\Invoice\InvoiceEloquentModel;
use App\Milestone\MilestoneEloquentModel;
use App\Onboarding\Actions\SeedSampleData;
use App\Project\ProjectEloquentModel;
use App\Rate\RateEloquentModel;
use App\Service\ServiceEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;

it('adds sample customers with contacts, tickets and an invoice to the team', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();

    SeedSampleData::forTeam(teamId: $user->current_team_id, userId: $user->id);

    $customers = CustomerEloquentModel::query()->withoutGlobalScopes()->orderBy('number')->get();
    expect($customers->pluck('name')->all())->toBe(['Harbour Dental', 'Coastal Logistics', 'Bright Spark Electrical'])
        ->and($customers->every(fn (CustomerEloquentModel $customer): bool => $customer->is_sample && $customer->team_id === $user->current_team_id))->toBeTrue();
    expect(ContactEloquentModel::query()->withoutGlobalScopes()->count())->toBe(4);
    expect(TicketEloquentModel::query()->withoutGlobalScopes()->whereNotNull('customer_id')->whereNull('project_id')->orderBy('number')->pluck('status_id')->all())
        ->toBe([ticketStatusId($user->current_team_id, 'In progress'), ticketStatusId($user->current_team_id, 'Closed')]);
    expect(InvoiceEloquentModel::query()->withoutGlobalScopes()->sole())
        ->status->toBe('sent')
        ->customer_id->toBe($customers->first()->id);
});

it('prices the sample services from rates added to the price list, reusing ones it already added', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    RateEloquentModel::factory()->priced(['AUD' => 9900])->create(['team_id' => $user->current_team_id, 'name' => 'Cloud backup']);

    SeedSampleData::forTeam(teamId: $user->current_team_id, userId: $user->id);

    $rates = RateEloquentModel::query()->withoutGlobalScopes()->orderBy('name')->get();
    expect($rates->pluck('name')->all())->toBe(['Cloud backup', 'Managed IT support', 'Microsoft 365 Business Standard'])
        ->and($rates->firstWhere('name', 'Managed IT support')->prices)->toEqual([['currency' => 'AUD', 'price_in_cents' => 4500, 'cost_in_cents' => null]])
        ->and(ServiceEloquentModel::query()->withoutGlobalScopes()->whereNull('rate_id')->exists())->toBeFalse()
        ->and(ServiceEloquentModel::query()->withoutGlobalScopes()->where('name', 'Cloud backup')->sole()->rate_id)->toBe($rates->first()->id);
});

it('adds sample projects with milestones, labelled tickets with due dates and a group', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();

    SeedSampleData::forTeam(teamId: $user->current_team_id, userId: $user->id);

    $projects = ProjectEloquentModel::query()->withoutGlobalScopes()->orderBy('number')->get();
    expect($projects->pluck('name')->all())->toBe(['Harbour Dental office refit', 'Coastal Logistics VPN upgrade', 'Documentation overhaul'])
        ->and($projects->every(fn (ProjectEloquentModel $project): bool => $project->is_sample && $project->lead_id === $user->id))->toBeTrue()
        ->and($projects->last()->customer_id)->toBeNull();
    expect(MilestoneEloquentModel::query()->withoutGlobalScopes()->count())->toBe(4);
    expect(ChoiceEloquentModel::query()->withoutGlobalScopes()->where('list', 'label')->pluck('name')->sort()->values()->all())->toBe(['Billing', 'Hardware', 'Network', 'Onboarding', 'Security', 'Software']);
    expect(GroupEloquentModel::query()->withoutGlobalScopes()->sole()->name)->toBe('Service desk');

    $internal = TicketEloquentModel::query()->withoutGlobalScopes()->where('project_id', $projects->last()->id)->get();
    expect($internal)->toHaveCount(2)
        ->and($internal->every(fn (TicketEloquentModel $ticket): bool => $ticket->customer_id === null && $ticket->due_on !== null))->toBeTrue();
    expect(TicketEloquentModel::query()->withoutGlobalScopes()->where('title', 'Reception printer offline')->sole()->labels->pluck('name')->all())->toBe(['Hardware']);
});

it('reuses the labels and group when sample data is added again, bringing back a deleted label', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    ChoiceEloquentModel::query()->withoutGlobalScopes()->where('list', 'label')->where('name', 'Network')->delete();

    SeedSampleData::forTeam(teamId: $user->current_team_id, userId: $user->id);
    SeedSampleData::forTeam(teamId: $user->current_team_id, userId: $user->id);

    expect(ChoiceEloquentModel::query()->withoutGlobalScopes()->where('list', 'label')->count())->toBe(6)
        ->and(GroupEloquentModel::query()->withoutGlobalScopes()->count())->toBe(1);
});
