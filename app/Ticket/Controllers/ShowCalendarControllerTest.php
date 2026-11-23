<?php

declare(strict_types=1);

use App\LeaveRequest\LeaveRequestEloquentModel;
use App\Project\ProjectEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\Ticket\TicketEloquentModel;
use App\Ticket\TicketStatusCategoryEnum;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('shows the Monday-first weeks covering a month, with tickets on their due dates', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $user->current_team_id];
    TicketEloquentModel::factory()->create([...$team, 'title' => 'Inside', 'due_on' => '2026-12-15']);
    TicketEloquentModel::factory()->create([...$team, 'title' => 'Spill-over week', 'due_on' => '2026-11-30']);
    TicketEloquentModel::factory()->create([...$team, 'title' => 'Next month', 'due_on' => '2027-01-20']);
    TicketEloquentModel::factory()->create([...$team, 'title' => 'Undated']);
    TicketEloquentModel::factory()->archived()->create([...$team, 'due_on' => '2026-12-15']);
    TicketEloquentModel::factory()->create(['due_on' => '2026-12-15']);

    actingAs($user)
        ->get(route('calendar', ['date' => '2026-12-10']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('calendar')
            ->where('calendar.view', 'month')
            ->where('calendar.from', '2026-11-30')
            ->where('calendar.to', '2027-01-03')
            ->where('tickets', fn ($tickets): bool => collect($tickets)->pluck('title')->all() === ['Spill-over week', 'Inside']),
        );
});

it('shows one week, narrowed by the toolbar filters', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $user->current_team_id];
    TicketEloquentModel::factory()->create([...$team, 'title' => 'Mine', 'due_on' => '2026-12-09', 'assignee_id' => $user->id]);
    TicketEloquentModel::factory()->create([...$team, 'title' => 'Not mine', 'due_on' => '2026-12-10']);
    TicketEloquentModel::factory()->create([...$team, 'title' => 'Next week', 'due_on' => '2026-12-15', 'assignee_id' => $user->id]);

    actingAs($user)
        ->get(route('calendar', ['calendar' => 'week', 'date' => '2026-12-10', 'assignee' => 'me']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('calendar.from', '2026-12-07')
            ->where('calendar.to', '2026-12-13')
            ->where('filters.assignee', 'me')
            ->has('tickets', 1)
            ->where('tickets.0.title', 'Mine'),
        );

    actingAs($user)
        ->get(route('calendar', ['calendar' => 'week', 'date' => '2026-12-10', 'search' => 'not']))
        ->assertInertia(fn (Assert $page) => $page->has('tickets', 1)->where('tickets.0.title', 'Not mine'));
});

it('shows closed tickets too unless a status is picked', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $user->current_team_id];
    $closedStatusId = TicketStatusCategoryEnum::Closed->statusIds()->where('team_id', $user->current_team_id)->value('id');
    TicketEloquentModel::factory()->create([...$team, 'title' => 'Open', 'due_on' => '2026-12-09']);
    TicketEloquentModel::factory()->create([...$team, 'title' => 'Done', 'due_on' => '2026-12-10', 'status_id' => $closedStatusId]);

    actingAs($user)
        ->get(route('calendar', ['calendar' => 'week', 'date' => '2026-12-10']))
        ->assertInertia(fn (Assert $page) => $page->where('filters.status', 'all')->has('tickets', 2));

    actingAs($user)
        ->get(route('calendar', ['calendar' => 'week', 'date' => '2026-12-10', 'status' => 'open']))
        ->assertInertia(fn (Assert $page) => $page->has('tickets', 1)->where('tickets.0.title', 'Open'));
});

it("shows a project's calendar on its page", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $project = ProjectEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'project_id' => $project->id, 'due_on' => '2026-12-10', 'title' => 'In project']);
    TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'due_on' => '2026-12-10']);

    actingAs($user)
        ->get(route('projects.show', ['project' => $project->id, 'view' => 'calendar', 'date' => '2026-12-10']))
        ->assertInertia(fn (Assert $page) => $page->where('tab', 'calendar')->has('tickets', 1)->where('tickets.0.title', 'In project'));
});

it('shows approved leave in the window, with its kind only for owners, admins and the person on leave', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create(['name' => 'Ada']);
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create(['name' => 'Ben']);
    $ownLeave = LeaveRequestEloquentModel::factory()->forUser($owner)->between('2026-12-14', '2026-12-18')->approved()->create();
    LeaveRequestEloquentModel::factory()->forUser($member)->between('2026-12-21')->approved()->create();
    LeaveRequestEloquentModel::factory()->forUser($member)->between('2026-12-22')->create();
    LeaveRequestEloquentModel::factory()->forUser($member)->between('2027-02-01')->approved()->create();

    actingAs($member)
        ->get(route('calendar', ['date' => '2026-12-10']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('leave', 2)
            ->where('leave.0', fn ($leave): bool => $leave['id'] === $ownLeave->id && $leave['name'] === 'Ada' && $leave['leave_type'] === null && $leave['ends_on'] === '2026-12-18')
            ->where('leave.1', fn ($leave): bool => $leave['name'] === 'Ben' && $leave['leave_type'] === 'Annual leave'));

    actingAs($owner)
        ->get(route('calendar', ['date' => '2026-12-10']))
        ->assertInertia(fn (Assert $page) => $page->where('leave.0.leave_type', 'Annual leave'));
});
