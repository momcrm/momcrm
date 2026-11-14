<?php

declare(strict_types=1);

use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('recommends a starting point from what brought the user here', function (?string $useCase, string $recommended): void {
    actingAs(UserEloquentModel::factory()->notOnboarded()->withPersonalTeam()->create(['onboarding_use_case' => $useCase]))
        ->get(route('onboarding.start'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('onboarding/start')->where('recommended', $recommended));
})->with([
    'managed services' => ['managed_services', 'sample'],
    'agency' => ['agency', 'sample'],
    'selling to businesses' => ['b2b_sales', 'import'],
    'something else' => ['other', 'blank'],
    'skipped' => [null, 'sample'],
]);
