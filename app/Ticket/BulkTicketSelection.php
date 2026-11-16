<?php

declare(strict_types=1);

namespace App\Ticket;

use App\Ticket\Actions\FindTicket;
use App\Ticket\Exceptions\CouldNotFindTicket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * The live tickets picked in a ticket view for a bulk action. Every one must
 * be a live ticket of the team the person can see, or none are touched.
 */
final class BulkTicketSelection
{
    public const string TICKET_IDS = 'ticket_ids';

    public const int MAX = 200;

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            self::TICKET_IDS => ['required', 'array', 'min:1', 'max:'.self::MAX],
            self::TICKET_IDS.'.*' => ['uuid', 'distinct'],
        ];
    }

    /**
     * The picked tickets; none when the list itself is invalid, so the
     * rules can report it.
     *
     * @throws CouldNotFindTicket
     */
    public static function load(FormRequest $request): TicketCollection
    {
        $input = $request->input(self::TICKET_IDS);
        $ids = array_values(array_unique(array_filter(
            is_array($input) ? $input : [],
            fn (mixed $id): bool => is_string($id) && Str::isUuid($id),
        )));

        if ($ids === [] || count($ids) > self::MAX) {
            return new TicketCollection([]);
        }

        $tickets = FindTicket::byIdsForTeam((string) $request->user()?->current_team_id, $ids);

        if ($tickets->count() !== count($ids)) {
            throw CouldNotFindTicket::notFound();
        }

        return $tickets;
    }
}
