<?php

declare(strict_types=1);

use App\Activity\ActivityEloquentModel;
use App\Service\ServiceEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('turns an inactive service back on and records it', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $service = ServiceEloquentModel::factory()->inactive()->create(['team_id' => $user->current_team_id, 'name' => 'Backup']);

    actingAs($user)
        ->post(route('services.activate', $service->id))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Backup turned on.']);

    expect($service->fresh()->status)->toBe('active')
        ->and(ActivityEloquentModel::query()->where('type', 'service_activated')->where('subject_id', $service->id)->exists())->toBeTrue();
});

it('returns not found for a service that is already on or archived', function (Closure $service): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($user)->post(route('services.activate', $service($user->current_team_id)->id))->assertNotFound();
})->with([
    'already on' => [fn (string $team) => ServiceEloquentModel::factory()->create(['team_id' => $team])],
    'archived' => [fn (string $team) => ServiceEloquentModel::factory()->inactive()->archived()->create(['team_id' => $team])],
]);
