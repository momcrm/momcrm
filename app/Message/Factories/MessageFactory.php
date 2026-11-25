<?php

declare(strict_types=1);

namespace App\Message\Factories;

use App\Conversation\ConversationEloquentModel;
use App\Message\MessageEloquentModel;
use App\Message\Properties\Body;
use App\Message\Properties\ConversationId;
use App\Message\Properties\TeamId;
use App\Message\Properties\UserId;
use App\Shared\Types;
use App\User\UserEloquentModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MessageEloquentModel>
 */
final class MessageFactory extends Factory
{
    protected $model = MessageEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            ConversationId::getDatabaseTableColumnName() => ConversationEloquentModel::factory(),
            TeamId::getDatabaseTableColumnName() => fn (array $attributes): string => Types::string(ConversationEloquentModel::query()
                ->withoutGlobalScopes()
                ->whereKey($attributes[ConversationId::getDatabaseTableColumnName()])
                ->value('team_id')),
            UserId::getDatabaseTableColumnName() => UserEloquentModel::factory(),
            Body::getDatabaseTableColumnName() => fake()->sentence(),
        ];
    }
}
