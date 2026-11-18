<?php

declare(strict_types=1);

namespace App\Connector\FormRequests;

use App\Connector\Actions\DeleteConnector;
use App\Connector\Actions\FindConnector;
use App\Connector\Connector;
use App\Connector\Exceptions\CouldNotFindConnector;
use App\Team\Actions\FindTeam;
use App\Team\Exceptions\CouldNotViewTeam;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

final class DeleteConnectorFormRequest extends FormRequest
{
    public Connector $connector;

    public function __construct()
    {
        parent::__construct();

        $this->connector = Connector::new();
    }

    /**
     * @throws CouldNotViewTeam
     * @throws CouldNotFindConnector
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

        $this->connector = FindConnector::byIdForTeam((string) $this->route('connector'), $team->getId()->toString());

        if ($this->connector->isNull()) {
            throw CouldNotFindConnector::notFound();
        }

        return Gate::authorize('manageConnectors', $team);
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
        DeleteConnector::fromConnector($this->connector);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':account disconnected.', ['account' => $this->connector->getAccount()->toString()])]);

        return to_route('connectors.index');
    }
}
