<?php

declare(strict_types=1);

namespace App\Ticket;

use App\Favorite\Actions\FindFavorite;
use App\Favorite\FavoritableTypeEnum;
use App\Ticket\Actions\FindTicket;
use App\Ticket\Controllers\ListTicketsController;
use App\User\UserEloquentModel;
use Illuminate\Http\Request;

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
     * @return array<string, mixed>
     */
    public static function for(Request $request, TicketListFilters $filters): array
    {
        /** @var UserEloquentModel $user */
        $user = $request->user();
        $teamId = (string) $user->current_team_id;
        $perPage = $filters->view === TicketListFilters::VIEW_TABLE ? self::TABLE_PAGE_SIZE : self::BOARD_LIMIT;

        return [
            'tickets' => FindTicket::paginateFiltered($teamId, $user->id, $filters, $perPage)
                ->through(fn (TicketEloquentModel $model): array => ListTicketsController::listItem($model)),
            'filters' => $filters->toArray(),
            'favoriteTicketIds' => FindFavorite::idsForUser($teamId, $user->id, FavoritableTypeEnum::Ticket),
            ...TicketDrawer::options($teamId),
            'drawer' => TicketDrawer::forRequest($request),
        ];
    }
}
