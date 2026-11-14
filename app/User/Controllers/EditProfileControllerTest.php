<?php

declare(strict_types=1);

use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('renders the profile settings page', function (): void {
    actingAs(UserEloquentModel::factory()->create())
        ->get(route('profile.edit'))
        ->assertOk();
});
