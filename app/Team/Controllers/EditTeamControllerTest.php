<?php

declare(strict_types=1);

use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('renders the team settings page', function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->get(route('team.edit'))
        ->assertOk();
});
