<?php

declare(strict_types=1);

namespace App\Ticket;

use App\CustomField\CustomFieldRelationLabels;
use App\CustomField\CustomFieldTargetEnum;
use App\Favorite\Actions\FindFavorite;
use App\Favorite\FavoritableTypeEnum;
use App\Ticket\Actions\FindTicket;
use App\Ticket\Controllers\ListTicketsController;
use App\Ticket\Events\TicketChanged;
use App\User\UserEloquentModel;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * The props every page with ticket views (list, board, table and the drawer)
 * needs: the filtered tickets, the filters, the person's stars, the pickers'
 * options and the opened ticket.
 */
final class TicketViewProps
{
    private const int TABLE_PAGE_SIZE = 25;

    private const int BOARD_LIMIT = 500;

    /**
     * A busy team changes tickets often; each open list reloads at most this
     * often, however many changes arrive.
     */
    private const int LIVE_THROTTLE_MS = 2000;

    /**
     * @return array<string, mixed>
     */
    public static function for(Request $request, TicketListFilters $filters): array
    {
        /** @var UserEloquentModel $user */
        $user = $request->user();
        $teamId = (string) $user->current_team_id;
        $perPage = $filters->view === TicketListFilters::VIEW_TABLE ? self::TABLE_PAGE_SIZE : self::BOARD_LIMIT;

        return [
            'tickets' => TicketChanged::liveForTeam(
                $teamId,
                fn (): LengthAwarePaginator => self::tickets($teamId, $user->id, $filters, $perPage),
                throttle: self::LIVE_THROTTLE_MS,
            ),
            'filters' => $filters->toArray(),
            'favoriteTicketIds' => FindFavorite::idsForUser($teamId, $user->id, FavoritableTypeEnum::Ticket),
            ...TicketDrawer::options($teamId, $user->id),
            'drawer' => TicketDrawer::forRequest($request),
        ];
    }

    /**
     * @return LengthAwarePaginator<int, non-empty-array<string, mixed>>
     */
    private static function tickets(string $teamId, string $userId, TicketListFilters $filters, int $perPage): LengthAwarePaginator
    {
        $tickets = FindTicket::paginateFiltered($teamId, $userId, $filters, $perPage);
        $labels = CustomFieldRelationLabels::for(
            $teamId,
            CustomFieldTargetEnum::Ticket,
            ...array_map(fn (Ticket $ticket): array => $ticket->getCustomFields()->toValues(), $tickets->items()),
        );

        return $tickets->through(fn (Ticket $ticket): array => [
            ...ListTicketsController::listItem($ticket),
            'custom_field_labels' => CustomFieldRelationLabels::only($labels, $ticket->getCustomFields()->toValues()),
        ]);
    }
}
