<?php

declare(strict_types=1);

namespace App\Comment\Notifications;

use App\Inbox\InboxKindEnum;
use App\Inbox\InboxNotification;
use App\Inbox\InboxPayload;
use App\Record\RecordEloquentModel;
use App\User\UserEloquentModel;

/**
 * Tells someone they were @mentioned in a comment on a custom object's record.
 */
final class MentionedInRecordCommentNotification extends InboxNotification
{
    public function __construct(
        public readonly RecordEloquentModel $record,
        public readonly string $objectName,
        public readonly ?UserEloquentModel $actor,
        public readonly string $excerpt,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return InboxPayload::forRecord(InboxKindEnum::Mention, $this->record, $this->objectName, $this->actor, __('mentioned you'), $this->excerpt);
    }
}
