<?php

declare(strict_types=1);

use App\Shift\ShiftEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;
use function Pest\Laravel\travelTo;

it('starts a shift dated by the member’s own day, with the IP it came from', function (): void {
    travelTo('2026-11-23 22:30:45');
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['timezone' => 'Australia/Perth']);

    actingAs($user)
        ->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])
        ->post(route('attendance.clock-in'))
        ->assertSessionHasNoErrors();

    expect(ShiftEloquentModel::query()->sole())
        ->user_id->toBe($user->id)
        ->team_id->toBe($user->current_team_id)
        ->work_date->toBe('2026-11-24')
        ->clocked_in_at->toDateTimeString()->toBe('2026-11-23 22:30:00')
        ->clocked_out_at->toBeNull()
        ->clock_in_ip->toBe('203.0.113.7');
});

it('keeps the running shift when clocking in again', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $shift = ShiftEloquentModel::factory()->forUser($user)->open()->create();

    actingAs($user)->post(route('attendance.clock-in'))->assertSessionHasNoErrors();

    expect(ShiftEloquentModel::query()->sole()->id)->toBe($shift->id);
});

it('saves the browser’s timezone for a member who has not set one', function (): void {
    travelTo('2026-11-23 22:30:00');
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['timezone' => null]);

    actingAs($user)->post(route('attendance.clock-in'), ['timezone' => 'Asia/Manila'])->assertSessionHasNoErrors();

    expect($user->refresh()->timezone)->toBe('Asia/Manila')
        ->and(ShiftEloquentModel::query()->sole()->work_date)->toBe('2026-11-24');
});

it('never replaces a timezone the member chose', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['timezone' => 'Australia/Perth']);

    actingAs($user)->post(route('attendance.clock-in'), ['timezone' => 'Asia/Manila'])->assertSessionHasNoErrors();

    expect($user->refresh()->timezone)->toBe('Australia/Perth');
});

it('rejects a timezone that does not exist', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['timezone' => null]);

    actingAs($user)->post(route('attendance.clock-in'), ['timezone' => 'Mars/Olympus'])->assertSessionHasErrors('timezone');

    expect(ShiftEloquentModel::query()->count())->toBe(0);
});

it('sends guests to sign in', function (): void {
    post(route('attendance.clock-in'))->assertRedirect(route('login'));
});
