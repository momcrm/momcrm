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

    public function google(): static
    {
        return $this->state([Type::getDatabaseTableColumnName() => ConnectorTypeEnum::Google->value]);
    }

    /**
     * A mailbox on a mail server, connected by its servers and passwords.
     */
    public function imap(): static
    {
        return $this->state([
            Type::getDatabaseTableColumnName() => ConnectorTypeEnum::Imap->value,
            Settings::getDatabaseTableColumnName() => [
                'from_name' => 'Help Desk',
                'imap_host' => '203.0.113.10',
                'imap_port' => 993,
                'imap_encryption' => 'ssl',
                'imap_username' => 'help',
                'smtp_host' => '203.0.113.20',
                'smtp_port' => 587,
                'smtp_encryption' => 'starttls',
                'smtp_username' => 'help',
            ],
            ConnectorEloquentModel::CREDENTIALS => ['imap_password' => 'imap-secret', 'smtp_password' => 'smtp-secret'],
        ]);
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
