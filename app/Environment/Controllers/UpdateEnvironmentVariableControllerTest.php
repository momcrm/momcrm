<?php

declare(strict_types=1);

use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->file = temporaryEnvironmentFile("APP_KEY=base64:secret\nMAIL_HOST=smtp.example.com\nMAIL_PASSWORD=hunter2\n");
});

it('rewrites only the variable\'s line', function (): void {
    actingAs(UserEloquentModel::factory()->platformAdmin()->create())
        ->patch(route('environment.variables.update', 'MAIL_PASSWORD'), ['value' => 'correct horse'])
        ->assertRedirectToRoute('environment.edit')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'MAIL_PASSWORD saved. Apply the environment for it to take effect.']);

    expect(file_get_contents($this->file->path))
        ->toBe("APP_KEY=base64:secret\nMAIL_HOST=smtp.example.com\nMAIL_PASSWORD=\"correct horse\"\n");
});

it('never changes the app key, and 404s for a missing variable', function (): void {
    $admin = UserEloquentModel::factory()->platformAdmin()->create();

    actingAs($admin)->patch(route('environment.variables.update', 'APP_KEY'), ['value' => 'x'])->assertForbidden();
    actingAs($admin)->patch(route('environment.variables.update', 'MISSING'), ['value' => 'x'])->assertNotFound();

    expect($this->file->get('APP_KEY'))->toBe('base64:secret');
});

it('forbids anyone who is not a platform admin, before saying whether a variable exists', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($owner)->patch(route('environment.variables.update', 'MAIL_HOST'), ['value' => 'x'])->assertForbidden();
    actingAs($owner)->patch(route('environment.variables.update', 'MISSING'), ['value' => 'x'])->assertForbidden();

    expect($this->file->get('MAIL_HOST'))->toBe('smtp.example.com');
});
