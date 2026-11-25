<?php

declare(strict_types=1);

namespace App\Ticket\Events;

use App\Ticket\TeamTicketsChannel;
use App\Ticket\TicketChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Broadcasting\ShouldRescue;
use Inertia\Inertia;
use Inertia\LiveProp;

use function Illuminate\Support\defer;

/**
 * Tells open pages that a ticket, or something shown with it such as a
 * comment, changed, so they reload it. Only the ids are sent: each page asks
 * the server for what its reader may see.
 */
final class TicketChanged implements ShouldBroadcastNow, ShouldRescue
{
    use InteractsWithSockets;

    public const string NAME = 'ticket.changed';

    public function __construct(
        public readonly string $teamId,
        public readonly string $ticketId,
    ) {}

    /**
     * Broadcasts once the request, job or command has finished, so the change
     * is saved and every part of it (such as labels) is in. Several changes
     * to one ticket in that time send a single broadcast. The tab that made
     * the change already reloads it, so it is left out.
     */
    public static function later(string $teamId, string $ticketId): void
    {
        defer(fn () => broadcast(new self($teamId, $ticketId))->toOthers(), 'ticket-changed:'.$ticketId);
    }

    /**
     * A page prop about one ticket that reloads whenever the ticket changes.
     *
     * @param  callable(): mixed  $value
     */
    public static function liveForTicket(string $ticketId, callable $value): LiveProp
    {
        return Inertia::live($value, on: self::class, channel: TicketChannel::for($ticketId));
    }

    /**
     * A page prop about a team's tickets, such as a list, that reloads
     * whenever one of them changes.
     *
     * @param  callable(): mixed  $value
     */
    public static function liveForTeam(string $teamId, callable $value, ?int $throttle = null): LiveProp
    {
        return Inertia::live($value, on: self::class, channel: TeamTicketsChannel::for($teamId), throttle: $throttle);
    }

    /**
     * @return list<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            TicketChannel::for($this->ticketId),
            TeamTicketsChannel::for($this->teamId),
        ];
    }

    public function broadcastAs(): string
    {
        return self::NAME;
    }

    /**
     * @return array{ticket_id: string}
     */
    public function broadcastWith(): array
    {
        return ['ticket_id' => $this->ticketId];
    }
}
