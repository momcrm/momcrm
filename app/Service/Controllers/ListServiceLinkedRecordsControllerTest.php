<?php

declare(strict_types=1);

use App\CustomField\CustomFieldEloquentModel;
use App\CustomField\CustomFieldRelationEnum;
use App\CustomObject\CustomObjectEloquentModel;
use App\Record\RecordEloquentModel;
use App\Service\ServiceEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('lists the records that link to a service', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['onboarded_at' => now()]);
    $team = ['team_id' => $user->current_team_id];
    $service = ServiceEloquentModel::factory()->create($team);
    $vehicles = CustomObjectEloquentModel::factory()->create([...$team, 'name_plural' => 'Vehicles']);
    $link = CustomFieldEloquentModel::factory()->forObject($vehicles)->linkingTo(CustomFieldRelationEnum::Service)->create(['name' => 'Kept at']);
    RecordEloquentModel::factory()->of($vehicles)->create(['name' => 'Van', 'custom_fields' => [$link->id => $service->id]]);

    actingAs($user)
        ->get(route('services.linked', $service->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('services/workspace/linked')
            ->where('linkedRecords.0.source', 'Vehicles')
            ->where('linkedRecords.0.field', 'Kept at')
            ->where('linkedRecords.0.records.0.title', 'Van')
            ->where('workspace.counts.linked', 1),
        );
});
