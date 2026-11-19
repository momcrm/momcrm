<?php

declare(strict_types=1);

use App\Service\ServiceEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('turns several services off, leaving archived ones alone', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $user->current_team_id];
    $on = ServiceEloquentModel::factory()->create($team);
    $alreadyOff = ServiceEloquentModel::factory()->inactive()->create($team);

    actingAs($user)
        ->post(route('services.bulk.change'), ['service_ids' => [$on->id, $alreadyOff->id], 'action' => 'deactivate'])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => '2 services turned off.']);

    expect($on->fresh()->status)->toBe('inactive')
        ->and($alreadyOff->fresh()->status)->toBe('inactive');
});

it('archives and restores several services', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $services = ServiceEloquentModel::factory()->count(2)->create(['team_id' => $user->current_team_id, 'auto_renew' => true]);

    actingAs($user)
        ->post(route('services.bulk.change'), ['service_ids' => $services->modelKeys(), 'action' => 'archive'])
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => '2 services archived.']);

    expect($services->map->fresh()->every(fn (ServiceEloquentModel $service): bool => $service->archived_at !== null && ! $service->auto_renew))->toBeTrue();

    actingAs($user)
        ->post(route('services.bulk.change'), ['service_ids' => [$services->first()->id], 'action' => 'restore'])
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => '1 service restored.']);

    expect($services->first()->fresh()->archived_at)->toBeNull();
});

it('refuses an unknown action', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $service = ServiceEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->post(route('services.bulk.change'), ['service_ids' => [$service->id], 'action' => 'explode'])
        ->assertSessionHasErrors('action');
});
