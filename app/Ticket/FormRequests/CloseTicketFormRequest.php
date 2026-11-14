<?php

declare(strict_types=1);

namespace App\Ticket\FormRequests;

use App\Ticket\Actions\FindTicket;
use App\Ticket\Actions\UpdateTicket;
use App\Ticket\Exceptions\CouldNotFindTicket;
use App\Ticket\Ticket;
use App\Ticket\TicketStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final class CloseTicketFormRequest extends FormRequest
{
    public Ticket $ticket;

    public function __construct()
    {
        parent::__construct();

        $this->ticket = Ticket::new();
    }

    /**
     * @throws CouldNotFindTicket
     */
    public function authorize(): bool
    {
        $this->ticket = FindTicket::byIdForTeam((string) $this->route('ticket'), $this->user()?->current_team_id);

        if ($this->ticket->isNull()) {
            throw CouldNotFindTicket::notFound();
        }

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
        UpdateTicket::fromTicket(
            $this->ticket->setStatus(TicketStatusEnum::Closed),
            updatedById: (string) $this->user()?->getAuthIdentifier(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Ticket #:number closed. Nice work.', ['number' => $this->ticket->getNumber()->toInteger()])]);

        return to_route('tickets.show', $this->ticket->getId()->toString());
    }
}
