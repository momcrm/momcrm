<?php

declare(strict_types=1);

namespace App\Ticket\FormRequests;

use App\Customer\Actions\FindCustomer;
use App\Project\Actions\FindProject;
use App\Ticket\Actions\CreateTicket;
use App\Ticket\Properties\Status;
use App\Ticket\Ticket;
use App\Ticket\TicketStatusEnum;
use App\Ticket\TicketValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Opens a ticket from the full form, or from a board column's quick add
 * (`quick`), which starts it in that column and stays on the board.
 */
final class StoreTicketFormRequest extends FormRequest
{
    public const string QUICK = 'quick';

    public function authorize(): bool
    {
        return $this->user()?->current_team_id !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            ...TicketValidationRules::for($this, (string) $this->user()?->current_team_id),
            Status::getNameForValidation() => ['sometimes', Rule::enum(TicketStatusEnum::class)],
            self::QUICK => ['sometimes', 'boolean'],
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
        $teamId = (string) $this->user()?->current_team_id;

        $ticket = CreateTicket::fromTicket(
            Ticket::new()->setPropertyIfExists(Arr::except($this->validated(), self::QUICK))->setTeamId($teamId),
            openedById: (string) $this->user()?->getAuthIdentifier(),
        );

        $for = FindCustomer::byIdForTeam($ticket->getCustomerId()->toNullableString(), $teamId)->getName()->toNullableString()
            ?? FindProject::byIdForTeam($ticket->getProjectId()->toNullableString(), $teamId)->getName()->toNullableString();

        Inertia::flash('toast', ['type' => 'success', 'message' => $for === null
            ? __('Ticket #:number opened.', ['number' => $ticket->getNumber()->toInteger()])
            : __('Ticket #:number opened for :customer.', ['number' => $ticket->getNumber()->toInteger(), 'customer' => $for])]);

        return $this->boolean(self::QUICK)
            ? back()
            : to_route('tickets.show', $ticket->getId()->toString());
    }
}
