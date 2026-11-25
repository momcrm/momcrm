<?php

declare(strict_types=1);

namespace App\Message\Actions;

use App\Conversation\ConversationEloquentModel;
use App\Conversation\Properties\LastMessageAt;
use App\ConversationParticipant\Actions\MarkConversationRead;
use App\ConversationParticipant\ConversationParticipantEloquentModel;
use App\ConversationParticipant\Properties\ConversationId;
use App\ConversationParticipant\Properties\UserId;
use App\Message\Message;
use App\Message\MessageEloquentModel;
use App\Message\Notifications\MessageReceivedNotification;
use App\Message\Properties\Id;
use App\User\UserEloquentModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Sends a message in a conversation. The sender has read it; the others may
 * be emailed about it a few minutes later if they haven't.
 */
final readonly class CreateMessage
{
    public function __construct(
        private Message $message,
    ) {}

    public function __invoke(): Message
    {
        return DB::transaction(function (): Message {
            if ($this->message->getId()->isNull()) {
                $this->message->setId(Id::generate());
            }

            $model = MessageEloquentModel::query()->withoutGlobalScopes()->create($this->message->forDatabaseCreate());

            ConversationEloquentModel::query()
                ->withoutGlobalScopes()
                ->whereKey($model->conversation_id)
                ->update([LastMessageAt::getDatabaseTableColumnName() => $model->created_at]);

            if ($model->user_id !== null) {
                MarkConversationRead::forParticipant($model->conversation_id, $model->user_id);
            }

            $recipients = UserEloquentModel::query()
                ->whereIn('id', ConversationParticipantEloquentModel::query()
                    ->where(ConversationId::getDatabaseTableColumnName(), $model->conversation_id)
                    ->where(UserId::getDatabaseTableColumnName(), '!=', $model->user_id)
                    ->select(UserId::getDatabaseTableColumnName()))
                ->get();

            if ($recipients->isNotEmpty()) {
                $author = $model->user_id === null ? null : UserEloquentModel::query()->find($model->user_id);

                Notification::send($recipients, new MessageReceivedNotification(
                    messageId: $model->id,
                    conversationId: $model->conversation_id,
                    authorName: $author instanceof UserEloquentModel ? $author->name : __('Someone'),
                    body: $model->body,
                ));
            }

            return Message::fromEloquentModel($model->refresh());
        });
    }

    public static function fromMessage(Message $message): Message
    {
        return new self($message)->handle();
    }

    public function handle(): Message
    {
        return $this->__invoke();
    }

    public function execute(): Message
    {
        return $this->__invoke();
    }
}
