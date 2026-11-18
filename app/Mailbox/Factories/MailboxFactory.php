<?php

declare(strict_types=1);

namespace App\Mailbox\Factories;

use App\Connector\ConnectorEloquentModel;
use App\Connector\Factories\ConnectorFactory;
use App\Mailbox\MailboxEloquentModel;
use App\Mailbox\Properties\Address;
use App\Mailbox\Properties\AutoCreateTickets;
use App\Mailbox\Properties\ConnectorId;
use App\Mailbox\Properties\IsShared;
use App\Mailbox\Properties\SyncedThrough;
use App\Mailbox\Properties\TeamId;
use App\Team\TeamEloquentModel;
use Closure;
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
            ConnectorId::getDatabaseTableColumnName() => self::connector(ConnectorEloquentModel::factory()),
            Address::getDatabaseTableColumnName() => fake()->unique()->safeEmail(),
            IsShared::getDatabaseTableColumnName() => false,
            AutoCreateTickets::getDatabaseTableColumnName() => true,
            SyncedThrough::getDatabaseTableColumnName() => now(),
        ];
    }

    public function shared(): static
    {
        return $this->state([IsShared::getDatabaseTableColumnName() => true]);
    }

    /**
     * A mailbox whose connector's sign-in is no longer accepted.
     */
    public function needsReconnect(): static
    {
        return $this->state([
            ConnectorId::getDatabaseTableColumnName() => self::connector(ConnectorEloquentModel::factory()->needsReconnect()),
        ]);
    }

    /**
     * A connector in the mailbox's own team, made once the team is known.
     *
     * @return Closure(array<string, mixed>): string
     */
    private static function connector(ConnectorFactory $factory): Closure
    {
        return fn (array $attributes): string => $factory->createOne(['team_id' => $attributes[TeamId::getDatabaseTableColumnName()]])->id;
    }
}
