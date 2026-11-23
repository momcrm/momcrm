<?php

declare(strict_types=1);

use App\Shift\ShiftEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\Timesheet\TimesheetEloquentModel;
use App\User\UserEloquentModel;
use App\WorkSchedule\WorkScheduleEloquentModel;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travelTo;

beforeEach(function (): void {
    travelTo('2026-11-25 12:00:00');
});

it('shows a member their own period, day by day', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['timezone' => 'UTC']);
    WorkScheduleEloquentModel::factory()->forTeam($user->current_team_id)->teamDefault()->create();
    ShiftEloquentModel::factory()->forUser($user)->between(CarbonImmutable::parse('2026-11-16 09:00'), 8)->create();

    actingAs($user)
        ->get(route('attendance.timesheet', [$user->id, '2026-11-16']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('attendance/timesheet')
            ->where('isYou', true)
            ->where('canSubmit', true)
            ->where('timesheet.status', 'open')
            ->where('timesheet.worked_seconds', 8 * 3600)
            ->where('timesheet.previous', '2026-11-09')
            ->has('days', 7)
            ->where('days.0.date', '2026-11-16')
            ->where('days.0.worked_seconds', 8 * 3600)
            ->missing('days.0.shifts'));
});

it('keeps an approved timesheet’s days as they were approved', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create(['timezone' => 'UTC']);
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();
    $timesheet = TimesheetEloquentModel::factory()->forUser($member)->period('2026-11-16')->submitted()->create();
    ShiftEloquentModel::factory()->forUser($member)->between(CarbonImmutable::parse('2026-11-16 09:00'), 8)->create();
    actingAs($owner)->post(route('timesheets.approve', $timesheet->id));
    ShiftEloquentModel::query()->delete();

    actingAs($owner)
        ->get(route('attendance.timesheet', [$member->id, '2026-11-16']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('timesheet.status', 'approved')
            ->where('timesheet.is_kept', true)
            ->where('timesheet.worked_seconds', 8 * 3600)
            ->where('days.0.worked_seconds', 8 * 3600)
            ->where('canSubmit', false));
});

it('does not show members anyone else’s timesheet, or a period that isn’t one', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();
    $stranger = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($member)->get(route('attendance.timesheet', [$owner->id, '2026-11-16']))->assertNotFound();
    actingAs($member)->get(route('attendance.timesheet', [$member->id, '2026-11-17']))->assertNotFound();
    actingAs($owner)->get(route('attendance.timesheet', [$stranger->id, '2026-11-16']))->assertNotFound();
    actingAs($owner)->get(route('attendance.timesheet', [$member->id, '2026-11-16']))->assertOk();
});
