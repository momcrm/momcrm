<?php

declare(strict_types=1);

use App\Shift\ShiftEloquentModel;
use App\ShiftBreak\ShiftBreakEloquentModel;
use App\User\UserEloquentModel;
use Carbon\CarbonImmutable;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travelTo;

it('ends the break and carries on with the shift', function (): void {
    travelTo('2026-11-23 12:30:10');
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $shift = ShiftEloquentModel::factory()->forUser($user)->between(CarbonImmutable::parse('2026-11-23 09:00'))->create();
    $break = ShiftBreakEloquentModel::factory()->forShift($shift)->between(CarbonImmutable::parse('2026-11-23 12:00'))->create();

    actingAs($user)->post(route('attendance.break.end'))->assertSessionHasNoErrors();

    expect($break->refresh()->ended_at?->toDateTimeString())->toBe('2026-11-23 12:30:00')
        ->and($shift->refresh()->clocked_out_at)->toBeNull();
});

it('says so when the member is not on a break', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    ShiftEloquentModel::factory()->forUser($user)->open()->create();

    actingAs($user)->post(route('attendance.break.end'))->assertSessionHasErrors(['attendance' => 'You aren’t on a break.']);
});
