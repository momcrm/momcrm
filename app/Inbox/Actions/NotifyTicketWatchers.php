<?php

declare(strict_types=1);

namespace App\Inbox\Actions;

use App\Favorite\FavoriteEloquentModel;
use App\Favorite\Properties\FavoritableId;
use App\Favorite\Properties\FavoritableType;
use App\Favorite\Properties\UserId;
use App\Shared\MorphMapKeyEnum;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as Notifier;

/**
 * Sends a notification about a ticket to the people watching it: its assignee
 * and everyone who starred it. The person who caused it, and anyone listed in
 * $except, is left out.
 */
final class NotifyTicketWatchers
{
    /**
     * @param  array<int, string|null>  $except
     */
    public static function send(TicketEloquentModel $ticket, Notification $notification, ?string $actorId, array $except = []): void
    {
        $watchers = self::watchers($ticket)
            ->reject(fn (UserEloquentModel $user): bool => in_array($user->id, [$actorId, ...$except], true));

        if ($watchers->isNotEmpty()) {
            Notifier::send($watchers, $notification);
        }
    }

    /**
     * @return Collection<int, UserEloquentModel>
     */
    public static function watchers(TicketEloquentModel $ticket): Collection
    {
        $starredBy = FavoriteEloquentModel::query()
            ->withoutGlobalScopes()
            ->where(FavoritableType::getDatabaseTableColumnName(), MorphMapKeyEnum::Ticket->value)
            ->where(FavoritableId::getDatabaseTableColumnName(), $ticket->id)
            ->pluck(UserId::getDatabaseTableColumnName())
            ->all();

        return UserEloquentModel::query()
            ->whereIn('id', array_values(array_filter([$ticket->assignee_id, ...$starredBy])))
            ->get();
    }
}
