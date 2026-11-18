<?php

declare(strict_types=1);

use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    temporaryEnvironmentFile("APP_KEY=base64:secret\nMAIL_PASSWORD=hunter2\n");
});

it('reveals a secret, uncached', function (): void {
    actingAs(UserEloquentModel::factory()->platformAdmin()->create())
        ->postJson(route('environment.variables.reveal', 'MAIL_PASSWORD'))
        ->assertOk()
        ->assertExactJson(['value' => 'hunter2'])
        ->assertHeader('Cache-Control', 'no-store, private');
});

it('never reveals the app key', function (): void {
    actingAs(UserEloquentModel::factory()->platformAdmin()->create())
        ->postJson(route('environment.variables.reveal', 'APP_KEY'))
        ->assertForbidden()
        ->assertJsonMissing(['value' => 'base64:secret']);
});

it('forbids anyone who is not a platform admin', function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->postJson(route('environment.variables.reveal', 'MAIL_PASSWORD'))
        ->assertForbidden()
        ->assertJsonMissing(['value' => 'hunter2']);
});
