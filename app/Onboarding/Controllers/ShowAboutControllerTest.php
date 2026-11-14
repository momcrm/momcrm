<?php

declare(strict_types=1);

use App\User\OnboardingUseCaseEnum;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('shows the questions with any saved answers', function (): void {
    $user = UserEloquentModel::factory()->notOnboarded()->withPersonalTeam()->create([
        'onboarding_use_case' => OnboardingUseCaseEnum::Agency->value,
    ]);

    actingAs($user)
        ->get(route('onboarding.about'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('onboarding/about')
            ->where('answers.onboarding_use_case', 'agency')
            ->where('answers.onboarding_role', null)
            ->has('options.onboarding_use_case', 4)
            ->where('options.onboarding_use_case.0', ['value' => 'agency', 'label' => 'Running an agency or consultancy']),
        );
});

it('sends users who already finished onboarding to the dashboard', function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->get(route('onboarding.about'))
        ->assertRedirectToRoute('dashboard');
});

it('requires a verified email address', function (): void {
    actingAs(UserEloquentModel::factory()->unverified()->notOnboarded()->create())
        ->get(route('onboarding.about'))
        ->assertRedirectToRoute('verification.notice');
});

it('requires authentication', function (): void {
    get(route('onboarding.about'))->assertRedirectToRoute('login');
});
