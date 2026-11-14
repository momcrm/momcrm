<?php

declare(strict_types=1);

namespace App\Ticket\Controllers;

use App\Contact\Actions\FindContact;
use App\Customer\Actions\FindCustomer;
use App\Location\Actions\FindLocation;
use App\Ticket\Actions\FindTicket;
use App\Ticket\Exceptions\CouldNotFindTicket;
use App\Ticket\TicketPriorityEnum;
use App\Ticket\TicketStatusEnum;
use App\User\Actions\FindUser;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ShowTicketController
{
    /**
     * @throws CouldNotFindTicket
     */
    public function __invoke(Request $request, string $ticket): Response
    {
        $teamId = (string) $request->user()?->current_team_id;
        $found = FindTicket::byIdForTeam($ticket, $teamId);

        if ($found->isNull()) {
            throw CouldNotFindTicket::notFound();
        }

        $contact = FindContact::byIdForTeam($found->getContactId()->toNullableString(), $teamId);
        $location = FindLocation::byIdForTeam($found->getLocationId()->toNullableString(), $teamId);

        return Inertia::render('tickets/show', [
            'ticket' => $found->toSummary(),
            'customer' => FindCustomer::byIdForTeam($found->getCustomerId()->toString(), $teamId)->toSummary(),
            'contact' => $contact->isNull() ? null : $contact->toSummary(),
            'location' => $location->isNull() ? null : $location->toSummary(),
            'assigneeName' => FindUser::byId($found->getAssigneeId()->toNullableString())->getName()->toNullableString(),
            'statuses' => TicketStatusEnum::options(),
            'priorities' => TicketPriorityEnum::options(),
        ]);
    }
}
