<?php

declare(strict_types=1);

namespace App\Connector\Actions;

use App\Connector\Connector;
use App\Connector\ConnectorEloquentModel;
use App\Connector\ConnectorStatusEnum;
use App\Connector\Properties\Account;
use App\Connector\Properties\ConnectedById;
use App\Connector\Properties\Id;
use App\Connector\Properties\LastError;
use App\Connector\Properties\Name;
use App\Connector\Properties\Settings;
use App\Connector\Properties\Status;
use App\Connector\Properties\TeamId;
use App\Connector\Properties\Type;
use App\Team\Scopes\CurrentTeamScope;

/**
 * Saves an outside account someone just connected, with the credentials that
 * reach it, which are encrypted on the way in. Connecting an account the team
 * already has replaces its credentials and keeps the connector, so whatever
 * uses it carries on.
 */
final readonly class SaveConnector
{
    /**
     * @param  array<string, mixed>  $credentials
     */
    public function __construct(
        private Connector $connector,
        private array $credentials,
    ) {}

    public function __invoke(): Connector
    {
        $identity = [
            TeamId::getDatabaseTableColumnName() => $this->connector->getTeamId()->toString(),
            Type::getDatabaseTableColumnName() => $this->connector->getType()->toEnum()->value,
            Account::getDatabaseTableColumnName() => $this->connector->getAccount()->toString(),
        ];

        $model = ConnectorEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->where($identity)
            ->first();

        $model ??= new ConnectorEloquentModel([Id::getDatabaseTableColumnName() => Id::generate()->toString(), ...$identity]);

        $model->fill([
            Name::getDatabaseTableColumnName() => $this->connector->getName()->toString(),
            Settings::getDatabaseTableColumnName() => $this->connector->getSettings()->toArray(),
            ConnectedById::getDatabaseTableColumnName() => $this->connector->getConnectedById()->toNullableString(),
            Status::getDatabaseTableColumnName() => ConnectorStatusEnum::Connected->value,
            LastError::getDatabaseTableColumnName() => null,
        ]);
        $model->credentials = $this->credentials;
        $model->save();

        return Connector::fromEloquentModel($model->refresh());
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public static function fromConnector(Connector $connector, array $credentials): Connector
    {
        return new self($connector, $credentials)->handle();
    }

    public function handle(): Connector
    {
        return $this->__invoke();
    }

    public function execute(): Connector
    {
        return $this->__invoke();
    }
}
