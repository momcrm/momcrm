<?php

declare(strict_types=1);

namespace App\Connector\FormRequests;

use App\Connector\Actions\SaveConnector;
use App\Connector\Connector;
use App\Connector\Sdk\ConnectorRegistry;
use App\Connector\Sdk\ConnectorType;
use App\Connector\Sdk\ConnectsByForm;
use App\Connector\Sdk\CouldNotConnect;
use App\Shared\Types;
use App\Team\Actions\FindTeam;
use App\Team\Exceptions\CouldNotViewTeam;
use App\Team\Team;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Connects an account of a type that is connected by filling in a form. The
 * type says what the form asks for and checks that what was typed works.
 */
final class StoreConnectorFormRequest extends FormRequest
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
        $rules = [];

        foreach ($this->type()->fields() as $field) {
            $rules[$field->name] = $field->validationRules();
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $messages = [];

        foreach ($this->type()->fields() as $field) {
            foreach ($field->messages as $rule => $message) {
                $messages["{$field->name}.{$rule}"] = $message;
            }
        }

        return $messages;
    }

    /**
     * Nothing is saved until the type says what was typed works. Connecting
     * an account the team already has replaces its settings and credentials.
     *
     * @throws ValidationException
     */
    public function process(): RedirectResponse
    {
        $type = $this->type();

        try {
            $account = $type->connect(Types::stringKeyed($this->validated()));
        } catch (CouldNotConnect $exception) {
            throw ValidationException::withMessages([$exception->field => $exception->getMessage()]);
        }

        SaveConnector::fromConnector(
            Connector::new()
                ->setTeamId($this->team->getId()->toString())
                ->setType($type->key())
                ->setAccount($account->account)
                ->setName($account->name)
                ->setSettings($account->settings)
                ->setConnectedById((string) $this->user()?->id),
            $account->credentials,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':account connected.', ['account' => $account->account])]);

        return to_route('connectors.index');
    }

    /**
     * The type being connected, which must be one that has a form and that
     * this installation can connect.
     */
    private function type(): ConnectsByForm&ConnectorType
    {
        $type = ConnectorRegistry::instance()->find((string) $this->route('type'));

        if (! $type instanceof ConnectsByForm || ! $type->isAvailable()) {
            throw new NotFoundHttpException;
        }

        return $type;
    }
}
