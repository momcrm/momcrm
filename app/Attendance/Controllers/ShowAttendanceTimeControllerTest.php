<?php

declare(strict_types=1);

use App\Shift\ShiftEloquentModel;
use App\ShiftBreak\ShiftBreakEloquentModel;
use App\User\UserEloquentModel;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travelTo;

beforeEach(function (): void {
    travelTo('2026-11-25 12:00:00');
});

it('shows the member’s own week, Monday to Sunday, in their timezone', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['timezone' => 'Australia/Perth']);
    $shift = ShiftEloquentModel::factory()->forUser($user)->between(CarbonImmutable::parse('2026-11-24 01:00'), 8)->create(['work_date' => '2026-11-24']);
    ShiftBreakEloquentModel::factory()->forShift($shift)->between(CarbonImmutable::parse('2026-11-24 04:00'), 30)->create();
    ShiftEloquentModel::factory()->forUser($user)->between(CarbonImmutable::parse('2026-11-17 01:00'), 8)->create(['work_date' => '2026-11-17']);

    actingAs($user)
        ->get(route('attendance.time'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('attendance/time')
            ->where('isYou', true)
            ->where('member.timezone', 'Australia/Perth')
            ->where('week.starts_on', '2026-11-23')
            ->where('week.ends_on', '2026-11-29')
            ->has('week.days', 7)
            ->where('week.worked_seconds', (int) (7.5 * 3600))
            ->where('week.break_seconds', 30 * 60)
            ->has('week.days.1.shifts', 1)
            ->where('week.days.1.shifts.0.clocked_in_local', '2026-11-24T09:00')
            ->where('week.days.1.shifts.0.clocked_out_local', '2026-11-24T17:00')
            ->where('week.days.1.shifts.0.breaks.0.started_local', '2026-11-24T12:00'));
});

it('shows the week holding the day asked for', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($user)
        ->get(route('attendance.time', ['week' => '2026-11-19']))
        ->assertInertia(fn (Assert $page) => $page->where('week.starts_on', '2026-11-16'));
});

it('lets owners see anyone’s week and pick from the team', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create(['name' => 'Ben']);

    actingAs($owner)
        ->get(route('attendance.time', ['member' => $member->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('member.name', 'Ben')
            ->where('isYou', false)
            ->where('canManage', true)
            ->has('members', 2));
});

it('gives members no one else to pick', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();

    actingAs($member)
        ->get(route('attendance.time'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('canManage', false)->where('members', []));
});

it('keeps members to their own week', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();

    actingAs($member)->get(route('attendance.time', ['member' => $owner->id]))->assertForbidden();
});

it('does not find people outside the team', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $outsider = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($owner)->get(route('attendance.time', ['member' => $outsider->id]))->assertNotFound();
});
