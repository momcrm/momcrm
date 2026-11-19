<?php

declare(strict_types=1);

use App\Activity\ActivityEloquentModel;
use App\Service\ServiceEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('archives a service, stopping it renewing, and records it', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $service = ServiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Backup', 'auto_renew' => true]);

    actingAs($user)
        ->post(route('services.archive', $service->id))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Backup archived.']);

    expect($service->fresh())
        ->archived_at->not->toBeNull()
        ->auto_renew->toBeFalse()
        ->and(ActivityEloquentModel::query()->where('type', 'service_archived')->where('subject_id', $service->id)->exists())->toBeTrue();
});

it('returns not found for a service that is already archived', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $service = ServiceEloquentModel::factory()->archived()->create(['team_id' => $user->current_team_id]);

    actingAs($user)->post(route('services.archive', $service->id))->assertNotFound();
});
