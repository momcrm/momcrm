<?php

declare(strict_types=1);

namespace App\Comment\Factories;

use App\Comment\CommentEloquentModel;
use App\Comment\Properties\Body;
use App\Comment\Properties\Mentions;
use App\Comment\Properties\TeamId;
use App\Comment\Properties\TicketId;
use App\Comment\Properties\UserId;
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
            TeamId::getDatabaseTableColumnName() => fn (array $attributes): string => (string) TicketEloquentModel::query()
                ->withoutGlobalScopes()
                ->whereKey($attributes[TicketId::getDatabaseTableColumnName()])
                ->value('team_id'),
            UserId::getDatabaseTableColumnName() => UserEloquentModel::factory(),
            Body::getDatabaseTableColumnName() => fake()->sentence(),
            Mentions::getDatabaseTableColumnName() => null,
        ];
    }
}
