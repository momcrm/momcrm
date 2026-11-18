<?php

declare(strict_types=1);

namespace App\Connector\FormRequests;

use App\Connector\Actions\FindConnector;
use App\Connector\ConnectorSession;
use App\Team\Actions\FindTeam;
use App\Team\Exceptions\CouldNotViewTeam;
use App\Team\Team;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Mom\Connectors\ConnectorRegistry;
use Mom\Connectors\ConnectsBySignIn;
use Mom\Connectors\SignInOption;
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
     * Each of the type's sign-in options may be ticked; a type that is not
     * signed in to has none, and is turned away in process().
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $type = ConnectorRegistry::instance()->find((string) $this->route('type'));
        $rules = ['connector' => ['nullable', 'uuid']];

        foreach ($type instanceof ConnectsBySignIn ? $type->signInOptions() : [] as $option) {
            $rules[$option->key] = ['nullable', 'boolean'];
        }

        return $rules;
    }

    /**
     * Hands over to the service's sign-in page, asking only for the
     * permissions wanted. Reconnecting asks for what the connector already
     * has, and suggests the account it belongs to.
     */
    public function process(): RedirectResponse
    {
        $type = ConnectorRegistry::instance()->find((string) $this->route('type'));

        if (! $type instanceof ConnectsBySignIn || ! $type->isAvailable()) {
            throw new NotFoundHttpException;
        }

        $reconnecting = FindConnector::byIdForTeam($this->string('connector')->toString() ?: null, $this->team->getId()->toString());
        $isReconnecting = $reconnecting->isNotNull() && $reconnecting->getType()->toNullableString() === $type->key();

        $options = array_values(array_map(
            fn (SignInOption $option): string => $option->key,
            array_filter(
                $type->signInOptions(),
                fn (SignInOption $option): bool => $isReconnecting ? $reconnecting->hasOption($option->key) : $this->boolean($option->key),
            ),
        ));

        $this->session()->put(ConnectorSession::PENDING_OPTIONS, $options);

        /** @var AbstractProvider $provider */
        $provider = Socialite::driver($type->signInDriver());

        return $provider
            ->scopes($type->scopes($options))
            ->redirectUrl(route('connectors.callback', ['type' => $type->key()]))
            ->with([
                ...$type->signInParameters(),
                ...($isReconnecting ? ['login_hint' => $reconnecting->getAccount()->toString()] : []),
            ])
            ->redirect();
    }
}
