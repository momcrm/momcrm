<?php

declare(strict_types=1);

use App\Contact\ContactEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Group\GroupEloquentModel;
use App\Invoice\InvoiceEloquentModel;
use App\Label\LabelEloquentModel;
use App\Milestone\MilestoneEloquentModel;
use App\Onboarding\Actions\SeedSampleData;
use App\Project\ProjectEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;

it('adds sample customers with contacts, tickets and an invoice to the team', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();

    SeedSampleData::forTeam(teamId: $user->current_team_id, userId: $user->id);

    $customers = CustomerEloquentModel::query()->withoutGlobalScopes()->orderBy('number')->get();
    expect($customers->pluck('name')->all())->toBe(['Harbour Dental', 'Coastal Logistics', 'Bright Spark Electrical'])
        ->and($customers->every(fn (CustomerEloquentModel $customer): bool => $customer->is_sample && $customer->team_id === $user->current_team_id))->toBeTrue();
    expect(ContactEloquentModel::query()->withoutGlobalScopes()->count())->toBe(4);
    expect(TicketEloquentModel::query()->withoutGlobalScopes()->whereNotNull('customer_id')->whereNull('project_id')->orderBy('number')->pluck('status')->all())
        ->toBe(['in_progress', 'closed']);
    expect(InvoiceEloquentModel::query()->withoutGlobalScopes()->sole())
        ->status->toBe('sent')
        ->customer_id->toBe($customers->first()->id);
});

it('adds sample projects with milestones, labelled tickets with due dates and a group', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();

    SeedSampleData::forTeam(teamId: $user->current_team_id, userId: $user->id);

    $projects = ProjectEloquentModel::query()->withoutGlobalScopes()->orderBy('number')->get();
    expect($projects->pluck('name')->all())->toBe(['Harbour Dental office refit', 'Coastal Logistics VPN upgrade', 'Documentation overhaul'])
        ->and($projects->every(fn (ProjectEloquentModel $project): bool => $project->is_sample && $project->lead_id === $user->id))->toBeTrue()
        ->and($projects->last()->customer_id)->toBeNull();
    expect(MilestoneEloquentModel::query()->withoutGlobalScopes()->count())->toBe(4);
    expect(LabelEloquentModel::query()->withoutGlobalScopes()->pluck('name')->sort()->values()->all())->toBe(['Billing', 'Hardware', 'Network', 'Onboarding']);
    expect(GroupEloquentModel::query()->withoutGlobalScopes()->sole()->name)->toBe('Service desk');

    $internal = TicketEloquentModel::query()->withoutGlobalScopes()->where('project_id', $projects->last()->id)->get();
    expect($internal)->toHaveCount(2)
        ->and($internal->every(fn (TicketEloquentModel $ticket): bool => $ticket->customer_id === null && $ticket->due_on !== null))->toBeTrue();
    expect(TicketEloquentModel::query()->withoutGlobalScopes()->where('title', 'Reception printer offline')->sole()->labels->pluck('name')->all())->toBe(['Hardware']);
});

it('reuses the labels and group when sample data is added again', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();

    SeedSampleData::forTeam(teamId: $user->current_team_id, userId: $user->id);
    SeedSampleData::forTeam(teamId: $user->current_team_id, userId: $user->id);

    expect(LabelEloquentModel::query()->withoutGlobalScopes()->count())->toBe(4)
        ->and(GroupEloquentModel::query()->withoutGlobalScopes()->count())->toBe(1);
});
