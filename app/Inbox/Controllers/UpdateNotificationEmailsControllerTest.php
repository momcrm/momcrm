<?php

declare(strict_types=1);

use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('saves which kinds the person is emailed', function (array $chosen): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($user)
        ->put(route('notification-settings.update'), ['emailed_notifications' => $chosen])
        ->assertRedirectToRoute('notification-settings.edit')
        ->assertSessionHasNoErrors();

    expect($user->refresh()->emailed_notifications)->toEqual($chosen);
})->with([
    'some kinds' => [['comment', 'update']],
    'nothing' => [[]],
]);

it('rejects a kind it does not know, and saves nothing', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($user)
        ->put(route('notification-settings.update'), ['emailed_notifications' => ['mention', 'everything']])
        ->assertSessionHasErrors(['emailed_notifications.1' => 'The selected emailed_notifications.1 is invalid.']);

    expect($user->refresh()->emailed_notifications)->toBeNull();
});
