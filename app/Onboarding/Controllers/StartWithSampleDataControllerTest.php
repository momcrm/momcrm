<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

it('fills the workspace with sample data and finishes onboarding', function (): void {
    Storage::fake('local');
    $user = UserEloquentModel::factory()->notOnboarded()->withPersonalTeam()->create();

    actingAs($user)
        ->post(route('onboarding.start.sample'))
        ->assertRedirectToRoute('dashboard');

    expect(CustomerEloquentModel::query()->where('team_id', $user->current_team_id)->where('is_sample', true)->count())->toBe(3)
        ->and($user->fresh()->onboarded_at)->not->toBeNull();
});
