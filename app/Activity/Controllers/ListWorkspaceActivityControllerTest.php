<?php

declare(strict_types=1);

use App\Activity\Actions\RecordActivity;
use App\Activity\ActivityTypeEnum;
use App\CustomObject\CustomObjectEloquentModel;
use App\Record\RecordEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('shows work on tickets and projects, not account events', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $teamId = $user->current_team_id;
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $teamId]);
    RecordActivity::for(ActivityTypeEnum::TicketCommented, $user->id, $teamId, $ticket, ['number' => 1]);
    RecordActivity::for(ActivityTypeEnum::TicketClosed, $user->id, $teamId, $ticket, ['number' => 1]);
    RecordActivity::for(ActivityTypeEnum::SignedIn, $user->id, $teamId);
    RecordActivity::for(ActivityTypeEnum::TicketOpened, $user->id, TicketEloquentModel::factory()->create()->team_id);

    actingAs($user)
        ->get(route('activity'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('activity')
            ->where('activities.total', 2)
            ->where('activities.data.0.url', route('tickets.index', ['ticket' => $ticket->id])),
        );

    actingAs($user)
        ->get(route('activity', ['filter' => 'comments']))
        ->assertInertia(fn (Assert $page) => $page->where('activities.total', 1)->where('activities.data.0.type', 'ticket_commented'));
});

it('filters by person', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    RecordActivity::for(ActivityTypeEnum::TicketClosed, $user->id, $user->current_team_id, $ticket);
    RecordActivity::for(ActivityTypeEnum::TicketClosed, null, $user->current_team_id, $ticket);

    actingAs($user)
        ->get(route('activity', ['person' => $user->id]))
        ->assertInertia(fn (Assert $page) => $page->where('activities.total', 1));
});

it("links a record's activity to its page under its object", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['onboarded_at' => now()]);
    $vehicles = CustomObjectEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $van = RecordEloquentModel::factory()->of($vehicles)->create();
    RecordActivity::for(ActivityTypeEnum::RecordCreated, $user->id, $user->current_team_id, $van, ['name' => 'Van']);

    Pest\Laravel\actingAs($user)
        ->get(route('activity'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('activities.data.0.type', 'record_created')
            ->where('activities.data.0.url', route('records.show', [$vehicles->id, $van->id])),
        );
});
