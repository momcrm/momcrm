<?php

declare(strict_types=1);

namespace App\Inbox\Actions;

use App\Favorite\FavoriteEloquentModel;
use App\Favorite\Properties\FavoritableId;
use App\Favorite\Properties\FavoritableType;
use App\Favorite\Properties\UserId;
use App\Shared\MorphMapKeyEnum;
use App\Shared\Table;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;
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
     * Sends a notification about something the customer did, such as raising
     * the ticket or replying to it. While nobody is assigned the ticket, the
     * people who should pick it up are told as well as its watchers, so it
     * never goes unnoticed.
     */
    public static function sendFromCustomer(TicketEloquentModel $ticket, Notification $notification): void
    {
        $recipients = self::watchers($ticket);

        if ($ticket->assignee_id === null) {
            $recipients = $recipients->merge(self::pickers($ticket));
        }

        if ($recipients->isNotEmpty()) {
            Notifier::send($recipients, $notification);
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

    /**
     * Who should pick up a ticket nobody is assigned: the members of its
     * group and whoever starred its customer, or everyone in the team when
     * that is nobody. Only people still in the team are counted.
     *
     * @return Collection<int, UserEloquentModel>
     */
    public static function pickers(TicketEloquentModel $ticket): Collection
    {
        $teamMemberIds = DB::table(Table::TeamUser->value)
            ->where('team_id', $ticket->team_id)
            ->select('user_id');

        $groupMemberIds = DB::table(Table::GroupUser->value)
            ->where('group_id', $ticket->group_id)
            ->select('user_id');

        $customerWatcherIds = FavoriteEloquentModel::query()
            ->withoutGlobalScopes()
            ->where('team_id', $ticket->team_id)
            ->where(FavoritableType::getDatabaseTableColumnName(), MorphMapKeyEnum::Customer->value)
            ->where(FavoritableId::getDatabaseTableColumnName(), $ticket->customer_id)
            ->select(UserId::getDatabaseTableColumnName());

        $chosen = UserEloquentModel::query()
            ->whereIn('id', $teamMemberIds)
            ->where(fn (Builder $query): Builder => $query->whereIn('id', $groupMemberIds)->orWhereIn('id', $customerWatcherIds))
            ->get();

        return $chosen->isNotEmpty() ? $chosen : UserEloquentModel::query()->whereIn('id', $teamMemberIds)->get();
    }
}
