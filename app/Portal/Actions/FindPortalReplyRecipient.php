<?php

declare(strict_types=1);

namespace App\Portal\Actions;

use App\Contact\Actions\FindContact;
use App\Contact\Contact;
use App\Ticket\Ticket;

/**
 * Who hears about staff replies through the portal when there is no mailbox
 * to email them from: the ticket's contact, if they can use the portal and
 * have an address to tell.
 */
final class FindPortalReplyRecipient
{
    public static function forTicket(Ticket $ticket): Contact
    {
        $contact = FindContact::byIdForTeam($ticket->getContactId()->toNullableString(), $ticket->getTeamId()->toString());

        return $contact->getPortalAccess()->toBoolean() && $contact->getEmail()->isNotNull() ? $contact : Contact::new();
    }
}
