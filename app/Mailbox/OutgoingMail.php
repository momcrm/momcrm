<?php

declare(strict_types=1);

namespace App\Mailbox;

use App\MailboxMessage\MailboxMessage;
use App\Shared\Types;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

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
     * The subject this goes out with: the ticket's title for a new thread, or
     * the subject of the message it answers with "Re:" before it.
     */
    public function subjectLine(): string
    {
        $answered = $this->answered->isNull() ? null : $this->answered->getSubject()->toNullableString();

        if ($answered === null) {
            return $this->tagged($this->subject);
        }

        return $this->tagged(preg_match('/^re:/i', mb_trim($answered)) === 1 ? $answered : "Re: {$answered}");
    }

    /**
     * The whole message, for the mail services that are handed one to send
     * as it is. A reply names the message it answers and the thread before
     * it, so mail programs show it in the same conversation. It is given a
     * Message-ID here, so the id is known whether or not the service says.
     */
    public function toEmail(Address $from): Email
    {
        $email = new Email()
            ->from($from)
            ->to(...$this->to)
            ->subject($this->subjectLine())
            ->text($this->bodyText)
            ->html($this->bodyHtml());

        $answeredId = $this->answered->isNull() ? null : $this->answered->getInternetMessageId()->toNullableString();

        if ($answeredId !== null) {
            $email->getHeaders()->addIdHeader('In-Reply-To', $answeredId);
            $email->getHeaders()->addIdHeader('References', [...Types::stringList($this->answered->getReferenceIds()->toArray()), $answeredId]);
        }

        $email->getHeaders()->addIdHeader('Message-ID', $email->generateMessageId());

        return $email;
    }

    /**
     * The Message-ID an email built here was given.
     */
    public static function messageId(Email $email): ?string
    {
        $ids = $email->getHeaders()->getHeaderBody('Message-ID');
        $id = is_array($ids) ? ($ids[0] ?? null) : $ids;

        return is_string($id) && $id !== '' ? $id : null;
    }

    /**
     * What staff wrote as simple HTML, keeping its line breaks.
     */
    public function bodyHtml(): string
    {
        return nl2br(e($this->bodyText), false);
    }
}
