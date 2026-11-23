<?php

declare(strict_types=1);

use App\Attendance\AttendanceCalendar;
use App\Attendance\AttendanceTimezone;
use App\Attendance\ExpectedDay;
use App\Holiday\HolidayEloquentModel;
use App\LeaveRequest\LeavePartEnum;
use App\LeaveRequest\LeaveRequest;
use App\LeaveRequest\LeaveRequestEloquentModel;
use App\TeamUser\TeamUserEloquentModel;
use App\User\UserEloquentModel;
use App\WorkSchedule\WorkScheduleEloquentModel;

/**
 * The calendar for the owner's team over the week of Monday 23 November 2026.
 */
function calendarOf(UserEloquentModel $owner): AttendanceCalendar
{
    return AttendanceCalendar::forTeam($owner->current_team_id, AttendanceTimezone::membersOfTeam($owner->current_team_id), '2026-11-23', '2026-11-29');
}

it('expects the default schedule’s hours of members without their own, in their timezone', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    WorkScheduleEloquentModel::factory()->forTeam($owner->current_team_id)->teamDefault()->weekdays('09:00', '17:00', 30)->create();

    $monday = calendarOf($owner)->expectedOn($owner->id, '2026-11-23', 'Australia/Perth');

    expect($monday->kind)->toBe(ExpectedDay::WORKDAY)
        ->and($monday->startsAt?->utc()->toDateTimeString())->toBe('2026-11-23 01:00:00')
        ->and($monday->endsAt?->utc()->toDateTimeString())->toBe('2026-11-23 09:00:00')
        ->and($monday->expectedSeconds())->toBe((int) (7.5 * 3600))
        ->and(calendarOf($owner)->expectedOn($owner->id, '2026-11-28', 'UTC')->kind)->toBe(ExpectedDay::DAY_OFF);
});

it('expects a member’s own schedule over the default', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();
    WorkScheduleEloquentModel::factory()->forTeam($owner->current_team_id)->teamDefault()->create();
    $nights = WorkScheduleEloquentModel::factory()->forTeam($owner->current_team_id)->weekdays('22:00', '06:00')->create();
    TeamUserEloquentModel::query()->where('user_id', $member->id)->update(['work_schedule_id' => $nights->id]);

    $monday = calendarOf($owner)->expectedOn($member->id, '2026-11-23', 'UTC');

    expect($monday->startsAt?->toDateTimeString())->toBe('2026-11-23 22:00:00')
        ->and($monday->endsAt?->toDateTimeString())->toBe('2026-11-24 06:00:00')
        ->and($monday->expectedSeconds())->toBe(8 * 3600)
        ->and(calendarOf($owner)->expectedOn($owner->id, '2026-11-23', 'UTC')->startsAt?->toDateTimeString())->toBe('2026-11-23 09:00:00');
});

it('expects nothing on the team’s holidays', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    WorkScheduleEloquentModel::factory()->forTeam($owner->current_team_id)->teamDefault()->create();
    HolidayEloquentModel::factory()->forTeam($owner->current_team_id)->on('2026-11-24')->create(['name' => 'Founders’ Day']);
    HolidayEloquentModel::factory()->on('2026-11-25')->create();

    $tuesday = calendarOf($owner)->expectedOn($owner->id, '2026-11-24', 'UTC');

    expect($tuesday->kind)->toBe(ExpectedDay::HOLIDAY)
        ->and($tuesday->holiday)->toBe('Founders’ Day')
        ->and($tuesday->expectedSeconds())->toBe(0)
        ->and(calendarOf($owner)->expectedOn($owner->id, '2026-11-25', 'UTC')->kind)->toBe(ExpectedDay::WORKDAY);
});

it('leaves every day unscheduled when the team has no schedules', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    WorkScheduleEloquentModel::factory()->teamDefault()->create();

    expect(calendarOf($owner)->expectedOn($owner->id, '2026-11-23', 'UTC')->kind)->toBe(ExpectedDay::UNSCHEDULED);
});

it('takes approved leave off the member’s days, half a day from its middle', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    WorkScheduleEloquentModel::factory()->forTeam($owner->current_team_id)->teamDefault()->weekdays('09:00', '17:00', 30)->create();
    HolidayEloquentModel::factory()->forTeam($owner->current_team_id)->on('2026-11-26')->create();
    LeaveRequestEloquentModel::factory()->forUser($owner)->between('2026-11-23')->approved()->create();
    LeaveRequestEloquentModel::factory()->forUser($owner)->between('2026-11-24')->part(LeavePartEnum::Morning)->approved()->create();
    LeaveRequestEloquentModel::factory()->forUser($owner)->between('2026-11-25')->create();
    LeaveRequestEloquentModel::factory()->forUser($owner)->between('2026-11-26', '2026-11-28')->approved()->create();
    $calendar = calendarOf($owner);

    $monday = $calendar->expectedOn($owner->id, '2026-11-23', 'UTC');
    $tuesday = $calendar->expectedOn($owner->id, '2026-11-24', 'UTC');

    expect($monday->kind)->toBe(ExpectedDay::ON_LEAVE)
        ->and($monday->leave)->toBe('Annual leave')
        ->and($tuesday->kind)->toBe(ExpectedDay::WORKDAY)
        ->and($tuesday->leave)->toBe('Annual leave (morning)')
        ->and($tuesday->startsAt?->toDateTimeString())->toBe('2026-11-24 13:00:00')
        ->and($tuesday->expectedSeconds())->toBe(4 * 3600)
        ->and($calendar->expectedOn($owner->id, '2026-11-25', 'UTC')->kind)->toBe(ExpectedDay::WORKDAY)
        ->and($calendar->expectedOn($owner->id, '2026-11-26', 'UTC')->kind)->toBe(ExpectedDay::HOLIDAY)
        ->and($calendar->expectedOn($owner->id, '2026-11-27', 'UTC')->kind)->toBe(ExpectedDay::ON_LEAVE)
        ->and($calendar->expectedOn($owner->id, '2026-11-28', 'UTC')->kind)->toBe(ExpectedDay::DAY_OFF);
});

it('counts the working days leave takes', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    WorkScheduleEloquentModel::factory()->forTeam($owner->current_team_id)->teamDefault()->create();
    HolidayEloquentModel::factory()->forTeam($owner->current_team_id)->on('2026-11-26')->create();
    $week = LeaveRequestEloquentModel::factory()->forUser($owner)->between('2026-11-23', '2026-11-29')->approved()->create();
    $morning = LeaveRequestEloquentModel::factory()->forUser($owner)->between('2026-11-30')->part(LeavePartEnum::Morning)->create();
    $calendar = calendarOf($owner);

    expect($calendar->workdaysOf(LeaveRequest::fromEloquentModel($week), 'UTC'))->toBe(4.0)
        ->and($calendar->workdaysOf(LeaveRequest::fromEloquentModel($morning), 'UTC'))->toBe(0.5);
});
