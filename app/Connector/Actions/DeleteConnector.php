<?php

declare(strict_types=1);

namespace App\Connector\Actions;

use App\Connector\Connector;
use App\Connector\ConnectorEloquentModel;
use App\Team\Scopes\CurrentTeamScope;

/**
 * Disconnects an outside account: its credentials are forgotten and nothing
 * uses it any more.
 */
final readonly class DeleteConnector
{
    public function __construct(
        private Connector $connector,
    ) {}

    public function __invoke(): void
    {
        ConnectorEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->find($this->connector->getId()->toString())
            ?->delete();

        GetConnectorAccessToken::forget($this->connector);
    }

    public static function fromConnector(Connector $connector): void
    {
        new self($connector)->handle();
    }

    public function handle(): void
    {
        $this->__invoke();
    }

    public function execute(): void
    {
        $this->__invoke();
    }
}
