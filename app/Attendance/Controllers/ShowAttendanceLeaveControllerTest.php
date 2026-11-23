<?php

declare(strict_types=1);

use App\LeaveRequest\LeavePartEnum;
use App\LeaveRequest\LeaveRequestEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;
use App\WorkSchedule\WorkScheduleEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\travelTo;

beforeEach(function (): void {
    travelTo('2026-11-23 12:00:00');
});

it('shows members their own leave, with the working days it takes', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create(['timezone' => 'UTC']);
    WorkScheduleEloquentModel::factory()->forTeam($owner->current_team_id)->teamDefault()->create();
    $week = LeaveRequestEloquentModel::factory()->forUser($member)->between('2026-12-07', '2026-12-13')->approved()->create(['reviewed_by' => $owner->id, 'review_note' => 'Enjoy']);
    LeaveRequestEloquentModel::factory()->forUser($member)->between('2026-12-01')->part(LeavePartEnum::Afternoon)->create();
    LeaveRequestEloquentModel::factory()->forUser($owner)->between('2026-12-02')->create();

    actingAs($member)
        ->get(route('attendance.leave'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('attendance/leave')
            ->where('canManage', false)
            ->has('mine', 2)
            ->where('mine.0.id', $week->id)
            ->where('mine.0.leave_type', 'Annual leave')
            ->where('mine.0.days', 5)
            ->where('mine.0.status', 'approved')
            ->where('mine.0.review_note', 'Enjoy')
            ->where('mine.0.can_cancel', true)
            ->where('mine.1.days', 0.5)
            ->where('mine.1.part', 'afternoon')
            ->where('pending', [])
            ->where('upcoming', [])
            ->where('members', []));
});

it('gives owners the requests waiting for them and the team’s leave coming up', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create(['name' => 'Ada']);
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create(['name' => 'Ben']);
    $waiting = LeaveRequestEloquentModel::factory()->forUser($member)->between('2026-12-01')->create();
    $coming = LeaveRequestEloquentModel::factory()->forUser($member)->between('2026-12-14', '2026-12-15')->approved()->create();
    LeaveRequestEloquentModel::factory()->forUser($member)->between('2027-06-01')->approved()->create();
    LeaveRequestEloquentModel::factory()->forUser($member)->between('2026-11-02')->approved()->create();

    actingAs($owner)
        ->get(route('attendance.leave'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('canManage', true)
            ->where('mine', [])
            ->has('pending', 1)
            ->where('pending.0.id', $waiting->id)
            ->where('pending.0.user_name', 'Ben')
            ->where('pending.0.days', null)
            ->has('upcoming', 1)
            ->where('upcoming.0.id', $coming->id)
            ->has('members', 2));
});

it('sends guests to sign in', function (): void {
    get(route('attendance.leave'))->assertRedirect(route('login'));
});
