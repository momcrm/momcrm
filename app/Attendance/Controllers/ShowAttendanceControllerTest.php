<?php

declare(strict_types=1);

use App\Shift\ShiftEloquentModel;
use App\ShiftBreak\ShiftBreakEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\travelTo;

it('shows who is in, on a break, finished and not in yet today', function (): void {
    travelTo('2026-11-23 12:00:00');
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create(['name' => 'Ada', 'timezone' => 'UTC']);
    $inTeam = fn (string $name) => UserEloquentModel::factory()->inTeam($owner->current_team_id)->create(['name' => $name, 'timezone' => 'UTC']);
    $working = $inTeam('Ben');
    $resting = $inTeam('Cy');
    $finished = $inTeam('Dee');

    ShiftEloquentModel::factory()->forUser($working)->between(CarbonImmutable::parse('2026-11-23 09:00'))->create();
    $restingShift = ShiftEloquentModel::factory()->forUser($resting)->between(CarbonImmutable::parse('2026-11-23 08:00'))->create();
    ShiftBreakEloquentModel::factory()->forShift($restingShift)->between(CarbonImmutable::parse('2026-11-23 11:30'))->create();
    ShiftEloquentModel::factory()->forUser($finished)->between(CarbonImmutable::parse('2026-11-23 06:00'), 4)->create();
    ShiftEloquentModel::factory()->forUser($finished)->between(CarbonImmutable::parse('2026-11-22 06:00'), 4)->create();
    ShiftEloquentModel::factory()->between(CarbonImmutable::parse('2026-11-23 09:00'))->create();

    actingAs($owner)
        ->get(route('attendance.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('attendance/today')
            ->where('today', '2026-11-23')
            ->where('canManage', true)
            ->has('members', 4)
            ->where('members.0', fn ($row): bool => $row['name'] === 'Ada' && $row['status'] === 'not_in' && $row['worked_seconds'] === 0)
            ->where('members.1', fn ($row): bool => $row['name'] === 'Ben' && $row['status'] === 'in' && $row['worked_seconds'] === 3 * 3600)
            ->where('members.2', fn ($row): bool => $row['name'] === 'Cy' && $row['status'] === 'break' && $row['on_break_since'] !== null && $row['worked_seconds'] === (int) (3.5 * 3600))
            ->where('members.3', fn ($row): bool => $row['name'] === 'Dee' && $row['status'] === 'out' && str_starts_with((string) $row['last_out_at'], '2026-11-23T10:00') && $row['worked_seconds'] === 4 * 3600));
});

it('shares the signed-in member’s clock with every page', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $shift = ShiftEloquentModel::factory()->forUser($user)->open()->create();

    actingAs($user)
        ->get(route('attendance.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('attendanceClock.shift_id', $shift->id)
            ->where('attendanceClock.on_break_since', null));
});

it('tells members they cannot correct shifts', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();

    actingAs($member)
        ->get(route('attendance.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('canManage', false)->has('members', 2));
});

it('sends guests to sign in', function (): void {
    get(route('attendance.index'))->assertRedirect(route('login'));
});
