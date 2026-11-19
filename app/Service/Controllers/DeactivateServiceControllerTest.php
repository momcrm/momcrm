<?php

declare(strict_types=1);

use App\Activity\ActivityEloquentModel;
use App\Service\ServiceEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('turns a service off, stopping it renewing, and records it', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $service = ServiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Backup', 'auto_renew' => true]);

    actingAs($user)
        ->post(route('services.deactivate', $service->id))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Backup turned off.']);

    expect($service->fresh())
        ->status->toBe('inactive')
        ->auto_renew->toBeFalse()
        ->and(ActivityEloquentModel::query()->where('type', 'service_deactivated')->where('subject_id', $service->id)->exists())->toBeTrue();
});

it("returns not found for another team's service", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->post(route('services.deactivate', ServiceEloquentModel::factory()->create()->id))
        ->assertNotFound();
});
