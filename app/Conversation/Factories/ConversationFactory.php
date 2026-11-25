<?php

declare(strict_types=1);

namespace App\Conversation\Factories;

use App\Conversation\ConversationEloquentModel;
use App\Conversation\Properties\TeamId;
use App\ConversationParticipant\ConversationParticipantEloquentModel;
use App\ConversationParticipant\Properties\ConversationId;
use App\ConversationParticipant\Properties\UserId;
use App\Team\TeamEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConversationEloquentModel>
 */
final class ConversationFactory extends Factory
{
    protected $model = ConversationEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            TeamId::getDatabaseTableColumnName() => TeamEloquentModel::factory(),
        ];
    }

    /**
     * A conversation between these people, in the first one's current team.
     */
    public function between(UserEloquentModel ...$people): self
    {
        return $this
            ->state([TeamId::getDatabaseTableColumnName() => $people[0]->current_team_id])
            ->afterCreating(function (ConversationEloquentModel $conversation) use ($people): void {
                foreach ($people as $person) {
                    ConversationParticipantEloquentModel::factory()->create([
                        ConversationId::getDatabaseTableColumnName() => $conversation->id,
                        UserId::getDatabaseTableColumnName() => $person->id,
                    ]);
                }
            });
    }
}
