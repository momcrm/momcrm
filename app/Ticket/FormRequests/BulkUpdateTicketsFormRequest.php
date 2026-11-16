<?php

declare(strict_types=1);

namespace App\Ticket\FormRequests;

use App\Choice\ChoiceListEnum;
use App\Shared\Table;
use App\Shared\Types;
use App\Ticket\Actions\UpdateTicket;
use App\Ticket\BulkTicketSelection;
use App\Ticket\Exceptions\CouldNotFindTicket;
use App\Ticket\Properties\AssigneeId;
use App\Ticket\Properties\PriorityId;
use App\Ticket\Properties\StatusId;
use App\Ticket\Ticket;
use App\Ticket\TicketAccess;
use App\Ticket\TicketCollection;
use App\Ticket\TicketValidationRules;
use Closure;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Inertia\Inertia;

/**
 * Sets the status, priority or assignee of several tickets at once, from the
 * bar a ticket view shows once tickets are picked. Each ticket is authorized
 * as if it were changed on its own (closing and reopening included), and if
 * any one is refused, none change.
 */
final class BulkUpdateTicketsFormRequest extends FormRequest
{
    /**
     * The fields a bulk change can set.
     *
     * @var array<int, string>
     */
    public const array FIELDS = ['status_id', 'priority_id', 'assignee_id'];

    public TicketCollection $tickets;

    public function __construct()
    {
        parent::__construct();

        $this->tickets = new TicketCollection([]);
    }

    /**
     * @throws CouldNotFindTicket
     */
    public function authorize(): Response
    {
        $this->tickets = BulkTicketSelection::load($this);

        foreach ($this->tickets as $ticket) {
            TicketAccess::authorizeStatusChange('update', $ticket, $this->input(StatusId::getNameForValidation()));
        }

        return Response::allow();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $teamId = (string) $this->user()?->current_team_id;

        return [
            ...BulkTicketSelection::rules(),
            StatusId::getNameForValidation() => [
                'sometimes',
                ...TicketValidationRules::status($this, $teamId, isRequired: true)[StatusId::getNameForValidation()],
            ],
            PriorityId::getNameForValidation() => [
                'sometimes',
                'nullable',
                'uuid',
                Rule::exists(Table::Choices->value, 'id')
                    ->where('team_id', $teamId)
                    ->where('list', ChoiceListEnum::TicketPriority->value)
                    ->where(fn (Builder $query) => $query->whereNull('archived_at')),
            ],
            AssigneeId::getNameForValidation() => [
                'sometimes',
                'nullable',
                'uuid',
                Rule::exists(Table::TeamUser->value, 'user_id')->where('team_id', $teamId),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return TicketValidationRules::messages();
    }

    /**
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $this->hasAny(self::FIELDS)) {
                $validator->errors()->add(BulkTicketSelection::TICKET_IDS, __('Choose what to change on the selected tickets.'));
            }
        }];
    }

    public function process(): RedirectResponse
    {
        $changes = Types::stringKeyed(Arr::only($this->validated(), self::FIELDS));
        $userId = (string) $this->user()?->id;

        DB::transaction(function () use ($changes, $userId): void {
            $this->tickets->each(fn (Ticket $ticket): Ticket => UpdateTicket::fromTicket(
                $ticket->setPropertyIfExists($changes),
                updatedById: $userId,
            ));
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => trans_choice('{1} 1 ticket updated.|[2,*] :count tickets updated.', $this->tickets->count())]);

        return back();
    }
}
