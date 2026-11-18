<?php

declare(strict_types=1);

use App\Environment\EnvironmentVariables;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('appends a variable, which then waits to be applied', function (): void {
    $file = temporaryEnvironmentFile("MAIL_HOST=smtp.example.com\n");

    actingAs(UserEloquentModel::factory()->platformAdmin()->create())
        ->post(route('environment.variables.store'), ['key' => 'MAIL_FROM_NAME', 'value' => 'MomCRM Staff'])
        ->assertRedirectToRoute('environment.edit')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'MAIL_FROM_NAME added. Apply the environment for it to take effect.']);

    expect(file_get_contents($file->path))->toBe("MAIL_HOST=smtp.example.com\nMAIL_FROM_NAME=\"MomCRM Staff\"\n")
        ->and(EnvironmentVariables::hasPendingChanges())->toBeTrue();
});

it('refuses a badly formed, existing or locked key', function (string $key): void {
    $file = temporaryEnvironmentFile("APP_KEY=base64:secret\nMAIL_HOST=smtp.example.com\n");

    actingAs(UserEloquentModel::factory()->platformAdmin()->create())
        ->post(route('environment.variables.store'), ['key' => $key, 'value' => 'x'])
        ->assertSessionHasErrors('key');

    expect(file_get_contents($file->path))->toBe("APP_KEY=base64:secret\nMAIL_HOST=smtp.example.com\n");
})->with(['lower-case', 'MAIL_HOST', 'APP_KEY']);

it('forbids anyone who is not a platform admin', function (): void {
    $file = temporaryEnvironmentFile("MAIL_HOST=smtp.example.com\n");

    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->post(route('environment.variables.store'), ['key' => 'NEW_KEY', 'value' => 'x'])
        ->assertForbidden();

    expect($file->has('NEW_KEY'))->toBeFalse();
});
