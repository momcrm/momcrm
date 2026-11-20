<?php

declare(strict_types=1);

namespace App\Portal\FormRequests;

use App\AccessRule\AccessActionEnum;
use App\AccessRule\AccessResourceEnum;
use App\Choice\Actions\FindChoice;
use App\Choice\ChoiceListEnum;
use App\Portal\Actions\FindPortalTickets;
use App\Portal\PortalAccess;
use App\Portal\PortalContact;
use App\Ticket\Actions\UpdateTicket;
use App\Ticket\Ticket;
use App\Ticket\TicketStatusCategoryEnum;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Closes or reopens one of the customer's tickets from the portal, moving it
 * to the team's default closed or open status.
 */
final class ChangePortalTicketStatusFormRequest extends FormRequest
{
    public Ticket $ticket;

    public function __construct()
    {
        parent::__construct();

        $this->ticket = Ticket::new();
    }

    /**
     * @throws AuthorizationException
     */
    public function authorize(): bool
    {
        $this->ticket = FindPortalTickets::visibleToContact((string) $this->route('ticket'));

        PortalAccess::authorize(AccessResourceEnum::Ticket, $this->isClosing() ? AccessActionEnum::Close : AccessActionEnum::Reopen, $this->ticket);

        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }

    public function process(): RedirectResponse
    {
        $teamId = $this->ticket->getTeamId()->toString();
        $category = $this->isClosing() ? TicketStatusCategoryEnum::Closed : TicketStatusCategoryEnum::Open;
        $status = FindChoice::defaultForTeam($teamId, ChoiceListEnum::TicketStatus, $category->value);

        if ($status->isNotNull() && ! FindChoice::byIdForTeam($this->ticket->getStatusId()->toNullableString(), $teamId)->hasCategory($category)) {
            UpdateTicket::fromTicket($this->ticket->setStatusId($status->getId()), actorName: PortalContact::current()->getFullName());
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => $this->isClosing() ? __('Ticket closed.') : __('Ticket reopened.')]);

        return back();
    }

    private function isClosing(): bool
    {
        return $this->routeIs('portal.tickets.close');
    }
}
