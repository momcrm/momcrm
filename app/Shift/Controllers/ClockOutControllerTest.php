<?php

declare(strict_types=1);

use App\Shift\ShiftEloquentModel;
use App\ShiftBreak\ShiftBreakEloquentModel;
use App\User\UserEloquentModel;
use Carbon\CarbonImmutable;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travelTo;

it('ends the running shift and the break the member is on', function (): void {
    travelTo('2026-11-23 17:05:30');
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $shift = ShiftEloquentModel::factory()->forUser($user)->between(CarbonImmutable::parse('2026-11-23 09:00'))->create();
    $break = ShiftBreakEloquentModel::factory()->forShift($shift)->between(CarbonImmutable::parse('2026-11-23 16:45'))->create();

    actingAs($user)->post(route('attendance.clock-out'))->assertSessionHasNoErrors();

    expect($shift->refresh()->clocked_out_at?->toDateTimeString())->toBe('2026-11-23 17:05:00')
        ->and($break->refresh()->ended_at?->toDateTimeString())->toBe('2026-11-23 17:05:00');
});

it('says so when the member is not clocked in', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $finished = ShiftEloquentModel::factory()->forUser($user)->create();
    $clockedOutAt = $finished->clocked_out_at?->toDateTimeString();

    actingAs($user)->post(route('attendance.clock-out'))->assertSessionHasErrors(['attendance' => 'You aren’t clocked in.']);

    expect($finished->refresh()->clocked_out_at?->toDateTimeString())->toBe($clockedOutAt);
});

it('leaves teammates’ shifts running', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $teammate = UserEloquentModel::factory()->inTeam($user->current_team_id)->create();
    $theirs = ShiftEloquentModel::factory()->forUser($teammate)->open()->create();
    ShiftEloquentModel::factory()->forUser($user)->open()->create();

    actingAs($user)->post(route('attendance.clock-out'))->assertSessionHasNoErrors();

    expect($theirs->refresh()->clocked_out_at)->toBeNull();
});
