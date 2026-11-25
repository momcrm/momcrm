<?php

declare(strict_types=1);

namespace App\Portal\Controllers;

use App\AccessRule\AccessActionEnum;
use App\AccessRule\AccessResourceEnum;
use App\Attachment\Actions\FindAttachment;
use App\Attachment\Attachment;
use App\Attachment\AttachmentValidationRules;
use App\Portal\Actions\FindPortalTickets;
use App\Portal\PortalAccess;
use App\Portal\PortalTicketView;
use App\Ticket\Events\TicketChanged;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A ticket as the customer sees it: what they asked, the replies they were
 * sent and what they wrote back, and the files shared with them.
 */
final class ShowPortalTicketController
{
    public function __invoke(string $ticket): Response
    {
        $found = FindPortalTickets::visibleToContact($ticket);
        $statuses = FindPortalTickets::statuses($found->getTeamId()->toString());
        $item = PortalTicketView::item($found, $statuses);

        $ticketId = $found->getId()->toString();
        $can = [
            'reply' => PortalAccess::allows(AccessResourceEnum::Comment, AccessActionEnum::Create),
            'attach' => PortalAccess::allows(AccessResourceEnum::Attachment, AccessActionEnum::Create),
            'close' => ! $item['status']['is_closed'] && PortalAccess::allows(AccessResourceEnum::Ticket, AccessActionEnum::Close, $found),
            'reopen' => $item['status']['is_closed'] && PortalAccess::allows(AccessResourceEnum::Ticket, AccessActionEnum::Reopen, $found),
        ];

        return Inertia::render('portal/tickets/show', [
            'ticket' => TicketChanged::liveForPortalTicket($ticketId, fn (): array => [
                ...$item,
                'description' => $found->getDescription()->toNullableString(),
            ]),
            'conversation' => TicketChanged::liveForPortalTicket($ticketId, fn (): array => FindPortalTickets::conversation($ticketId)),
            'files' => TicketChanged::liveForPortalTicket($ticketId, fn (): array => FindAttachment::sharedOnTicket($ticketId)
                ->map(fn (Attachment $attachment): array => PortalTicketView::file($attachment))
                ->values()
                ->all()),
            'can' => TicketChanged::liveForPortalTicket($ticketId, fn (): array => $can),
            'accept' => AttachmentValidationRules::ACCEPT,
        ]);
    }
}
