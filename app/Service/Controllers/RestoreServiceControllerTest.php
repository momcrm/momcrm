<?php

declare(strict_types=1);

use App\Activity\ActivityEloquentModel;
use App\Service\ServiceEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('restores an archived service and records it', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $service = ServiceEloquentModel::factory()->archived()->create(['team_id' => $user->current_team_id, 'name' => 'Backup']);

    actingAs($user)
        ->post(route('services.restore', $service->id))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Backup restored.']);

    expect($service->fresh()->archived_at)->toBeNull()
        ->and(ActivityEloquentModel::query()->where('type', 'service_restored')->where('subject_id', $service->id)->exists())->toBeTrue();
});

it('returns not found for a service that is not archived', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $service = ServiceEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)->post(route('services.restore', $service->id))->assertNotFound();
});
