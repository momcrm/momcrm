<?php

declare(strict_types=1);

namespace App\Portal\Actions;

use App\Favorite\FavoriteEloquentModel;
use App\Favorite\Properties\FavoritableId;
use App\Favorite\Properties\FavoritableType;
use App\Favorite\Properties\UserId;
use App\Shared\MorphMapKeyEnum;
use App\Shared\Table;
use App\TeamUser\TeamRoleEnum;
use App\Ticket\Notifications\TicketRaisedInPortalNotification;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Tells staff about a ticket raised in the portal: the people who starred its
 * customer, who are watching that customer, or else the team's owners and
 * admins, so a new ticket never goes unnoticed.
 */
final class NotifyStaffOfPortalTicket
{
    public static function send(TicketEloquentModel $ticket, string $contactName): void
    {
        $recipients = self::customerWatchers($ticket);

        if ($recipients->isEmpty()) {
            $recipients = self::ownersAndAdmins($ticket->team_id);
        }

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new TicketRaisedInPortalNotification($ticket, $contactName));
        }
    }

    /**
     * @return Collection<int, UserEloquentModel>
     */
    private static function customerWatchers(TicketEloquentModel $ticket): Collection
    {
        $userIds = FavoriteEloquentModel::query()
            ->withoutGlobalScopes()
            ->where('team_id', $ticket->team_id)
            ->where(FavoritableType::getDatabaseTableColumnName(), MorphMapKeyEnum::Customer->value)
            ->where(FavoritableId::getDatabaseTableColumnName(), $ticket->customer_id)
            ->pluck(UserId::getDatabaseTableColumnName())
            ->all();

        return UserEloquentModel::query()->whereIn('id', $userIds)->get();
    }

    /**
     * @return Collection<int, UserEloquentModel>
     */
    private static function ownersAndAdmins(string $teamId): Collection
    {
        $userIds = DB::table(Table::TeamUser->value)
            ->where('team_id', $teamId)
            ->whereIn('role', [TeamRoleEnum::Owner->value, TeamRoleEnum::Admin->value])
            ->pluck('user_id')
            ->all();

        return UserEloquentModel::query()->whereIn('id', $userIds)->get();
    }
}
