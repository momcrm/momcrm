<?php

declare(strict_types=1);

namespace App\Mailbox;

use App\MailboxMessage\MailboxMessage;

/**
 * A reply about a ticket, ready for a mail client to send.
 */
final readonly class OutgoingMail
{
    /**
     * @param  list<string>  $to  the addresses it goes to
     * @param  string  $bodyText  what staff wrote, as plain text
     * @param  string  $subject  the subject when this starts a thread
     * @param  string  $subjectTag  the ticket's number, such as "[#123]", which every subject carries so an answer finds the ticket
     * @param  MailboxMessage  $answered  the customer's latest message on the ticket in this mailbox; empty when there is none
     */
    public function __construct(
        public array $to,
        public string $bodyText,
        public string $subject,
        public string $subjectTag,
        public MailboxMessage $answered,
    ) {}

    /**
     * A subject with the ticket's number in it, such as "Printer offline
     * [#123]", unless it is already there.
     */
    public function tagged(string $subject): string
    {
        return str_contains($subject, $this->subjectTag) ? $subject : mb_trim("{$subject} {$this->subjectTag}");
    }

    /**
     * What staff wrote as simple HTML, keeping its line breaks.
     */
    public function bodyHtml(): string
    {
        return nl2br(e($this->bodyText), false);
    }
}
