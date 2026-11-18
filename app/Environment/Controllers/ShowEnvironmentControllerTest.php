<?php

declare(strict_types=1);

use App\Environment\EnvironmentVariables;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    temporaryEnvironmentFile("APP_KEY=base64:secret\nMAIL_HOST=smtp.example.com\nMAIL_PASSWORD=hunter2\nMAIL_FROM_NAME=\n");
});

it('lists the variables in file order with secrets and the app key masked', function (): void {
    $admin = UserEloquentModel::factory()->platformAdmin()->withPersonalTeam()->create();
    EnvironmentVariables::markChanged();

    actingAs($admin)
        ->get(route('environment.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/environment')
            ->where('canManageEnvironment', true)
            ->where('variables', [
                ['key' => 'APP_KEY', 'value' => null, 'is_secret' => true, 'is_locked' => true, 'is_empty' => false],
                ['key' => 'MAIL_HOST', 'value' => 'smtp.example.com', 'is_secret' => false, 'is_locked' => false, 'is_empty' => false],
                ['key' => 'MAIL_PASSWORD', 'value' => null, 'is_secret' => true, 'is_locked' => false, 'is_empty' => false],
                ['key' => 'MAIL_FROM_NAME', 'value' => '', 'is_secret' => false, 'is_locked' => false, 'is_empty' => true],
            ])
            ->where('meta.config_cached', false)
            ->where('meta.has_pending_changes', true),
        );
});

it('forbids everyone but platform admins, team owners included', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($owner)->get(route('environment.edit'))->assertForbidden();
    actingAs($owner)
        ->get(route('profile.edit'))
        ->assertInertia(fn (Assert $page) => $page->where('canManageEnvironment', false));
});
