<?php

declare(strict_types=1);

namespace App\Mailbox\FormRequests;

use App\Connector\Actions\FindConnector;
use App\Mailbox\Actions\FindMailbox;
use App\Mailbox\Exceptions\CouldNotFindMailbox;
use App\Mailbox\Jobs\SyncMailboxJob;
use App\Mailbox\Mailbox;
use App\Team\Actions\FindTeam;
use App\Team\Exceptions\CouldNotViewTeam;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

final class SyncMailboxFormRequest extends FormRequest
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
        return [];
    }

    public function process(): RedirectResponse
    {
        $address = $this->mailbox->getAddress()->toString();

        if (! FindConnector::byId($this->mailbox->getConnectorId()->toNullableString())->isConnected()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Reconnect the account :address goes through before checking it for mail.', ['address' => $address])]);

            return to_route('mailboxes.index');
        }

        SyncMailboxJob::dispatch($this->mailbox->getId()->toString());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Checking :address for new mail.', ['address' => $address])]);

        return to_route('mailboxes.index');
    }
}
