<?php

declare(strict_types=1);

namespace App\Ticket\FormRequests;

use App\Ticket\Actions\FindTicket;
use App\Ticket\Actions\UpdateTicket;
use App\Ticket\Exceptions\CouldNotFindTicket;
use App\Ticket\Properties\Status;
use App\Ticket\Ticket;
use App\Ticket\TicketValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final class UpdateTicketFormRequest extends FormRequest
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
        return [
            ...TicketValidationRules::for($this, (string) $this->user()?->current_team_id, $this->ticket->getProjectId()->toNullableString()),
            Status::getNameForValidation() => Status::getValidationRulesForUpdate($this),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return TicketValidationRules::messages();
    }

    public function process(): RedirectResponse
    {
        $ticket = UpdateTicket::fromTicket(
            $this->ticket->setPropertyIfExists($this->validated()),
            updatedById: (string) $this->user()?->getAuthIdentifier(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Ticket #:number saved.', ['number' => $ticket->getNumber()->toInteger()])]);

        return to_route('tickets.show', $ticket->getId()->toString());
    }
}
