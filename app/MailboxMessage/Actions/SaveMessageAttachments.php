<?php

declare(strict_types=1);

namespace App\MailboxMessage\Actions;

use App\Attachment\Actions\CreateAttachment;
use App\Mailbox\Exceptions\CouldNotReachMailbox;
use App\Mailbox\MicrosoftGraph;
use App\MailboxMessage\MailboxMessage;
use App\Ticket\TicketEloquentModel;

/**
 * Saves the files attached to an email on the ticket it opened or was added
 * to. Images shown inside the message, such as signature logos, are left out,
 * as are linked and embedded items, files over the size people may upload and
 * any beyond the number of files people may upload at once.
 */
final readonly class SaveMessageAttachments
{
    public const int MAX_FILES = 10;

    public const int MAX_BYTES = 20 * 1024 * 1024;

    public function __construct(
        private MicrosoftGraph $graph,
        private MailboxMessage $message,
    ) {}

    /**
     * The number of files saved.
     *
     * @throws CouldNotReachMailbox
     */
    public function __invoke(): int
    {
        $microsoftId = $this->message->getMicrosoftId()->toNullableString();
        $ticket = TicketEloquentModel::query()->withoutGlobalScopes()->find($this->message->getTicketId()->toNullableString());

        if ($microsoftId === null || $ticket === null) {
            return 0;
        }

        $sender = $this->message->getFromName()->toNullableString() ?? $this->message->getFromAddress()->toNullableString();
        $saved = 0;

        foreach ($this->graph->attachments($microsoftId) as $attachment) {
            if ($saved >= self::MAX_FILES) {
                break;
            }

            if (! $attachment['is_file'] || $attachment['is_inline'] || $attachment['size'] > self::MAX_BYTES) {
                continue;
            }

            CreateAttachment::fromContents(
                $attachment['name'],
                $this->graph->attachmentContents($microsoftId, $attachment['id']),
                $attachment['content_type'],
                $ticket,
                $sender,
            );

            $saved++;
        }

        return $saved;
    }

    /**
     * @throws CouldNotReachMailbox
     */
    public static function fromMessage(MicrosoftGraph $graph, MailboxMessage $message): int
    {
        return new self($graph, $message)->handle();
    }

    /**
     * @throws CouldNotReachMailbox
     */
    public function handle(): int
    {
        return $this->__invoke();
    }

    /**
     * @throws CouldNotReachMailbox
     */
    public function execute(): int
    {
        return $this->__invoke();
    }
}
