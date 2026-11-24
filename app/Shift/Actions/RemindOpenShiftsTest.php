<?php

declare(strict_types=1);

use App\AuditLog\AuditLogEloquentModel;
use App\Shift\Actions\RemindOpenShifts;
use App\Shift\Notifications\ShiftStillOpenNotification;
use App\Shift\ShiftEloquentModel;
use App\Team\TeamEloquentModel;
use App\User\UserEloquentModel;
use App\WorkSchedule\WorkScheduleEloquentModel;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    Notification::fake();
});

it('reminds members still clocked in two hours after their scheduled end, once', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['timezone' => 'Australia/Perth']);
    WorkScheduleEloquentModel::factory()->forTeam($user->current_team_id)->teamDefault()->weekdays('09:00', '17:00')->create();
    $shift = ShiftEloquentModel::factory()->forUser($user)->between(CarbonImmutable::parse('2026-11-23 01:00'))->create(['work_date' => '2026-11-23']);
    $audited = AuditLogEloquentModel::query()->count();

    expect(RemindOpenShifts::run(CarbonImmutable::parse('2026-11-23 10:59')))->toBe(0)
        ->and(RemindOpenShifts::run(CarbonImmutable::parse('2026-11-23 11:00')))->toBe(1)
        ->and(RemindOpenShifts::run(CarbonImmutable::parse('2026-11-23 12:00')))->toBe(0)
        ->and($shift->refresh()->clocked_out_at)->toBeNull()
        ->and($shift->reminded_at?->toDateTimeString())->toBe('2026-11-23 11:00:00')
        ->and(AuditLogEloquentModel::query()->count())->toBe($audited);

    Notification::assertSentTo($user, ShiftStillOpenNotification::class, fn (ShiftStillOpenNotification $notification): bool => $notification->shift->is($shift) && $notification->clockedIn === 'Mon 23 Nov at 9:00 AM');
    Notification::assertCount(1);
});

it('reminds after twelve hours on a day with no scheduled hours', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['timezone' => 'UTC']);
    WorkScheduleEloquentModel::factory()->forTeam($user->current_team_id)->teamDefault()->weekdays('09:00', '17:00')->create();
    ShiftEloquentModel::factory()->forUser($user)->between(CarbonImmutable::parse('2026-11-28 10:00'))->create();

    expect(RemindOpenShifts::run(CarbonImmutable::parse('2026-11-28 21:59')))->toBe(0)
        ->and(RemindOpenShifts::run(CarbonImmutable::parse('2026-11-28 22:00')))->toBe(1);
});

it('leaves finished shifts alone', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    ShiftEloquentModel::factory()->forUser($user)->between(CarbonImmutable::parse('2026-11-23 09:00'), 8)->create();

    expect(RemindOpenShifts::run(CarbonImmutable::parse('2026-11-25 09:00')))->toBe(0);

    Notification::assertNothingSent();
});

it('reminds nobody in a suspended team', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['timezone' => 'UTC']);
    $shift = ShiftEloquentModel::factory()->forUser($user)->between(CarbonImmutable::parse('2026-11-28 10:00'))->create();
    TeamEloquentModel::query()->whereKey($user->current_team_id)->update(['suspended_at' => now()]);

    expect(RemindOpenShifts::run(CarbonImmutable::parse('2026-11-28 22:00')))->toBe(0)
        ->and($shift->refresh()->reminded_at)->toBeNull();

    Notification::assertNothingSent();
});
