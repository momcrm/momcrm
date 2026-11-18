<?php

declare(strict_types=1);

use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->file = temporaryEnvironmentFile("APP_KEY=base64:secret\nMAIL_HOST=smtp.example.com\n");
});

it('removes the variable and backs the old file up', function (): void {
    actingAs(UserEloquentModel::factory()->platformAdmin()->create())
        ->delete(route('environment.variables.destroy', 'MAIL_HOST'))
        ->assertRedirectToRoute('environment.edit')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'MAIL_HOST removed. Apply the environment for it to take effect.']);

    expect(file_get_contents($this->file->path))->toBe("APP_KEY=base64:secret\n")
        ->and(glob($this->file->backupDirectory.'/.env.*'))->toHaveCount(1);
});

it('never removes the app key', function (): void {
    actingAs(UserEloquentModel::factory()->platformAdmin()->create())
        ->delete(route('environment.variables.destroy', 'APP_KEY'))
        ->assertForbidden();

    expect($this->file->has('APP_KEY'))->toBeTrue();
});

it('forbids anyone who is not a platform admin', function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->delete(route('environment.variables.destroy', 'MAIL_HOST'))
        ->assertForbidden();

    expect($this->file->has('MAIL_HOST'))->toBeTrue();
});
