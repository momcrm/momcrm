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
use function Pest\Laravel\get;
use function Pest\Laravel\travelTo;

beforeEach(function (): void {
    travelTo('2026-11-25 12:00:00');
});

it('lists the member’s recent periods with what each adds up to', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create(['timezone' => 'UTC']);
    WorkScheduleEloquentModel::factory()->forTeam($owner->current_team_id)->teamDefault()->weekdays('09:00', '17:00')->create();
    ShiftEloquentModel::factory()->forUser($member)->between(CarbonImmutable::parse('2026-11-17 09:30'), 8)->create();
    TimesheetEloquentModel::factory()->forUser($member)->period('2026-11-09')->approved()->create();

    actingAs($member)
        ->get(route('attendance.timesheets'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('attendance/timesheets')
            ->where('periodLength', 'weekly')
            ->has('mine', 6)
            ->where('mine.0.starts_on', '2026-11-23')
            ->where('mine.0.status', 'open')
            ->where('mine.0.can_submit', true)
            ->where('mine.1.starts_on', '2026-11-16')
            ->where('mine.1.worked_seconds', 8 * 3600)
            ->where('mine.1.expected_seconds', 5 * 8 * 3600)
            ->where('mine.1.late_days', 1)
            ->where('mine.1.absent_days', 4)
            ->where('mine.2.status', 'approved')
            ->where('mine.2.can_submit', false)
            ->where('team', null)
            ->where('canManage', false));
});

it('gives owners everyone’s timesheets for the last finished period, or the one asked for', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create(['name' => 'Ada']);
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create(['name' => 'Ben']);
    $submitted = TimesheetEloquentModel::factory()->forUser($member)->period('2026-11-16')->create();

    actingAs($owner)
        ->get(route('attendance.timesheets'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('team.period.starts_on', '2026-11-16')
            ->where('team.period.previous', '2026-11-09')
            ->where('team.period.next', '2026-11-23')
            ->has('team.rows', 2)
            ->where('team.rows.0.status', 'open')
            ->where('team.rows.1.name', 'Ben')
            ->where('team.rows.1.status', 'submitted')
            ->where('team.rows.1.timesheet_id', $submitted->id));

    actingAs($owner)
        ->get(route('attendance.timesheets', ['period' => '2026-11-09']))
        ->assertInertia(fn (Assert $page) => $page->where('team.period.starts_on', '2026-11-09')->where('team.rows.1.status', 'open'));
});

it('sends guests to sign in', function (): void {
    get(route('attendance.timesheets'))->assertRedirect(route('login'));
});
