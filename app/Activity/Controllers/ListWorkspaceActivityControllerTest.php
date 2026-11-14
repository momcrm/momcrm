<?php

declare(strict_types=1);

use App\Activity\Actions\RecordActivity;
use App\Activity\ActivityTypeEnum;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
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
