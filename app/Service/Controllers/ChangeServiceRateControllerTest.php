<?php

declare(strict_types=1);

use App\Activity\ActivityEloquentModel;
use App\Rate\RateEloquentModel;
use App\Service\ServiceEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('moves a service to another rate and records the change', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $user->current_team_id];
    $old = RateEloquentModel::factory()->create([...$team, 'name' => 'Basic']);
    $new = RateEloquentModel::factory()->create([...$team, 'name' => 'Premium']);
    $service = ServiceEloquentModel::factory()->create([...$team, 'name' => 'Support', 'rate_id' => $old->id]);

    actingAs($user)
        ->put(route('services.rate', $service->id), ['rate_id' => $new->id])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Support is now on Premium.']);

    expect($service->fresh()->rate_id)->toBe($new->id)
        ->and(ActivityEloquentModel::query()->where('type', 'service_rate_changed')->sole()->details)->toEqual(['name' => 'Support', 'from' => 'Basic', 'to' => 'Premium']);
});

it("refuses an archived rate or another team's", function (Closure $rate): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $service = ServiceEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->put(route('services.rate', $service->id), ['rate_id' => $rate($user->current_team_id)->id])
        ->assertSessionHasErrors(['rate_id' => 'Choose one of your rates.']);
})->with([
    'archived' => [fn (string $team) => RateEloquentModel::factory()->archived()->create(['team_id' => $team])],
    "another team's" => [fn () => RateEloquentModel::factory()->create()],
]);
