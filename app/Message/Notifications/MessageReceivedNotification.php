<?php

declare(strict_types=1);

namespace App\Message\Notifications;

use App\ConversationParticipant\ConversationParticipantEloquentModel;
use App\ConversationParticipant\Properties\ConversationId;
use App\ConversationParticipant\Properties\UserId;
use App\Inbox\InboxKindEnum;
use App\Inbox\InboxMail;
use App\Inbox\NotificationEmails;
use App\Message\MessageEloquentModel;
use App\Message\Properties\ConversationId as MessageConversationId;
use App\Message\Properties\CreatedAt;
use App\Message\Properties\Id;
use App\Message\Properties\UserId as MessageUserId;
use App\Shared\BrandedMailMessage;
use App\User\UserEloquentModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Emails someone a direct message they were sent, if they chose to be
 * emailed about messages. It waits a few minutes first and is not sent if
 * they have read it by then, or were already emailed about an earlier
 * message they still haven't read: one email per run of unread messages.
 */
final class MessageReceivedNotification extends Notification implements ShouldQueueAfterCommit
{
    use Queueable;

    public const int DELAY_MINUTES = 5;

    public function __construct(
        public readonly string $messageId,
        public readonly string $conversationId,
        public readonly string $authorName,
        public readonly string $body,
    ) {
        $this->delay(now()->addMinutes(self::DELAY_MINUTES));
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $notifiable instanceof UserEloquentModel && NotificationEmails::isWantedBy($notifiable, InboxKindEnum::Message) ? ['mail'] : [];
    }

    public function shouldSend(object $notifiable, string $channel): bool
    {
        if (! $notifiable instanceof UserEloquentModel) {
            return false;
        }

        $participant = ConversationParticipantEloquentModel::query()
            ->where(ConversationId::getDatabaseTableColumnName(), $this->conversationId)
            ->where(UserId::getDatabaseTableColumnName(), $notifiable->id)
            ->first();
        $message = MessageEloquentModel::query()->withoutGlobalScopes()->find($this->messageId);

        if (! $participant instanceof ConversationParticipantEloquentModel || ! $message instanceof MessageEloquentModel) {
            return false;
        }

        if ($participant->last_read_at !== null && $message->created_at !== null && $message->created_at->lte($participant->last_read_at)) {
            return false;
        }

        return ! MessageEloquentModel::query()
            ->withoutGlobalScopes()
            ->where(MessageConversationId::getDatabaseTableColumnName(), $this->conversationId)
            ->where(Id::getDatabaseTableColumnName(), '<', $this->messageId)
            ->where(fn (Builder $query) => $query->whereNull(MessageUserId::getDatabaseTableColumnName())->orWhere(MessageUserId::getDatabaseTableColumnName(), '!=', $notifiable->id))
            ->when($participant->last_read_at, fn (Builder $query, mixed $lastReadAt) => $query->where(CreatedAt::getDatabaseTableColumnName(), '>', $lastReadAt))
            ->exists();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return new BrandedMailMessage()
            ->subject(__(':name sent you a message', ['name' => $this->authorName]))
            ->greeting(InboxMail::plain(__(':name sent you a message', ['name' => $this->authorName])))
            ->line(InboxMail::plain(Str::limit($this->body, 1000)))
            ->action(__('Reply in :app', ['app' => config()->string('app.name')]), route('messages.index', ['conversation' => $this->conversationId]))
            ->line(__('Choose which notifications you are emailed in [your settings](:url).', ['url' => route('notification-settings.edit')]));
    }
}
