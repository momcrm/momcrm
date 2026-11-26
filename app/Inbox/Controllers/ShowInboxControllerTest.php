<?php

declare(strict_types=1);

use App\Comment\Notifications\MentionedInRecordCommentNotification;
use App\CustomObject\CustomObjectEloquentModel;
use App\LeaveRequest\LeaveRequestEloquentModel;
use App\LeaveRequest\Notifications\LeaveRequestedNotification;
use App\Record\RecordEloquentModel;
use App\Shift\Notifications\ShiftStillOpenNotification;
use App\Shift\ShiftEloquentModel;
use App\Ticket\Notifications\TicketAssignedNotification;
use App\Ticket\Notifications\TicketDueSoonNotification;
use App\Ticket\TicketEloquentModel;
use App\Timesheet\Notifications\TimesheetSubmittedNotification;
use App\Timesheet\TimesheetEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it("lists the person's notifications in their current team, with unread counts", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'title' => 'Printer']);
    $user->notify(new TicketAssignedNotification($ticket, null));
    $user->notify(new TicketDueSoonNotification($ticket));
    $user->notify(new TicketAssignedNotification(TicketEloquentModel::factory()->create(), null));
    $user->notifications()->where('type', TicketDueSoonNotification::class)->sole()->markAsRead();

    actingAs($user)
        ->get(route('inbox'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('inbox')
            ->has('notifications', 2)
            ->where('counts.all', 1)
            ->where('counts.assignment', 1)
            ->where('counts.update', 0)
            ->where('notifications', fn ($notifications): bool => collect($notifications)->pluck('title')->unique()->all() === ['Printer']),
        );
});

it('filters by kind and unread, and shows the selected ticket', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $user->notify(new TicketAssignedNotification($ticket, null));
    $user->notify(new TicketDueSoonNotification($ticket));

    actingAs($user)
        ->get(route('inbox', ['kind' => 'assignment', 'unread' => 1, 'ticket' => $ticket->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('notifications', 1)
            ->where('notifications.0.kind', 'assignment')
            ->where('filters.unread', true)
            ->where('selected.id', $ticket->id)
            ->has('selected.comments'),
        );
});

it('reloads the notifications and their counts when the inbox changes', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();

    $response = actingAs($user)->get(route('inbox'))->assertOk();

    expect(liveListeners($response, 'notifications'))->toBe(["private-inbox.{$user->id} inbox.changed"])
        ->and(liveListeners($response, 'counts'))->toBe(["private-inbox.{$user->id} inbox.changed"]);
});

it('links a notification about a ticket to the ticket', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $user->notify(new TicketDueSoonNotification($ticket));

    actingAs($user)
        ->get(route('inbox'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('notifications.0.message', 'due tomorrow')
            ->where('notifications.0.url', route('tickets.index', ['ticket' => $ticket->id])),
        );
});

it("links a mention on a custom object's record to the record's page", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['onboarded_at' => now()]);
    $vehicles = CustomObjectEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name_plural' => 'Vehicles']);
    $van = RecordEloquentModel::factory()->of($vehicles)->create(['name' => 'Van']);
    $user->notify(new MentionedInRecordCommentNotification($van, 'Vehicles', null, 'Service is due'));

    actingAs($user)
        ->get(route('inbox'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('notifications.0.subject_type', 'record')
            ->where('notifications.0.title', 'Van')
            ->where('notifications.0.project_name', 'Vehicles')
            ->where('notifications.0.url', route('records.show', [$vehicles->id, $van->id])),
        );
});

it('links a reminder about a shift to the person’s time', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $shift = ShiftEloquentModel::factory()->forUser($user)->open()->create();
    $user->notify(new ShiftStillOpenNotification($shift, 'Mon 23 Nov at 9:00 AM'));

    actingAs($user)
        ->get(route('inbox'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('notifications.0.subject_type', 'shift')
            ->where('notifications.0.title', 'You’re still clocked in')
            ->where('notifications.0.url', route('attendance.time')),
        );
});

it('links a leave request to the Leave page', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create(['name' => 'Ben']);
    $leave = LeaveRequestEloquentModel::factory()->forUser($member)->create(['reason' => 'Wedding']);
    $owner->notify(new LeaveRequestedNotification($leave, $member, 'Annual leave', 'Mon 30 Nov'));

    actingAs($owner)
        ->get(route('inbox'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('notifications.0.subject_type', 'leave')
            ->where('notifications.0.title', 'Annual leave: Mon 30 Nov')
            ->where('notifications.0.actor_name', 'Ben')
            ->where('notifications.0.excerpt', 'Wedding')
            ->where('notifications.0.url', route('attendance.leave')),
        );
});

it('links a submitted timesheet to the timesheet', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();
    $timesheet = TimesheetEloquentModel::factory()->forUser($member)->period('2026-11-16')->create();
    $owner->notify(new TimesheetSubmittedNotification($timesheet, $member, 'Mon 16 Nov – Sun 22 Nov'));

    actingAs($owner)
        ->get(route('inbox'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('notifications.0.subject_type', 'timesheet')
            ->where('notifications.0.title', 'Timesheet: Mon 16 Nov – Sun 22 Nov')
            ->where('notifications.0.url', route('attendance.timesheet', [$member->id, '2026-11-16'])),
        );
});

it('sends the old notifications address to the inbox', function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->get('/notifications')
        ->assertRedirect(route('inbox'));
});
