<?php

declare(strict_types=1);

namespace App\Connector\Factories;

use App\Connector\Connector;
use App\Connector\ConnectorEloquentModel;
use App\Connector\ConnectorStatusEnum;
use App\Connector\ConnectorTypeEnum;
use App\Connector\Properties\Account;
use App\Connector\Properties\Name;
use App\Connector\Properties\Settings;
use App\Connector\Properties\Status;
use App\Connector\Properties\TeamId;
use App\Connector\Properties\Type;
use App\Team\TeamEloquentModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConnectorEloquentModel>
 */
final class ConnectorFactory extends Factory
{
    protected $model = ConnectorEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $account = fake()->unique()->safeEmail();

        return [
            TeamId::getDatabaseTableColumnName() => TeamEloquentModel::factory(),
            Type::getDatabaseTableColumnName() => ConnectorTypeEnum::Microsoft->value,
            Name::getDatabaseTableColumnName() => fake()->name(),
            Account::getDatabaseTableColumnName() => $account,
            Status::getDatabaseTableColumnName() => ConnectorStatusEnum::Connected->value,
            ConnectorEloquentModel::CREDENTIALS => [ConnectorEloquentModel::REFRESH_TOKEN => 'refresh-token'],
        ];
    }

    public function needsReconnect(): static
    {
        return $this->state([Status::getDatabaseTableColumnName() => ConnectorStatusEnum::NeedsReconnect->value]);
    }

    /**
     * A sign-in that covers the shared mailboxes the account can open.
     */
    public function withSharedMailboxes(): static
    {
        return $this->state([Settings::getDatabaseTableColumnName() => [Connector::SETTING_SHARED_MAILBOXES => true]]);
    }
}
