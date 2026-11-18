<?php

declare(strict_types=1);

namespace App\Connector\Types\Microsoft;

use App\Mailbox\Contracts\MailClient;
use App\Connector\Types\Microsoft\MicrosoftGraph;
use App\Mailbox\OutgoingMail;
use App\Mailbox\SentMail;
use App\MailboxMessage\MailboxMessage;
use App\MailboxMessage\Properties\InternetMessageId;
use App\Shared\Types;
use Carbon\CarbonImmutable;

/**
 * A Microsoft 365 mailbox, through Microsoft Graph.
 */
final readonly class MicrosoftMailClient implements MailClient
{
    public function __construct(
        private MicrosoftGraph $graph,
    ) {}

    public function checkInbox(): void
    {
        $this->graph->inbox();
    }

    public function inboxMessagesSince(CarbonImmutable $since, int $limit): array
    {
        return $this->graph->inboxMessagesSince($since, $limit);
    }

    public function message(string $id): ?MailboxMessage
    {
        $message = $this->graph->message($id);

        if ($message === null || ($message['isDraft'] ?? false) === true) {
            return null;
        }

        return MailboxMessage::fromMicrosoftGraph($message)
            ->setHasAttachments(($message['hasAttachments'] ?? false) === true);
    }

    public function attachments(string $messageId): array
    {
        return $this->graph->attachments($messageId);
    }

    public function attachmentContents(string $messageId, string $attachmentId): string
    {
        return $this->graph->attachmentContents($messageId, $attachmentId);
    }

    /**
     * A reply is drafted from the message it answers, which makes Microsoft
     * thread it and quote the original; otherwise a new draft starts a thread.
     * The id Microsoft gives the draft is the one the sent message keeps.
     */
    public function send(OutgoingMail $mail): SentMail
    {
        $content = [
            'body' => ['contentType' => 'html', 'content' => $mail->bodyHtml()],
            'toRecipients' => array_map(fn (string $address): array => ['emailAddress' => ['address' => $address]], $mail->to),
        ];

        $reply = $mail->answered->isNull()
            ? null
            : $this->graph->createReplyDraft($mail->answered->getExternalId()->toString());

        $draft = $reply === null
            ? $this->graph->createDraft(['subject' => $mail->tagged($mail->subject), ...$content])
            : $this->graph->updateDraft(Types::string($reply['id'] ?? null), [
                'subject' => $mail->tagged(is_string($reply['subject'] ?? null) ? $reply['subject'] : $mail->subject),
                ...$content,
            ]);

        $this->graph->sendDraft(Types::string($draft['id'] ?? null));

        return new SentMail(
            is_string($draft['id'] ?? null) ? $draft['id'] : null,
            InternetMessageId::normalize($draft['internetMessageId'] ?? null),
            is_string($draft['subject'] ?? null) ? $draft['subject'] : null,
        );
    }
}
