<?php

declare(strict_types=1);

namespace App\Ticket\Factories;

use App\Customer\CustomerEloquentModel;
use App\Team\TeamEloquentModel;
use App\Ticket\Properties\AssigneeId;
use App\Ticket\Properties\ClosedAt;
use App\Ticket\Properties\ContactId;
use App\Ticket\Properties\CustomerId;
use App\Ticket\Properties\Description;
use App\Ticket\Properties\LocationId;
use App\Ticket\Properties\Number;
use App\Ticket\Properties\Priority;
use App\Ticket\Properties\Status;
use App\Ticket\Properties\TeamId;
use App\Ticket\Properties\Title;
use App\Ticket\TicketEloquentModel;
use App\Ticket\TicketPriorityEnum;
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
            Priority::getDatabaseTableColumnName() => TicketPriorityEnum::Normal->value,
            ClosedAt::getDatabaseTableColumnName() => null,
        ];
    }
}
