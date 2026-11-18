<?php

declare(strict_types=1);

namespace App\Connector\Types\Imap;

use App\Connector\Actions\MarkConnectorAsNeedingReconnect;
use App\Connector\Connector;
use App\Connector\Exceptions\CouldNotReachConnector;
use App\Mailbox\Contracts\MailClient;
use App\Mailbox\OutgoingMail;
use App\Mailbox\SentMail;
use App\MailboxMessage\HtmlToText;
use App\MailboxMessage\MailboxMessage;
use App\MailboxMessage\Properties\InternetMessageId;
use Carbon\CarbonImmutable;
use Symfony\Component\Mime\Address;

/**
 * A mailbox on any mail server, read over IMAP and sent from over SMTP.
 *
 * A message is known by the inbox's number and its own, such as "7:1024":
 * the message's number alone stops meaning anything if the server rebuilds
 * the inbox. A reply is sent as a new email and is not copied to the
 * mailbox's Sent folder.
 */
final class ImapMailClient implements MailClient
{
    private ?ImapInbox $inbox = null;

    /**
     * Messages already fetched by this client, by their number.
     *
     * @var array<int, array{headers: array<string, string>, text: ?string, html: ?string, received_at: ?CarbonImmutable, is_draft: bool, attachments: list<array{name: string, content_type: ?string, size: int, is_inline: bool, content: string}>}|null>
     */
    private array $fetched = [];

    private function __construct(
        private readonly MailServers $servers,
        private readonly MailServerSettings $settings,
        private readonly Connector $connector,
    ) {}

    public static function forConnector(Connector $connector): self
    {
        return new self(app(MailServers::class), MailServerSettings::fromConnector($connector), $connector);
    }

    public function checkInbox(): void
    {
        $this->inbox();
    }

    /**
     * IMAP searches by day, so what arrived earlier on the day is left out here.
     */
    public function inboxMessagesSince(CarbonImmutable $since, int $limit): array
    {
        $inbox = $this->inbox();
        $messages = [];

        foreach ($inbox->since($since) as $listed) {
            if ($listed['received_at']->greaterThanOrEqualTo($since)) {
                $messages[] = ['id' => "{$inbox->uidValidity()}:{$listed['uid']}", 'received_at' => $listed['received_at']];
            }
        }

        usort($messages, fn (array $a, array $b): int => $a['received_at'] <=> $b['received_at']);

        return array_slice($messages, 0, $limit);
    }

    public function message(string $id): ?MailboxMessage
    {
        $message = $this->raw($id);

        if ($message === null || $message['is_draft']) {
            return null;
        }

        $text = $message['text'] ?? ($message['html'] === null ? null : HtmlToText::convert($message['html']));

        return MailboxMessage::fromHeaders($message['headers'], $id, $text, $message['received_at'])
            ->setHasAttachments($message['attachments'] !== []);
    }

    /**
     * A file is known by its place among the message's files.
     */
    public function attachments(string $messageId): array
    {
        $attachments = [];

        foreach (($this->raw($messageId) ?? ['attachments' => []])['attachments'] as $index => $attachment) {
            $attachments[] = [
                'id' => (string) $index,
                'name' => $attachment['name'],
                'content_type' => $attachment['content_type'],
                'size' => $attachment['size'],
                'is_file' => true,
                'is_inline' => $attachment['is_inline'],
            ];
        }

        return $attachments;
    }

    public function attachmentContents(string $messageId, string $attachmentId): string
    {
        $attachment = ($this->raw($messageId) ?? ['attachments' => []])['attachments'][(int) $attachmentId] ?? null;

        if ($attachment === null || ! ctype_digit($attachmentId)) {
            throw CouldNotReachConnector::refused((string) __('That attachment is no longer on the message.'));
        }

        return $attachment['content'];
    }

    public function send(OutgoingMail $mail): SentMail
    {
        $email = $mail->toEmail(new Address($this->settings->fromAddress, $this->settings->fromName ?? ''));

        try {
            $this->servers->send($this->settings, $email);
        } catch (CouldNotReachConnector $exception) {
            throw $this->remembering($exception);
        }

        return new SentMail(null, InternetMessageId::normalize(OutgoingMail::messageId($email)), $email->getSubject());
    }

    /**
     * @throws CouldNotReachConnector
     */
    private function inbox(): ImapInbox
    {
        try {
            return $this->inbox ??= $this->servers->inbox($this->settings);
        } catch (CouldNotReachConnector $exception) {
            throw $this->remembering($exception);
        }
    }

    /**
     * The message with this id; null when it has gone or the inbox it was
     * numbered in has been rebuilt.
     *
     * @return array{headers: array<string, string>, text: ?string, html: ?string, received_at: ?CarbonImmutable, is_draft: bool, attachments: list<array{name: string, content_type: ?string, size: int, is_inline: bool, content: string}>}|null
     *
     * @throws CouldNotReachConnector
     */
    private function raw(string $id): ?array
    {
        [$uidValidity, $uid] = [...explode(':', $id, 2), ''];
        $inbox = $this->inbox();

        if (! ctype_digit($uidValidity) || ! ctype_digit($uid) || (int) $uidValidity !== $inbox->uidValidity()) {
            return null;
        }

        if (! array_key_exists((int) $uid, $this->fetched)) {
            $this->fetched[(int) $uid] = $inbox->message((int) $uid);
        }

        return $this->fetched[(int) $uid];
    }

    /**
     * When the server refuses the stored username and password, nothing
     * will work until they are entered again, so the connector says so.
     */
    private function remembering(CouldNotReachConnector $exception): CouldNotReachConnector
    {
        return $exception->needsReconnect ? MarkConnectorAsNeedingReconnect::fromConnector($this->connector) : $exception;
    }
}
