<?php

declare(strict_types=1);

namespace App\Mailbox\FormRequests;

use App\Mailbox\Actions\FindMailbox;
use App\Mailbox\Actions\UpdateMailbox;
use App\Mailbox\Exceptions\CouldNotFindMailbox;
use App\Mailbox\Mailbox;
use App\Mailbox\Properties\AutoCreateTickets;
use App\Mailbox\Properties\GroupId;
use App\Shared\Table;
use App\Team\Actions\FindTeam;
use App\Team\Exceptions\CouldNotViewTeam;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

final class UpdateMailboxFormRequest extends FormRequest
{
    public Mailbox $mailbox;

    public function __construct()
    {
        parent::__construct();

        $this->mailbox = Mailbox::new();
    }

    /**
     * @throws CouldNotViewTeam
     * @throws CouldNotFindMailbox
     */
    public function authorize(): Response
    {
        $team = FindTeam::byIdForMember(
            id: $this->user()?->current_team_id,
            userId: (string) $this->user()?->id,
        );

        if ($team->isNull()) {
            throw CouldNotViewTeam::notFound();
        }

        $this->mailbox = FindMailbox::byIdForTeam((string) $this->route('mailbox'), $team->getId()->toString());

        if ($this->mailbox->isNull()) {
            throw CouldNotFindMailbox::notFound();
        }

        return Gate::authorize('manageEmail', $team);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            GroupId::getNameForValidation() => [
                ...GroupId::getValidationRulesForCreate($this),
                Rule::exists(Table::Groups->value, 'id')->where('team_id', $this->mailbox->getTeamId()->toString()),
            ],
            AutoCreateTickets::getNameForValidation() => AutoCreateTickets::getValidationRulesForCreate($this),
        ];
    }

    public function process(): RedirectResponse
    {
        UpdateMailbox::fromMailbox(
            $this->mailbox
                ->setGroupId($this->validated(GroupId::getNameForValidation()))
                ->setAutoCreateTickets($this->boolean(AutoCreateTickets::getNameForValidation())),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':address saved.', ['address' => $this->mailbox->getAddress()->toString()])]);

        return to_route('mailboxes.index');
    }
}
