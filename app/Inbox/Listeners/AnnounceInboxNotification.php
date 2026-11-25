<?php

declare(strict_types=1);

namespace App\Inbox\Listeners;

use App\Inbox\Events\InboxChanged;
use App\Inbox\InboxNotification;
use App\User\UserEloquentModel;
use Illuminate\Notifications\Events\NotificationSent;

/**
 * Tells someone's open pages that a notification reached their inbox, once
 * it has been stored.
 */
final class AnnounceInboxNotification
{
    public function handle(NotificationSent $event): void
    {
        if ($event->channel === 'database' && $event->notification instanceof InboxNotification && $event->notifiable instanceof UserEloquentModel) {
            InboxChanged::later($event->notifiable->id);
        }
    }
}
