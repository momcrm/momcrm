<?php

declare(strict_types=1);

namespace App\Ticket\FormRequests;

use App\Ticket\Actions\CreateTicket;
use App\Ticket\Actions\FindTicket;
use App\Ticket\Exceptions\CouldNotFindTicket;
use App\Ticket\Properties\Title;
use App\Ticket\Ticket;
use App\Ticket\TicketPriorityEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;

/**
 * Adds a subtask under a ticket. It belongs to the same customer and project.
 */
final class StoreSubtaskFormRequest extends FormRequest
{
    public Ticket $parent;

    public function __construct()
    {
        parent::__construct();

        $this->parent = Ticket::new();
    }

    /**
     * @throws CouldNotFindTicket
     */
    public function authorize(): bool
    {
        $this->parent = FindTicket::byIdForTeam((string) $this->route('ticket'), $this->user()?->current_team_id);

        if ($this->parent->isNull() || $this->parent->getParentId()->isNotNull()) {
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
            Title::getNameForValidation() => Title::getValidationRulesForCreate($this),
        ];
    }

    public function process(): RedirectResponse
    {
        CreateTicket::fromTicket(
            Ticket::new()
                ->setTeamId($this->parent->getTeamId())
                ->setParentId($this->parent->getId())
                ->setCustomerId($this->parent->getCustomerId())
                ->setProjectId($this->parent->getProjectId())
                ->setTitle($this->string(Title::getNameForValidation())->toString())
                ->setPriority(TicketPriorityEnum::Normal),
            openedById: (string) $this->user()?->getAuthIdentifier(),
        );

        return back();
    }
}
