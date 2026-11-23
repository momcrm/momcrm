<?php

declare(strict_types=1);

use App\Comment\Notifications\MentionedInRecordCommentNotification;
use App\CustomObject\CustomObjectEloquentModel;
use App\LeaveRequest\LeaveRequestEloquentModel;
use App\LeaveRequest\Notifications\LeaveRequestedNotification;
use App\Record\RecordEloquentModel;
use App\Shift\Notifications\ShiftStillOpenNotification;
use App\Shift\ShiftEloquentModel;
use App\Ticket\Notifications\TicketDueSoonNotification;
use App\Ticket\TicketEloquentModel;
use App\Timesheet\Notifications\TimesheetSubmittedNotification;
use App\Timesheet\TimesheetEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('lists every notification with a link to its ticket', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $user->notify(new TicketDueSoonNotification($ticket));

    actingAs($user)
        ->get(route('notifications.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('notifications')
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
        ->get(route('notifications.index'))
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
        ->get(route('notifications.index'))
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
        ->get(route('notifications.index'))
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
        ->get(route('notifications.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('notifications.0.subject_type', 'timesheet')
            ->where('notifications.0.title', 'Timesheet: Mon 16 Nov – Sun 22 Nov')
            ->where('notifications.0.url', route('attendance.timesheet', [$member->id, '2026-11-16'])),
        );
});
