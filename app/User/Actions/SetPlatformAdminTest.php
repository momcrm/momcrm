<?php

declare(strict_types=1);

use App\User\UserEloquentModel;

use function Pest\Laravel\artisan;

it('makes someone a platform admin from the command line, and takes it away', function (): void {
    $user = UserEloquentModel::factory()->create(['email' => 'ops@acme.test']);

    artisan('users:platform-admin', ['email' => 'ops@acme.test'])
        ->expectsOutputToContain('ops@acme.test is now a platform admin.')
        ->assertSuccessful();

    expect($user->fresh()?->is_platform_admin)->toBeTrue();

    artisan('users:platform-admin', ['email' => 'ops@acme.test', '--revoke' => true])->assertSuccessful();

    expect($user->fresh()?->is_platform_admin)->toBeFalse();
});

it('fails for an unknown email address', function (): void {
    artisan('users:platform-admin', ['email' => 'nobody@acme.test'])
        ->expectsOutputToContain('Nobody has the email address nobody@acme.test.')
        ->assertFailed();
});
