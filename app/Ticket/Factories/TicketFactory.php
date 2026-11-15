<?php

declare(strict_types=1);

namespace App\Ticket\Factories;

use App\Choice\Actions\FindChoice;
use App\Choice\ChoiceListEnum;
use App\Customer\CustomerEloquentModel;
use App\Shared\Types;
use App\Team\TeamEloquentModel;
use App\Ticket\Properties\ArchivedAt;
use App\Ticket\Properties\AssigneeId;
use App\Ticket\Properties\ClosedAt;
use App\Ticket\Properties\ContactId;
use App\Ticket\Properties\CustomerId;
use App\Ticket\Properties\Description;
use App\Ticket\Properties\DueOn;
use App\Ticket\Properties\EstimateMinutes;
use App\Ticket\Properties\LocationId;
use App\Ticket\Properties\Number;
use App\Ticket\Properties\ParentId;
use App\Ticket\Properties\Position;
use App\Ticket\Properties\PriorityId;
use App\Ticket\Properties\ProjectId;
use App\Ticket\Properties\StartsOn;
use App\Ticket\Properties\Status;
use App\Ticket\Properties\TeamId;
use App\Ticket\Properties\Title;
use App\Ticket\TicketEloquentModel;
use App\Ticket\TicketStatusEnum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketEloquentModel>
 */
final class TicketFactory extends Factory
{
    protected $model = TicketEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            TeamId::getDatabaseTableColumnName() => TeamEloquentModel::factory(),
            Number::getDatabaseTableColumnName() => fake()->unique()->numberBetween(1, 1000000),
            CustomerId::getDatabaseTableColumnName() => CustomerEloquentModel::factory(),
            ContactId::getDatabaseTableColumnName() => null,
            LocationId::getDatabaseTableColumnName() => null,
            AssigneeId::getDatabaseTableColumnName() => null,
            Title::getDatabaseTableColumnName() => fake()->sentence(4),
            Description::getDatabaseTableColumnName() => fake()->paragraph(),
            Status::getDatabaseTableColumnName() => TicketStatusEnum::New->value,
            PriorityId::getDatabaseTableColumnName() => fn (array $attributes): ?string => FindChoice::defaultForTeam(Types::string($attributes[TeamId::getDatabaseTableColumnName()]), ChoiceListEnum::TicketPriority)->getId()->toNullableString(),
            ClosedAt::getDatabaseTableColumnName() => null,
            ProjectId::getDatabaseTableColumnName() => null,
            ParentId::getDatabaseTableColumnName() => null,
            StartsOn::getDatabaseTableColumnName() => null,
            DueOn::getDatabaseTableColumnName() => null,
            EstimateMinutes::getDatabaseTableColumnName() => null,
            Position::getDatabaseTableColumnName() => 0,
            ArchivedAt::getDatabaseTableColumnName() => null,
        ];
    }

    public function archived(): self
    {
        return $this->state([ArchivedAt::getDatabaseTableColumnName() => now()]);
    }
}
