<?php

declare(strict_types=1);

use App\Service\ServiceEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it("shows a service's own activity and changes, not another's", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $user->current_team_id];
    $service = ServiceEloquentModel::factory()->create([...$team, 'name' => 'Backup']);
    $other = ServiceEloquentModel::factory()->create($team);

    actingAs($user)->post(route('services.deactivate', $service->id));
    actingAs($user)->post(route('services.deactivate', $other->id));

    actingAs($user)
        ->get(route('services.activity', $service->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('services/workspace/activity')
            ->has('activities.data', 1)
            ->where('activities.data.0.type', 'service_deactivated')
            ->where('activities.data.0.label', 'Turned a service off')
            ->where('auditLogs.data.0.event', 'updated')
            ->where('auditLogs.data.0.subject', 'service'),
        );
});
