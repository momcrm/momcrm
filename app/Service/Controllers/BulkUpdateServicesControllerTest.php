<?php

declare(strict_types=1);

use App\Activity\ActivityEloquentModel;
use App\Rate\RateEloquentModel;
use App\Service\ServiceEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('moves several services to a rate, recording each, and sets their quantity and billing period', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $user->current_team_id];
    $rate = RateEloquentModel::factory()->create($team);
    $services = ServiceEloquentModel::factory()->count(2)->create([...$team, 'billing_period_in_months' => 3]);

    actingAs($user)
        ->patch(route('services.bulk.update'), ['service_ids' => $services->modelKeys(), 'rate_id' => $rate->id, 'quantity' => 5, 'billing_period_in_months' => null])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => '2 services updated.']);

    foreach ($services as $service) {
        expect($service->fresh())
            ->rate_id->toBe($rate->id)
            ->quantity->toBe(5)
            ->billing_period_in_months->toBeNull();
    }

    expect(ActivityEloquentModel::query()->where('type', 'service_rate_changed')->count())->toBe(2);
});

it('turns auto-renew on only when every picked service is on', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $user->current_team_id];
    $on = ServiceEloquentModel::factory()->create($team);
    $off = ServiceEloquentModel::factory()->inactive()->create($team);

    actingAs($user)
        ->patch(route('services.bulk.update'), ['service_ids' => [$on->id, $off->id], 'auto_renew' => true])
        ->assertSessionHasErrors(['auto_renew' => 'Only active services can renew by themselves. Turn the others on first.']);

    expect($on->fresh()->auto_renew)->toBeFalse();
});

it('needs something to change', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $service = ServiceEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->patch(route('services.bulk.update'), ['service_ids' => [$service->id]])
        ->assertSessionHasErrors(['service_ids' => 'Choose what to change on the selected services.']);
});

it("changes nothing when one of the services is another team's", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ours = ServiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'quantity' => 1]);

    actingAs($user)
        ->patch(route('services.bulk.update'), ['service_ids' => [$ours->id, ServiceEloquentModel::factory()->create()->id], 'quantity' => 9])
        ->assertNotFound();

    expect($ours->fresh()->quantity)->toBe(1);
});
