<?php

declare(strict_types=1);

namespace App\Portal;

use App\AccessRule\AccessActionEnum;
use App\AccessRule\AccessResourceEnum;
use App\AccessRule\Engine\AccessEvaluator;
use App\Contact\ContactEloquentModel;
use Illuminate\Broadcasting\PrivateChannel;

/**
 * The private channel that tells a customer's ticket list in the portal one
 * of their tickets changed. Only the ticket's id is sent, so every contact at
 * the customer who may see tickets listens: the list reloads what each may
 * see.
 */
final class PortalCustomerTicketsChannel
{
    public const string NAME = 'portal.customers.{customerId}.tickets';

    public static function for(string $customerId): PrivateChannel
    {
        return new PrivateChannel(str_replace('{customerId}', $customerId, self::NAME));
    }

    public function join(ContactEloquentModel $contact, string $customerId): bool
    {
        return $contact->customer_id === $customerId
            && AccessEvaluator::contactMayOnSome($contact->id, AccessResourceEnum::Ticket, AccessActionEnum::View);
    }
}
