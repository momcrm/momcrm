<?php

declare(strict_types=1);

use App\Service\ServiceEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('turns auto-renew on and off', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $service = ServiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'example.com']);

    actingAs($user)
        ->put(route('services.auto-renew', $service->id), ['auto_renew' => true])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'example.com will renew by itself.']);

    expect($service->fresh()->auto_renew)->toBeTrue();

    actingAs($user)->put(route('services.auto-renew', $service->id), ['auto_renew' => false])->assertSessionHasNoErrors();

    expect($service->fresh()->auto_renew)->toBeFalse();
});

it('refuses to turn it on for a service that is off or archived', function (Closure $service): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $service = $service($user->current_team_id);

    actingAs($user)
        ->put(route('services.auto-renew', $service->id), ['auto_renew' => true])
        ->assertSessionHasErrors(['auto_renew' => 'Turn the service on before it can renew by itself.']);

    expect($service->fresh()->auto_renew)->toBeFalse();
})->with([
    'off' => [fn (string $team) => ServiceEloquentModel::factory()->inactive()->create(['team_id' => $team])],
    'archived' => [fn (string $team) => ServiceEloquentModel::factory()->archived()->create(['team_id' => $team])],
]);
