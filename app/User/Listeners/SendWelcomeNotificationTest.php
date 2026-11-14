<?php

declare(strict_types=1);

use App\User\Notifications\WelcomeNotification;
use App\User\UserEloquentModel;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Notification;

it('welcomes a user once they confirm their email address', function (): void {
    Notification::fake();
    $user = UserEloquentModel::factory()->create(['name' => 'Jane Smith']);

    event(new Verified($user));

    Notification::assertSentTo($user, WelcomeNotification::class, function (WelcomeNotification $notification) use ($user): bool {
        $mail = $notification->toMail($user);

        return $mail->subject === "You're in — here's where to start"
            && $mail->greeting === 'Hi Jane Smith,'
            && $mail->actionUrl === route('dashboard');
    });
});
