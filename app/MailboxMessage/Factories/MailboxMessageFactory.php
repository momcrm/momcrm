<?php

declare(strict_types=1);

namespace App\MailboxMessage\Factories;

use App\Mailbox\MailboxEloquentModel;
use App\MailboxMessage\MailboxMessageDirectionEnum;
use App\MailboxMessage\MailboxMessageEloquentModel;
use App\MailboxMessage\MailboxMessageStatusEnum;
use App\MailboxMessage\Properties\BodyText;
use App\MailboxMessage\Properties\Direction;
use App\MailboxMessage\Properties\FromAddress;
use App\MailboxMessage\Properties\InternetMessageId;
use App\MailboxMessage\Properties\MailboxId;
use App\MailboxMessage\Properties\MicrosoftId;
use App\MailboxMessage\Properties\ReceivedAt;
use App\MailboxMessage\Properties\Status;
use App\MailboxMessage\Properties\Subject;
use App\MailboxMessage\Properties\TeamId;
use App\Shared\Types;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MailboxMessageEloquentModel>
 */
final class MailboxMessageFactory extends Factory
{
    protected $model = MailboxMessageEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            MailboxId::getDatabaseTableColumnName() => MailboxEloquentModel::factory(),
            TeamId::getDatabaseTableColumnName() => fn (array $attributes): string => Types::string(MailboxEloquentModel::query()
                ->withoutGlobalScopes()
                ->whereKey($attributes[MailboxId::getDatabaseTableColumnName()])
                ->value('team_id')),
            Direction::getDatabaseTableColumnName() => MailboxMessageDirectionEnum::Inbound->value,
            Status::getDatabaseTableColumnName() => MailboxMessageStatusEnum::Processed->value,
            MicrosoftId::getDatabaseTableColumnName() => fake()->unique()->sha256(),
            InternetMessageId::getDatabaseTableColumnName() => fake()->unique()->uuid().'@mail.example.test',
            FromAddress::getDatabaseTableColumnName() => fake()->safeEmail(),
            Subject::getDatabaseTableColumnName() => fake()->sentence(4),
            BodyText::getDatabaseTableColumnName() => fake()->paragraph(),
            ReceivedAt::getDatabaseTableColumnName() => now(),
        ];
    }

    public function outbound(): static
    {
        return $this->state([
            Direction::getDatabaseTableColumnName() => MailboxMessageDirectionEnum::Outbound->value,
            Status::getDatabaseTableColumnName() => MailboxMessageStatusEnum::Sent->value,
        ]);
    }
}
