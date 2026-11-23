<?php

declare(strict_types=1);

use App\Shift\ShiftEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\Timesheet\Notifications\TimesheetReviewedNotification;
use App\Timesheet\TimesheetEloquentModel;
use App\User\UserEloquentModel;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travelTo;

beforeEach(function (): void {
    travelTo('2026-11-23 12:00:00');
    Notification::fake();
});

it('marks a submitted timesheet approved and tells the member', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create(['timezone' => 'UTC']);
    ShiftEloquentModel::factory()->forUser($member)->between(CarbonImmutable::parse('2026-11-17 09:00'), 8)->create();
    $timesheet = TimesheetEloquentModel::factory()->forUser($member)->period('2026-11-16')->create();

    actingAs($owner)->post(route('timesheets.approve', $timesheet->id), ['review_note' => 'Thanks'])->assertSessionHasNoErrors();

    $timesheet->refresh();

    expect($timesheet->status)->toBe('approved')
        ->and($timesheet->reviewed_by)->toBe($owner->id)
        ->and($timesheet->review_note)->toBe('Thanks')
        ->and($timesheet->totals['worked_seconds'] ?? null)->toBe(8 * 3600);

    Notification::assertSentTo($member, TimesheetReviewedNotification::class, fn (TimesheetReviewedNotification $notification): bool => $notification->toArray($member)['message'] === 'approved your timesheet');
});

it('leaves a timesheet that isn’t waiting alone', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $timesheet = TimesheetEloquentModel::factory()->forUser($owner)->approved()->create();

    actingAs($owner)->post(route('timesheets.approve', $timesheet->id))->assertSessionHasErrors('timesheet');

    expect($timesheet->refresh()->status)->toBe('approved');
});

it('refuses members, even for their own', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();
    $timesheet = TimesheetEloquentModel::factory()->forUser($member)->create();

    actingAs($member)->post(route('timesheets.approve', $timesheet->id))->assertForbidden();

    expect($timesheet->refresh()->status)->toBe('submitted');
});

it('does not find another team’s timesheet', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $theirs = TimesheetEloquentModel::factory()->create();

    actingAs($owner)->post(route('timesheets.approve', $theirs->id))->assertNotFound();

    expect($theirs->refresh()->status)->toBe('submitted');
});
