<?php

declare(strict_types=1);

namespace App\Portal;

use App\AccessRule\AccessActionEnum;
use App\AccessRule\AccessResourceEnum;
use App\AccessRule\Engine\AccessEvaluator;
use App\Contact\ContactEloquentModel;
use App\Portal\Actions\FindPortalTickets;
use Illuminate\Broadcasting\PrivateChannel;

/**
 * The private channel that tells a ticket's page in the customer portal it
 * changed. Only a contact at the ticket's customer who may see the ticket
 * listens; staff use TicketChannel.
 */
final class PortalTicketChannel
{
    public const string NAME = 'portal.tickets.{ticketId}';

    public static function for(string $ticketId): PrivateChannel
    {
        return new PrivateChannel(str_replace('{ticketId}', $ticketId, self::NAME));
    }

    public function join(ContactEloquentModel $contact, string $ticketId): bool
    {
        $ticket = FindPortalTickets::byIdForCustomer($ticketId, $contact->team_id, $contact->customer_id);

        return $ticket->isNotNull()
            && AccessEvaluator::allowsContact($contact->id, AccessResourceEnum::Ticket, AccessActionEnum::View, $ticket);
    }
}
