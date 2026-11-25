<?php

declare(strict_types=1);

namespace App\Ticket\FormRequests;

use App\Choice\LabelTargetEnum;
use App\CustomField\CustomFieldTargetEnum;
use App\CustomField\CustomFieldValueRules;
use App\CustomField\Properties\CustomFieldValues;
use App\Shared\Types;
use App\Ticket\Actions\FindTicket;
use App\Ticket\Actions\UpdateTicket;
use App\Ticket\Events\TicketChanged;
use App\Ticket\Exceptions\CouldNotFindTicket;
use App\Ticket\Properties\ContactId;
use App\Ticket\Properties\CustomerId;
use App\Ticket\Properties\LocationId;
use App\Ticket\Properties\StatusId;
use App\Ticket\Ticket;
use App\Ticket\TicketAccess;
use App\Ticket\TicketEloquentModel;
use App\Ticket\TicketValidationRules;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;

/**
 * Changes one or a few fields of a ticket in place, as the drawer's inline
 * pickers do. Fields left out keep their value, and the result is checked with
 * the same rules as the full edit form.
 */
final class PatchTicketFormRequest extends FormRequest
{
    public const string LABEL_IDS = 'label_ids';

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
        $this->loadTicket();

        return TicketAccess::authorizeStatusChange('update', $this->ticket, $this->input(StatusId::getNameForValidation()));
    }

    /**
     * @return array<string, array<array-key, mixed>>
     */
    public function rules(): array
    {
        $teamId = (string) $this->user()?->current_team_id;

        return [
            ...TicketValidationRules::for($this, $teamId, $this->ticket),
            ...TicketValidationRules::status($this, $teamId, currentStatusId: $this->ticket->getStatusId()->toNullableString()),
            ...CustomFieldValueRules::for($teamId, CustomFieldTargetEnum::Ticket, $this->ticket->getCustomFields()),
            self::LABEL_IDS => ['sometimes', 'array'],
            self::LABEL_IDS.'.*' => ['uuid', LabelTargetEnum::Ticket->rule($teamId, $this->ticket->getId()->toString())],
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
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return CustomFieldValueRules::attributes((string) $this->user()?->current_team_id, CustomFieldTargetEnum::Ticket);
    }

    public function process(): RedirectResponse
    {
        $validated = $this->validated();

        $customFields = CustomFieldValues::getNameForValidation();
        $this->ticket->setPropertyIfExists(Types::stringKeyed(Arr::except($validated, [self::LABEL_IDS, $customFields])));

        if (array_key_exists($customFields, $validated)) {
            $this->ticket->setCustomFields(CustomFieldValueRules::merge(
                $this->ticket->getTeamId()->toString(),
                CustomFieldTargetEnum::Ticket,
                $this->ticket->getCustomFields(),
                $validated[$customFields],
            ));
        }

        UpdateTicket::fromTicket(
            $this->ticket,
            updatedById: (string) $this->user()?->id,
        );

        if (array_key_exists(self::LABEL_IDS, $validated)) {
            TicketEloquentModel::query()
                ->findOrFail($this->ticket->getId()->toString())
                ->labels()
                ->sync(Types::stringList($validated[self::LABEL_IDS]));

            // Labels are saved apart from the ticket's row, which fires no model event.
            TicketChanged::later($this->ticket->getTeamId()->toString(), $this->ticket->getId()->toString());
        }

        return back();
    }

    /**
     * Runs before authorize(), so it loads the ticket itself.
     *
     * @throws CouldNotFindTicket
     */
    protected function prepareForValidation(): void
    {
        $this->loadTicket();

        $current = $this->ticket->toSummary();
        $customerChanged = $this->has(CustomerId::getNameForValidation())
            && $this->input(CustomerId::getNameForValidation()) !== $current['customer_id'];

        if ($customerChanged) {
            $current[ContactId::getNameForValidation()] = null;
            $current[LocationId::getNameForValidation()] = null;
        }

        $this->merge(array_merge(
            Arr::only($current, ['customer_id', 'project_id', 'contact_id', 'location_id', 'assignee_id', 'title', 'description', 'priority_id', 'type_id', 'group_id', 'status_id', 'starts_on', 'due_on', 'estimate_minutes']),
            $this->all(),
        ));
    }

    /**
     * @throws CouldNotFindTicket
     */
    private function loadTicket(): bool
    {
        if ($this->ticket->isNull()) {
            $this->ticket = FindTicket::byIdForTeam((string) $this->route('ticket'), $this->user()?->current_team_id);
        }

        if ($this->ticket->isNull()) {
            throw CouldNotFindTicket::notFound();
        }

        return true;
    }
}
