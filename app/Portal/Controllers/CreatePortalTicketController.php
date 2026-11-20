<?php

declare(strict_types=1);

namespace App\Portal\Controllers;

use App\AccessRule\AccessActionEnum;
use App\AccessRule\AccessResourceEnum;
use App\Attachment\AttachmentValidationRules;
use App\Choice\Actions\FindChoice;
use App\Choice\ChoiceListEnum;
use App\Portal\PortalAccess;
use App\Portal\PortalContact;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The form for raising a ticket in the portal.
 */
final class CreatePortalTicketController
{
    /**
     * @throws AuthorizationException
     */
    public function __invoke(): Response
    {
        PortalAccess::authorize(AccessResourceEnum::Ticket, AccessActionEnum::Create);

        $teamId = PortalContact::current()->getTeamId()->toString();

        return Inertia::render('portal/tickets/create', [
            'types' => FindChoice::listForTeam($teamId, ChoiceListEnum::TicketType)->toOptions(),
            'priorities' => FindChoice::listForTeam($teamId, ChoiceListEnum::TicketPriority)->toOptions(),
            'defaultPriorityId' => FindChoice::defaultForTeam($teamId, ChoiceListEnum::TicketPriority)->getId()->toNullableString(),
            'canAttach' => PortalAccess::allows(AccessResourceEnum::Attachment, AccessActionEnum::Create),
            'accept' => AttachmentValidationRules::ACCEPT,
        ]);
    }
}
