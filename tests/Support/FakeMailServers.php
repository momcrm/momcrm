<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Connector\Exceptions\CouldNotReachConnector;
use App\Connector\Types\Imap\ImapInbox;
use App\Connector\Types\Imap\MailServers;
use App\Connector\Types\Imap\MailServerSettings;
use Carbon\CarbonImmutable;
use Symfony\Component\Mime\Email;

/**
 * Stands in for the IMAP and SMTP servers of every IMAP/SMTP connector. It
 * holds one inbox, records what was asked of it, and can be told to fail.
 */
final class FakeMailServers implements ImapInbox, MailServers
{
    public int $uidValidity = 7;

    /**
     * The inbox's messages by their number.
     *
     * @var array<int, array{headers: array<string, string>, text: ?string, html: ?string, received_at: ?CarbonImmutable, is_draft: bool, attachments: list<array{name: string, content_type: ?string, size: int, is_inline: bool, content: string}>}>
     */
    public array $messages = [];

    /** @var list<Email> */
    public array $sent = [];

    /** @var list<MailServerSettings> */
    public array $imapSignIns = [];

    /** @var list<MailServerSettings> */
    public array $smtpSignIns = [];

    /** @var list<int> */
    public array $fetched = [];

    public ?CouldNotReachConnector $imapFailure = null;

    public ?CouldNotReachConnector $smtpFailure = null;

    /**
     * Puts a message in the inbox.
     *
     * @param  array<string, string>  $headers
     * @param  list<array{name: string, content_type: ?string, size: int, is_inline: bool, content: string}>  $attachments
     */
    public function deliver(int $uid, string $receivedAt, array $headers = [], ?string $text = 'The printer on level 2 is offline.', ?string $html = null, bool $isDraft = false, array $attachments = []): self
    {
        $this->messages[$uid] = [
            'headers' => [
                'from' => 'Kim Lee <Kim@Acme.test>',
                'to' => 'help@helpdesk.test',
                'subject' => 'Printer offline',
                'message-id' => "<msg-{$uid}@acme.test>",
                ...$headers,
            ],
            'text' => $text,
            'html' => $html,
            'received_at' => CarbonImmutable::parse($receivedAt, 'UTC'),
            'is_draft' => $isDraft,
            'attachments' => $attachments,
        ];

        return $this;
    }

    public function inbox(MailServerSettings $settings): ImapInbox
    {
        $this->imapSignIns[] = $settings;

        if ($this->imapFailure !== null) {
            throw $this->imapFailure;
        }

        return $this;
    }

    public function checkSmtp(MailServerSettings $settings): void
    {
        $this->smtpSignIns[] = $settings;

        if ($this->smtpFailure !== null) {
            throw $this->smtpFailure;
        }
    }

    public function send(MailServerSettings $settings, Email $email): void
    {
        $this->smtpSignIns[] = $settings;

        if ($this->smtpFailure !== null) {
            throw $this->smtpFailure;
        }

        $this->sent[] = $email;
    }

    public function uidValidity(): int
    {
        return $this->uidValidity;
    }

    /**
     * Like a real server, everything from the start of that day.
     */
    public function since(CarbonImmutable $since): array
    {
        $listed = [];

        foreach ($this->messages as $uid => $message) {
            if ($message['received_at'] !== null && $message['received_at']->greaterThanOrEqualTo($since->utc()->startOfDay())) {
                $listed[] = ['uid' => $uid, 'received_at' => $message['received_at']];
            }
        }

        return $listed;
    }

    public function message(int $uid): ?array
    {
        $this->fetched[] = $uid;

        return $this->messages[$uid] ?? null;
    }
}
