<?php

declare(strict_types=1);

namespace App\Conversation\Actions;

use App\Conversation\Conversation;
use App\Conversation\ConversationEloquentModel;
use App\Conversation\Properties\Id;
use App\Conversation\Properties\TeamId;
use App\ConversationParticipant\ConversationParticipantEloquentModel;
use App\ConversationParticipant\Properties\ConversationId;
use App\ConversationParticipant\Properties\UserId;
use App\Message\Actions\CreateMessage;
use App\Message\Message;
use Illuminate\Support\Facades\DB;

/**
 * Sends a first message to one or more teammates. If the sender already has
 * a conversation with exactly those people, the message goes there instead of
 * starting another.
 */
final readonly class StartConversation
{
    /**
     * @param  list<string>  $recipientIds
     */
    public function __construct(
        private string $teamId,
        private string $senderId,
        private array $recipientIds,
        private string $body,
    ) {}

    public function __invoke(): Conversation
    {
        return DB::transaction(function (): Conversation {
            $everyone = array_values(array_unique([$this->senderId, ...$this->recipientIds]));
            $conversation = FindConversation::betweenExactly($this->teamId, $everyone);

            if ($conversation->isNull()) {
                $conversation = Conversation::fromEloquentModel(ConversationEloquentModel::query()->withoutGlobalScopes()->create([
                    Id::getDatabaseTableColumnName() => Id::generate()->toString(),
                    TeamId::getDatabaseTableColumnName() => $this->teamId,
                ]));

                foreach ($everyone as $userId) {
                    ConversationParticipantEloquentModel::query()->create([
                        ConversationId::getDatabaseTableColumnName() => $conversation->getId()->toString(),
                        UserId::getDatabaseTableColumnName() => $userId,
                    ]);
                }
            }

            CreateMessage::fromMessage(Message::new()
                ->setTeamId($this->teamId)
                ->setConversationId($conversation->getId())
                ->setUserId($this->senderId)
                ->setBody($this->body));

            return $conversation;
        });
    }

    /**
     * @param  list<string>  $recipientIds
     */
    public static function from(string $teamId, string $senderId, array $recipientIds, string $body): Conversation
    {
        return new self($teamId, $senderId, $recipientIds, $body)->handle();
    }

    public function handle(): Conversation
    {
        return $this->__invoke();
    }

    public function execute(): Conversation
    {
        return $this->__invoke();
    }
}
