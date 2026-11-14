<?php

declare(strict_types=1);

use App\EmailCode\EmailCodePurposeEnum;
use App\EmailCode\Notifications\EmailCodeNotification;
use App\User\UserEloquentModel;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;

it("emails the signed-in person a code to confirm it's them", function (): void {
    Notification::fake();
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['email' => 'sam@acme.test', 'password' => null]);

    actingAs($user)
        ->from(route('password.confirm'))
        ->post(route('password.confirm.code.send'))
        ->assertRedirect(route('password.confirm'));

    Notification::assertSentOnDemand(
        EmailCodeNotification::class,
        fn (EmailCodeNotification $notification, array $channels, AnonymousNotifiable $notifiable): bool => $notifiable->routes['mail'] === 'sam@acme.test'
            && $notification->purpose === EmailCodePurposeEnum::Confirm,
    );
});
