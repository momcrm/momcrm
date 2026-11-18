<?php

declare(strict_types=1);

use App\Connector\Actions\GetConnectorAccessToken;
use App\Connector\Connector;
use App\Connector\ConnectorEloquentModel;
use App\Connector\Exceptions\CouldNotReachConnector;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('disconnects a connector and forgets its access', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $model = ConnectorEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'account' => 'sam@acme.test']);
    $connector = Connector::fromEloquentModel($model);
    GetConnectorAccessToken::remember($connector, 'access-token', 3600);

    actingAs($owner)
        ->delete(route('connectors.destroy', $model->id))
        ->assertRedirectToRoute('connectors.index')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'sam@acme.test disconnected.']);

    expect($model->fresh())->toBeNull()
        ->and(fn () => GetConnectorAccessToken::forConnector($connector))->toThrow(CouldNotReachConnector::class);
});

it('forbids members and hides other teams\' connectors', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();
    $connector = ConnectorEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);
    $theirs = ConnectorEloquentModel::factory()->create();

    actingAs($member)->delete(route('connectors.destroy', $connector->id))->assertForbidden();
    actingAs($owner)->delete(route('connectors.destroy', $theirs->id))->assertNotFound();

    expect($connector->fresh())->not->toBeNull()
        ->and($theirs->fresh())->not->toBeNull();
});
