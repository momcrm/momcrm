<?php

declare(strict_types=1);

use App\Team\Actions\FindTeam;
use App\Team\Actions\SuspendTeam;
use App\Team\TeamEloquentModel;

use function Pest\Laravel\travelTo;

it('suspends a team from now, and reactivates it', function (): void {
    travelTo('2026-11-24 09:00:00');
    $team = TeamEloquentModel::factory()->create();

    $suspended = SuspendTeam::suspend(FindTeam::byId($team->id));

    expect($suspended->isSuspended())->toBeTrue()
        ->and($team->fresh()?->suspended_at?->toDateTimeString())->toBe('2026-11-24 09:00:00');

    $reactivated = SuspendTeam::reactivate(FindTeam::byId($team->id));

    expect($reactivated->isSuspended())->toBeFalse()
        ->and($team->fresh()?->suspended_at)->toBeNull();
});

it('keeps the day a team was first suspended when it is suspended again', function (): void {
    travelTo('2026-11-24 09:00:00');
    $team = TeamEloquentModel::factory()->suspended()->create();
    travelTo('2026-11-30 09:00:00');

    SuspendTeam::suspend(FindTeam::byId($team->id));

    expect($team->fresh()?->suspended_at?->toDateTimeString())->toBe('2026-11-24 09:00:00');
});
