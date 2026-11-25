<?php

declare(strict_types=1);

namespace App\ConversationParticipant\Factories;

use App\Conversation\ConversationEloquentModel;
use App\ConversationParticipant\ConversationParticipantEloquentModel;
use App\ConversationParticipant\Properties\ConversationId;
use App\ConversationParticipant\Properties\UserId;
use App\User\UserEloquentModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConversationParticipantEloquentModel>
 */
final class ConversationParticipantFactory extends Factory
{
    protected $model = ConversationParticipantEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            ConversationId::getDatabaseTableColumnName() => ConversationEloquentModel::factory(),
            UserId::getDatabaseTableColumnName() => UserEloquentModel::factory(),
        ];
    }
}
