<?php

declare(strict_types=1);

use App\Inbox\InboxChannel;
use App\User\UserEloquentModel;

it("lets only the inbox's owner listen", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $someoneElse = UserEloquentModel::factory()->withPersonalTeam()->create();

    expect(new InboxChannel()->join($user, $user->id))->toBeTrue()
        ->and(new InboxChannel()->join($user, $someoneElse->id))->toBeFalse();
});
