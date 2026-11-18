<?php

declare(strict_types=1);

namespace App\Connector\FormRequests;

use App\Connector\Actions\FindConnector;
use App\Connector\ConnectorSession;
use App\Connector\ConnectorTypeEnum;
use App\Team\Actions\FindTeam;
use App\Team\Exceptions\CouldNotViewTeam;
use App\Team\Team;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ConnectConnectorFormRequest extends FormRequest
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

        return Gate::authorize('manageConnectors', $this->team);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'shared_mailboxes' => ['nullable', 'boolean'],
            'connector' => ['nullable', 'uuid'],
        ];
    }

    /**
     * Hands over to the service's sign-in page, asking only for the
     * permissions wanted. Reconnecting asks for what the connector already
     * has, and suggests the account it belongs to.
     */
    public function process(): RedirectResponse
    {
        $type = ConnectorTypeEnum::tryFrom((string) $this->route('type'));

        $signIn = $type?->signInProvider();

        if ($type === null || $signIn === null || ! $type->isAvailable()) {
            throw new NotFoundHttpException;
        }

        $reconnecting = FindConnector::byIdForTeam($this->string('connector')->toString() ?: null, $this->team->getId()->toString());
        $isReconnecting = $reconnecting->isNotNull() && $reconnecting->getType()->toNullableEnum() === $type;

        $withSharedMailboxes = $type->supportsSharedMailboxes()
            && ($isReconnecting ? $reconnecting->reachesSharedMailboxes() : $this->boolean('shared_mailboxes'));

        $this->session()->put(ConnectorSession::PENDING_SHARED_MAILBOXES, $withSharedMailboxes);

        /** @var AbstractProvider $provider */
        $provider = Socialite::driver($signIn->value);

        return $provider
            ->scopes($type->scopes($withSharedMailboxes))
            ->redirectUrl(route('connectors.callback', ['type' => $type->value]))
            ->with([
                ...$type->signInParameters(),
                ...($isReconnecting ? ['login_hint' => $reconnecting->getAccount()->toString()] : []),
            ])
            ->redirect();
    }
}
