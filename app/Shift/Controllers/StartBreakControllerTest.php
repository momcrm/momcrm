<?php

declare(strict_types=1);

use App\Shift\ShiftEloquentModel;
use App\ShiftBreak\ShiftBreakEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travelTo;

it('starts a break in the running shift', function (): void {
    travelTo('2026-11-23 12:00:20');
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $shift = ShiftEloquentModel::factory()->forUser($user)->open()->create();

    actingAs($user)->post(route('attendance.break.start'))->assertSessionHasNoErrors();

    expect(ShiftBreakEloquentModel::query()->sole())
        ->shift_id->toBe($shift->id)
        ->team_id->toBe($user->current_team_id)
        ->started_at->toDateTimeString()->toBe('2026-11-23 12:00:00')
        ->ended_at->toBeNull();
});

it('keeps the break already running', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $shift = ShiftEloquentModel::factory()->forUser($user)->open()->create();
    ShiftBreakEloquentModel::factory()->forShift($shift)->between(now()->subMinutes(5)->toImmutable())->create();

    actingAs($user)->post(route('attendance.break.start'))->assertSessionHasNoErrors();

    expect(ShiftBreakEloquentModel::query()->count())->toBe(1);
});

it('asks the member to clock in first', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($user)->post(route('attendance.break.start'))->assertSessionHasErrors(['attendance' => 'Clock in before taking a break.']);

    expect(ShiftBreakEloquentModel::query()->count())->toBe(0);
});
