<?php

declare(strict_types=1);

namespace App\Mailbox\FormRequests;

use App\Connector\Actions\FindConnector;
use App\Connector\Connector;
use App\Connector\ConnectorCapabilityEnum;
use App\Connector\Exceptions\CouldNotReachConnector;
use App\Mailbox\Actions\CreateMailbox;
use App\Mailbox\Actions\FindMailbox;
use App\Mailbox\Actions\ResolveMailClient;
use App\Mailbox\Contracts\ProvidesMail;
use App\Mailbox\Mailbox;
use App\Team\Actions\FindTeam;
use App\Team\Exceptions\CouldNotViewTeam;
use App\Team\Team;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

final class StoreMailboxFormRequest extends FormRequest
{
    public Team $team;

    public function __construct()
    {
        parent::__construct();

        $this->team = Team::new();
    }

    /**
     * @throws CouldNotViewTeam
     */
    public function authorize(): Response
    {
        $this->team = FindTeam::byIdForMember(
            id: $this->user()?->current_team_id,
            userId: (string) $this->user()?->id,
        );

        if ($this->team->isNull()) {
            throw CouldNotViewTeam::notFound();
        }

        return Gate::authorize('manageEmail', $this->team);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'connector_id' => ['required', 'uuid'],
            'address' => ['nullable', 'string', 'email', 'max:255'],
        ];
    }

    /**
     * Adds the connected account's own mailbox, or a shared mailbox it can
     * open when an address is given. The mailbox is saved only once its inbox
     * has opened, so one that cannot be read is never listed.
     *
     * @throws ValidationException
     */
    public function process(): RedirectResponse
    {
        $teamId = $this->team->getId()->toString();
        $connector = $this->connector($teamId);

        $account = $connector->getAccount()->toString();
        $typed = mb_strtolower(mb_trim($this->string('address')->toString()));
        $sharedAddress = $typed === '' || $typed === $account ? null : $typed;

        $type = $connector->connectorType();

        if ($sharedAddress !== null && ! ($type instanceof ProvidesMail && $type->opensSharedMailboxes($connector))) {
            throw ValidationException::withMessages([
                'address' => __(':account was connected without shared mailboxes. Reconnect it with them turned on to add one.', ['account' => $account]),
            ]);
        }

        $address = $sharedAddress ?? $account;
        $existing = FindMailbox::byAddress($address);

        if ($existing->isNotNull()) {
            throw ValidationException::withMessages([
                'address' => $existing->getTeamId()->toString() === $teamId
                    ? __(':address is already one of your mailboxes.', ['address' => $address])
                    : __(':address is already connected to another workspace.', ['address' => $address]),
            ]);
        }

        try {
            ResolveMailClient::forConnector($connector, $sharedAddress)->checkInbox();
        } catch (CouldNotReachConnector) {
            throw ValidationException::withMessages([
                'address' => $sharedAddress === null
                    ? __("We couldn't open the inbox of :address. Check that the account has a mailbox.", ['address' => $address])
                    : __("We couldn't open the inbox of :address. Check the address, and that :account has access to it.", ['address' => $address, 'account' => $account]),
            ]);
        }

        CreateMailbox::fromMailbox(
            Mailbox::new()
                ->setTeamId($teamId)
                ->setConnectorId($connector->getId()->toString())
                ->setAddress($address)
                ->setDisplayName($sharedAddress === null ? $connector->getName()->toString() : null)
                ->setIsShared($sharedAddress !== null)
                ->setAutoCreateTickets(true),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':address added.', ['address' => $address])]);

        return to_route('mailboxes.index');
    }

    /**
     * The team's connector to go through, which must work and be one that
     * can do mail.
     *
     * @throws ValidationException
     */
    private function connector(string $teamId): Connector
    {
        $connector = FindConnector::byIdForTeam($this->string('connector_id')->toString(), $teamId);

        if ($connector->isNull() || ! $connector->hasCapability(ConnectorCapabilityEnum::Mail)) {
            throw ValidationException::withMessages(['connector_id' => __('Choose one of your connected accounts.')]);
        }

        if (! $connector->isConnected()) {
            throw ValidationException::withMessages([
                'connector_id' => __(':account needs reconnecting before a mailbox can be added.', ['account' => $connector->getAccount()->toString()]),
            ]);
        }

        return $connector;
    }
}
