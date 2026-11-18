<?php

declare(strict_types=1);

namespace App\Connector\Actions;

use App\Connector\Connector;
use App\Connector\ConnectorEloquentModel;
use App\Connector\ConnectorStatusEnum;
use App\Connector\Exceptions\CouldNotReachConnector;
use App\Connector\Properties\LastError;
use App\Connector\Properties\Status;
use App\Team\Scopes\CurrentTeamScope;

/**
 * Records that the outside service turned away what a connector has stored.
 * The connector is not used again until someone connects the account anew,
 * and settings shows why.
 */
final readonly class MarkConnectorAsNeedingReconnect
{
    public function __construct(
        private Connector $connector,
    ) {}

    /**
     * The reason, as an exception for the caller to throw.
     */
    public function __invoke(): CouldNotReachConnector
    {
        $exception = CouldNotReachConnector::needsReconnect($this->connector->connectorType()->company());

        ConnectorEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->find($this->connector->getId()->toString())
            ?->forceFill([
                Status::getDatabaseTableColumnName() => ConnectorStatusEnum::NeedsReconnect->value,
                LastError::getDatabaseTableColumnName() => $exception->getMessage(),
            ])
            ->save();

        return $exception;
    }

    public static function fromConnector(Connector $connector): CouldNotReachConnector
    {
        return new self($connector)->handle();
    }

    public function handle(): CouldNotReachConnector
    {
        return $this->__invoke();
    }

    public function execute(): CouldNotReachConnector
    {
        return $this->__invoke();
    }
}
