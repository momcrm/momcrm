<?php

declare(strict_types=1);

namespace App\User\Listeners;

use App\User\Notifications\WelcomeNotification;
use App\User\UserEloquentModel;
use Illuminate\Auth\Events\Verified;

/**
 * Welcomes a new user once they have confirmed their email address.
 */
final class SendWelcomeNotification
{
    public function handle(Verified $event): void
    {
        if ($event->user instanceof UserEloquentModel) {
            $event->user->notify(new WelcomeNotification);
        }
    }
}
