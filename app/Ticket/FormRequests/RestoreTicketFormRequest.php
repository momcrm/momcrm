<?php

declare(strict_types=1);

namespace App\Ticket\FormRequests;

use App\Ticket\Actions\ArchiveTicket;
use App\Ticket\Actions\FindTicket;
use App\Ticket\Exceptions\CouldNotFindTicket;
use App\Ticket\Ticket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final class RestoreTicketFormRequest extends FormRequest
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

        if ($this->ticket->isNull() || ! $this->ticket->isArchived()) {
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
        ArchiveTicket::restore($this->ticket, userId: (string) $this->user()?->getAuthIdentifier());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Ticket #:number restored.', ['number' => $this->ticket->getNumber()->toInteger()])]);

        return back();
    }
}
