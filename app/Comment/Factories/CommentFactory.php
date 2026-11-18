<?php

declare(strict_types=1);

namespace App\Comment\Factories;

use App\Comment\CommentEloquentModel;
use App\Comment\CommentVisibilityEnum;
use App\Comment\Properties\Body;
use App\Comment\Properties\Mentions;
use App\Comment\Properties\SenderEmail;
use App\Comment\Properties\SenderName;
use App\Comment\Properties\TeamId;
use App\Comment\Properties\TicketId;
use App\Comment\Properties\UserId;
use App\Comment\Properties\Visibility;
use App\Shared\Types;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommentEloquentModel>
 */
final class CommentFactory extends Factory
{
    protected $model = CommentEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            TicketId::getDatabaseTableColumnName() => TicketEloquentModel::factory(),
            TeamId::getDatabaseTableColumnName() => fn (array $attributes): string => Types::string(TicketEloquentModel::query()
                ->withoutGlobalScopes()
                ->whereKey($attributes[TicketId::getDatabaseTableColumnName()])
                ->value('team_id')),
            UserId::getDatabaseTableColumnName() => UserEloquentModel::factory(),
            Body::getDatabaseTableColumnName() => fake()->sentence(),
            Mentions::getDatabaseTableColumnName() => null,
            Visibility::getDatabaseTableColumnName() => CommentVisibilityEnum::Internal->value,
        ];
    }

    /**
     * A reply the team sent to the customer.
     */
    public function public(): static
    {
        return $this->state([Visibility::getDatabaseTableColumnName() => CommentVisibilityEnum::Public->value]);
    }

    /**
     * Something the customer wrote in by email.
     */
    public function fromCustomer(string $email = 'sam@acme.test', ?string $name = 'Sam Lee'): static
    {
        return $this->public()->state([
            UserId::getDatabaseTableColumnName() => null,
            SenderEmail::getDatabaseTableColumnName() => $email,
            SenderName::getDatabaseTableColumnName() => $name,
        ]);
    }
}
