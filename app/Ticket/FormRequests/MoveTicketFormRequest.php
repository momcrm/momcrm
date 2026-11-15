<?php

declare(strict_types=1);

namespace App\Ticket\FormRequests;

use App\Ticket\Actions\FindTicket;
use App\Ticket\Actions\MoveTicket;
use App\Ticket\Exceptions\CouldNotFindTicket;
use App\Ticket\Properties\Position;
use App\Ticket\Properties\StatusId;
use App\Ticket\Ticket;
use App\Ticket\TicketAccess;
use App\Ticket\TicketValidationRules;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;

final class MoveTicketFormRequest extends FormRequest
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
    public function authorize(): Response
    {
        $this->ticket = FindTicket::byIdForTeam((string) $this->route('ticket'), $this->user()?->current_team_id);

        if ($this->ticket->isNull() || $this->ticket->isArchived()) {
            throw CouldNotFindTicket::notFound();
        }

        return TicketAccess::authorizeStatusChange('move', $this->ticket, $this->input(StatusId::getNameForValidation()));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            ...TicketValidationRules::status($this, $this->ticket->getTeamId()->toString(), isRequired: true),
            Position::getNameForValidation() => ['required', 'integer', 'min:0'],
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
        MoveTicket::to(
            $this->ticket,
            $this->string(StatusId::getNameForValidation())->toString(),
            $this->integer(Position::getNameForValidation()),
            movedById: (string) $this->user()?->id,
        );

        return back();
    }
}
