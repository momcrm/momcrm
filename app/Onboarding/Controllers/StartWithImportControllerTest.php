<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Http\UploadedFile;

use function Pest\Laravel\actingAs;

it('imports customers and finishes onboarding', function (): void {
    $user = UserEloquentModel::factory()->notOnboarded()->withPersonalTeam()->create();
    $file = UploadedFile::fake()->createWithContent('customers.csv', "Company,Email\nAcme IT,hi@acme.test\n");

    actingAs($user)
        ->post(route('onboarding.start.import'), ['file' => $file])
        ->assertRedirectToRoute('customers.index');

    expect(CustomerEloquentModel::query()->sole()->name)->toBe('Acme IT')
        ->and($user->fresh()->onboarded_at)->not->toBeNull();
});

it('keeps the user in onboarding when the file cannot be imported', function (): void {
    $user = UserEloquentModel::factory()->notOnboarded()->withPersonalTeam()->create();
    $file = UploadedFile::fake()->createWithContent('customers.csv', "Email\nhi@acme.test\n");

    actingAs($user)
        ->post(route('onboarding.start.import'), ['file' => $file])
        ->assertSessionHasErrors('file');

    expect($user->fresh()->onboarded_at)->toBeNull();
});
