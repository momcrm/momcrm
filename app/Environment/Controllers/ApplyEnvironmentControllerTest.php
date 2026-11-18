<?php

declare(strict_types=1);

use App\Environment\EnvironmentVariables;
use App\User\UserEloquentModel;
use Illuminate\Support\Facades\Cache;

use function Pest\Laravel\actingAs;

it('restarts the queue workers and clears the pending changes', function (): void {
    EnvironmentVariables::markChanged();
    $this->travel(1)->seconds();

    actingAs(UserEloquentModel::factory()->platformAdmin()->create())
        ->post(route('environment.apply'))
        ->assertRedirectToRoute('environment.edit')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Web requests already read the environment file. Queue workers restart after their current job.']);

    expect(Cache::get('illuminate:queue:restart'))->not->toBeNull()
        ->and(EnvironmentVariables::hasPendingChanges())->toBeFalse();
});

it('forbids anyone who is not a platform admin', function (): void {
    EnvironmentVariables::markChanged();

    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->post(route('environment.apply'))
        ->assertForbidden();

    expect(EnvironmentVariables::hasPendingChanges())->toBeTrue();
});
