<?php

declare(strict_types=1);

namespace App\Mailbox\Factories;

use App\Mailbox\MailboxEloquentModel;
use App\Mailbox\MailboxStatusEnum;
use App\Mailbox\Properties\Address;
use App\Mailbox\Properties\AutoCreateTickets;
use App\Mailbox\Properties\IsShared;
use App\Mailbox\Properties\Status;
use App\Mailbox\Properties\SyncedThrough;
use App\Mailbox\Properties\TeamId;
use App\Team\TeamEloquentModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MailboxEloquentModel>
 */
final class MailboxFactory extends Factory
{
    protected $model = MailboxEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            TeamId::getDatabaseTableColumnName() => TeamEloquentModel::factory(),
            Address::getDatabaseTableColumnName() => fake()->unique()->safeEmail(),
            IsShared::getDatabaseTableColumnName() => false,
            Status::getDatabaseTableColumnName() => MailboxStatusEnum::Connected->value,
            AutoCreateTickets::getDatabaseTableColumnName() => true,
            MailboxEloquentModel::REFRESH_TOKEN => 'refresh-token',
            SyncedThrough::getDatabaseTableColumnName() => now(),
        ];
    }

    public function shared(): static
    {
        return $this->state([IsShared::getDatabaseTableColumnName() => true]);
    }

    public function needsReconnect(): static
    {
        return $this->state([Status::getDatabaseTableColumnName() => MailboxStatusEnum::NeedsReconnect->value]);
    }
}
