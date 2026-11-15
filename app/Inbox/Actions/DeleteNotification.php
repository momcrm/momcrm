<?php

declare(strict_types=1);

namespace App\Inbox\Actions;

use App\Inbox\InboxNotifications;
use App\User\UserEloquentModel;

/**
 * Deletes one of someone's notifications.
 */
final class DeleteNotification
{
    public static function for(UserEloquentModel $user, string $id): void
    {
        InboxNotifications::query($user)->whereKey($id)->delete();
    }
}
