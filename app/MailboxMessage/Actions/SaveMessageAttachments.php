<?php

declare(strict_types=1);

namespace App\MailboxMessage\Actions;

use App\Attachment\Actions\CreateAttachment;
use App\MailboxMessage\MailboxMessage;
use App\Ticket\TicketEloquentModel;
use Mom\Connectors\CouldNotReachConnector;
use Mom\Connectors\Mail\MailClient;

/**
 * Saves the files that came with an email on the ticket it opened or was added
 * to, however many there are. Images shown inside the message are saved too,
 * because a screenshot pasted into an email is often its point. Linked and
 * embedded items are left out, as are files over the size people may upload.
 */
final readonly class SaveMessageAttachments
{
    public const int MAX_BYTES = 20 * 1024 * 1024;

    public function __construct(
        private MailClient $client,
        private MailboxMessage $message,
    ) {}

    /**
     * The number of files saved.
     *
     * @throws CouldNotReachConnector
     */
    public function __invoke(): int
    {
        $externalId = $this->message->getExternalId()->toNullableString();
        $ticket = TicketEloquentModel::query()->withoutGlobalScopes()->find($this->message->getTicketId()->toNullableString());

        if ($externalId === null || $ticket === null) {
            return 0;
        }

        $sender = $this->message->getFromName()->toNullableString() ?? $this->message->getFromAddress()->toNullableString();
        $saved = 0;

        foreach ($this->client->attachments($externalId) as $attachment) {
            if (! $attachment['is_file'] || $attachment['size'] > self::MAX_BYTES) {
                continue;
            }

            CreateAttachment::fromContents(
                $attachment['name'],
                $this->client->attachmentContents($externalId, $attachment['id']),
                $attachment['content_type'],
                $ticket,
                $sender,
            );

            $saved++;
        }

        return $saved;
    }

    /**
     * @throws CouldNotReachConnector
     */
    public static function fromMessage(MailClient $client, MailboxMessage $message): int
    {
        return new self($client, $message)->handle();
    }

    /**
     * @throws CouldNotReachConnector
     */
    public function handle(): int
    {
        return $this->__invoke();
    }

    /**
     * @throws CouldNotReachConnector
     */
    public function execute(): int
    {
        return $this->__invoke();
    }
}
