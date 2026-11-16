<?php

declare(strict_types=1);

namespace App\Choice\Factories;

use App\Choice\ChoiceEloquentModel;
use App\Choice\ChoiceListEnum;
use App\Choice\Properties\Category;
use App\Choice\Properties\Hue;
use App\Choice\Properties\IsDefault;
use App\Choice\Properties\ListName;
use App\Choice\Properties\Name;
use App\Choice\Properties\Position;
use App\Choice\Properties\TeamId;
use App\Team\TeamEloquentModel;
use App\Ticket\TicketStatusCategoryEnum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChoiceEloquentModel>
 */
final class ChoiceFactory extends Factory
{
    protected $model = ChoiceEloquentModel::class;

    /**
     * An open ticket status, unless a state says otherwise.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            TeamId::getDatabaseTableColumnName() => TeamEloquentModel::factory(),
            ListName::getDatabaseTableColumnName() => ChoiceListEnum::TicketStatus->value,
            Name::getDatabaseTableColumnName() => fake()->unique()->words(2, true),
            Hue::getDatabaseTableColumnName() => fake()->numberBetween(0, 359),
            Category::getDatabaseTableColumnName() => TicketStatusCategoryEnum::Open->value,
            Position::getDatabaseTableColumnName() => 100,
            IsDefault::getDatabaseTableColumnName() => false,
        ];
    }

    public function label(): self
    {
        return $this->state([
            ListName::getDatabaseTableColumnName() => ChoiceListEnum::Label->value,
            Category::getDatabaseTableColumnName() => null,
        ]);
    }

    public function closedTicketStatus(): self
    {
        return $this->state([Category::getDatabaseTableColumnName() => TicketStatusCategoryEnum::Closed->value]);
    }

    public function ticketPriority(): self
    {
        return $this->state([
            ListName::getDatabaseTableColumnName() => ChoiceListEnum::TicketPriority->value,
            Category::getDatabaseTableColumnName() => null,
        ]);
    }

    public function customerType(): self
    {
        return $this->state([
            ListName::getDatabaseTableColumnName() => ChoiceListEnum::CustomerType->value,
            Category::getDatabaseTableColumnName() => null,
        ]);
    }
}
