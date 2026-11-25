<?php

declare(strict_types=1);

use App\Ticket\TeamTicketsChannel;
use App\User\UserEloquentModel;

it("lets only the team's members listen", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $outsider = UserEloquentModel::factory()->withPersonalTeam()->create();

    expect(new TeamTicketsChannel()->join($user, (string) $user->current_team_id))->toBeTrue()
        ->and(new TeamTicketsChannel()->join($outsider, (string) $user->current_team_id))->toBeFalse();
});
