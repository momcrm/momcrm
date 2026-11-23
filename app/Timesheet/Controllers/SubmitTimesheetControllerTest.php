<?php

declare(strict_types=1);

use App\TeamUser\TeamRoleEnum;
use App\Timesheet\Notifications\TimesheetSubmittedNotification;
use App\Timesheet\TimesheetEloquentModel;
use App\Timesheet\TimesheetStatusEnum;
use App\User\UserEloquentModel;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travelTo;

beforeEach(function (): void {
    travelTo('2026-11-23 12:00:00');
    Notification::fake();
});

it('submits the member’s timesheet to the owners and admins', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create(['timezone' => 'UTC']);

    actingAs($member)->post(route('timesheets.submit'), ['starts_on' => '2026-11-16'])->assertSessionHasNoErrors();

    $timesheet = TimesheetEloquentModel::query()->sole();

    expect($timesheet->user_id)->toBe($member->id)
        ->and($timesheet->starts_on)->toStartWith('2026-11-16')
        ->and($timesheet->ends_on)->toStartWith('2026-11-22')
        ->and($timesheet->status)->toBe('submitted');

    Notification::assertSentTo($owner, TimesheetSubmittedNotification::class, fn (TimesheetSubmittedNotification $notification): bool => $notification->period === 'Mon 16 Nov – Sun 22 Nov');
    Notification::assertNotSentTo($member, TimesheetSubmittedNotification::class);
});

it('submits a timesheet that was sent back again, clearing the old review', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create(['timezone' => 'UTC']);
    $timesheet = TimesheetEloquentModel::factory()->forUser($member)->period('2026-11-16')->status(TimesheetStatusEnum::Rejected)->create(['reviewed_by' => $owner->id, 'review_note' => 'Missing Friday']);

    actingAs($member)->post(route('timesheets.submit'), ['starts_on' => '2026-11-16'])->assertSessionHasNoErrors();

    $timesheet->refresh();

    expect($timesheet->status)->toBe('submitted')
        ->and($timesheet->review_note)->toBeNull()
        ->and($timesheet->reviewed_by)->toBeNull()
        ->and(TimesheetEloquentModel::query()->count())->toBe(1);
});

it('refuses a period that isn’t one, hasn’t started or was already submitted', function (string $startsOn, ?TimesheetStatusEnum $existing): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['timezone' => 'UTC']);

    if ($existing !== null) {
        TimesheetEloquentModel::factory()->forUser($user)->period($startsOn)->status($existing)->create();
    }

    actingAs($user)->post(route('timesheets.submit'), ['starts_on' => $startsOn])->assertSessionHasErrors('starts_on');

    expect(TimesheetEloquentModel::query()->where('status', 'submitted')->count())->toBe($existing === TimesheetStatusEnum::Submitted ? 1 : 0);
})->with([
    'not a period start' => ['2026-11-18', null],
    'next week' => ['2026-11-30', null],
    'already submitted' => ['2026-11-16', TimesheetStatusEnum::Submitted],
    'already approved' => ['2026-11-16', TimesheetStatusEnum::Approved],
]);
