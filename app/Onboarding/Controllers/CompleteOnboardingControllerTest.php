<?php

declare(strict_types=1);

use App\Activity\ActivityEloquentModel;
use App\Activity\ActivityTypeEnum;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travelTo;

it('finishes onboarding and opens the dashboard', function (): void {
    travelTo('2026-11-14 09:30:00');
    $user = UserEloquentModel::factory()->notOnboarded()->withPersonalTeam()->create();

    $response = actingAs($user)->post(route('onboarding.complete'));

    $response->assertRedirectToRoute('dashboard');
    expect($user->fresh()->onboarded_at?->toDateTimeString())->toBe('2026-11-14 09:30:00');
    expect(ActivityEloquentModel::query()->where('type', ActivityTypeEnum::OnboardingCompleted->value)->sole())
        ->user_id->toBe($user->id)
        ->team_id->toBe($user->current_team_id);
});
