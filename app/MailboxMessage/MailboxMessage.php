<?php

declare(strict_types=1);

namespace App\MailboxMessage;

use App\MailboxMessage\Factories\MailboxMessageFactory;
use App\MailboxMessage\Properties\BodyText;
use App\MailboxMessage\Properties\CommentId;
use App\MailboxMessage\Properties\CreatedAt;
use App\MailboxMessage\Properties\Direction;
use App\MailboxMessage\Properties\Error;
use App\MailboxMessage\Properties\ExternalId;
use App\MailboxMessage\Properties\FromAddress;
use App\MailboxMessage\Properties\FromName;
use App\MailboxMessage\Properties\HasAttachments;
use App\MailboxMessage\Properties\Id;
use App\MailboxMessage\Properties\InReplyTo;
use App\MailboxMessage\Properties\InternetMessageId;
use App\MailboxMessage\Properties\MailboxId;
use App\MailboxMessage\Properties\ReceivedAt;
use App\MailboxMessage\Properties\ReferenceIds;
use App\MailboxMessage\Properties\ReplyText;
use App\MailboxMessage\Properties\Status;
use App\MailboxMessage\Properties\Subject;
use App\MailboxMessage\Properties\TeamId;
use App\MailboxMessage\Properties\TicketId;
use App\MailboxMessage\Properties\ToRecipients;
use App\MailboxMessage\Properties\UpdatedAt;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;
use Throwable;

/**
 * An email read from, or sent through, a connected mailbox.
 *
 * @extends AbstractData<MailboxMessageEloquentModel>
 */
final class MailboxMessage extends AbstractData
{
    public function __construct(
        public BodyText $bodyText,
        public CommentId $commentId,
        public CreatedAt $createdAt,
        public Direction $direction,
        public Error $error,
        public FromAddress $fromAddress,
        public FromName $fromName,
        public HasAttachments $hasAttachments,
        public Id $id,
        public InReplyTo $inReplyTo,
        public InternetMessageId $internetMessageId,
        public MailboxId $mailboxId,
        public ExternalId $externalId,
        public ReceivedAt $receivedAt,
        public ReferenceIds $referenceIds,
        public ReplyText $replyText,
        public Status $status,
        public Subject $subject,
        public TeamId $teamId,
        public TicketId $ticketId,
        public ToRecipients $toRecipients,
        public UpdatedAt $updatedAt,
    ) {}

    public static function getFactory(): MailboxMessageFactory
    {
        return MailboxMessageEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $items
     */
    public static function collect(Collection|array $items, string $method = 'fromData'): MailboxMessageCollection
    {
        if ($items instanceof MailboxMessageCollection) {
            return $items;
        }

        return MailboxMessageCollection::make(parent::collect($items, $method)->all());
    }

    /**
     * A message as Microsoft Graph returns it, asked for with its headers and
     * with its body as plain text. Addresses are lower-cased, and ids lose
     * their angle brackets, so they compare with what is stored.
     *
     * @param  array<string, mixed>  $message
     */
    public static function fromMicrosoftGraph(array $message): self
    {
        $headers = self::headers($message);
        $externalId = Arr::get($message, 'id');
        $bodyText = self::text(Arr::get($message, 'body.content'));

        return self::new()
            ->setDirection(MailboxMessageDirectionEnum::Inbound)
            ->setExternalId(is_string($externalId) ? $externalId : null)
            ->setInternetMessageId(InternetMessageId::normalize(Arr::get($message, 'internetMessageId')))
            ->setInReplyTo(InternetMessageId::normalize($headers['in-reply-to'] ?? null))
            ->setReferenceIds(self::referenceIds($headers['references'] ?? null))
            ->setFromAddress(self::address(Arr::get($message, 'from.emailAddress.address')))
            ->setFromName(self::text(Arr::get($message, 'from.emailAddress.name')))
            ->setToRecipients(self::addresses(Arr::get($message, 'toRecipients')))
            ->setSubject(self::text(Arr::get($message, 'subject')))
            ->setBodyText($bodyText)
            ->setReplyText(self::text(Arr::get($message, 'uniqueBody.content')) ?? $bodyText)
            ->setReceivedAt(self::date(Arr::get($message, 'receivedDateTime')));
    }

    /**
     * A message as its own headers describe it, for the mail services that
     * hand us the headers and the text and leave the rest to us. Header names
     * are in lower case. What the sender wrote is told apart from the quoted
     * history by QuotedReply.
     *
     * @param  array<string, string>  $headers
     */
    public static function fromHeaders(array $headers, ?string $externalId, ?string $bodyText, ?CarbonImmutable $receivedAt): self
    {
        $headers = array_map(self::decodeHeader(...), $headers);
        $from = self::sender($headers['from'] ?? '');
        $bodyText = self::text($bodyText);

        return self::new()
            ->setDirection(MailboxMessageDirectionEnum::Inbound)
            ->setExternalId($externalId)
            ->setInternetMessageId(InternetMessageId::normalize($headers['message-id'] ?? null))
            ->setInReplyTo(InternetMessageId::normalize($headers['in-reply-to'] ?? null))
            ->setReferenceIds(self::referenceIds($headers['references'] ?? null))
            ->setFromAddress($from['address'])
            ->setFromName($from['name'])
            ->setToRecipients(self::addressesIn($headers['to'] ?? ''))
            ->setSubject(self::text($headers['subject'] ?? null))
            ->setBodyText($bodyText)
            ->setReplyText(QuotedReply::strip($bodyText))
            ->setReceivedAt($receivedAt?->utc());
    }

    /**
     * Every id this message says it follows, most recent first: the message
     * it answers, then the rest of the thread.
     *
     * @return list<string>
     */
    public function threadIds(): array
    {
        $ids = array_reverse($this->getReferenceIds()->toArray());
        $inReplyTo = $this->getInReplyTo()->toNullableString();

        if ($inReplyTo !== null) {
            array_unshift($ids, $inReplyTo);
        }

        return array_values(array_unique(array_filter($ids, is_string(...))));
    }

    public function getBodyText(): BodyText
    {
        return $this->bodyText;
    }

    public function setBodyText(mixed $bodyText): self
    {
        $this->bodyText = new BodyText($bodyText instanceof AbstractValue ? $bodyText->toValue() : $bodyText);

        return $this;
    }

    public function getCommentId(): CommentId
    {
        return $this->commentId;
    }

    public function setCommentId(mixed $commentId): self
    {
        $this->commentId = new CommentId($commentId instanceof AbstractValue ? $commentId->toValue() : $commentId);

        return $this;
    }

    public function getCreatedAt(): CreatedAt
    {
        return $this->createdAt;
    }

    public function getDirection(): Direction
    {
        return $this->direction;
    }

    public function setDirection(mixed $direction): self
    {
        $this->direction = new Direction($direction instanceof AbstractValue ? $direction->toValue() : $direction);

        return $this;
    }

    public function getError(): Error
    {
        return $this->error;
    }

    public function setError(mixed $error): self
    {
        $this->error = new Error($error instanceof AbstractValue ? $error->toValue() : $error);

        return $this;
    }

    public function getFromAddress(): FromAddress
    {
        return $this->fromAddress;
    }

    public function setFromAddress(mixed $fromAddress): self
    {
        $this->fromAddress = new FromAddress($fromAddress instanceof AbstractValue ? $fromAddress->toValue() : $fromAddress);

        return $this;
    }

    public function getFromName(): FromName
    {
        return $this->fromName;
    }

    public function setFromName(mixed $fromName): self
    {
        $this->fromName = new FromName($fromName instanceof AbstractValue ? $fromName->toValue() : $fromName);

        return $this;
    }

    public function getHasAttachments(): HasAttachments
    {
        return $this->hasAttachments;
    }

    public function setHasAttachments(mixed $hasAttachments): self
    {
        $this->hasAttachments = new HasAttachments($hasAttachments instanceof AbstractValue ? $hasAttachments->toValue() : $hasAttachments);

        return $this;
    }

    public function getId(): Id
    {
        return $this->id;
    }

    public function setId(mixed $id): self
    {
        $this->id = new Id($id instanceof AbstractValue ? $id->toValue() : $id);

        return $this;
    }

    public function getInReplyTo(): InReplyTo
    {
        return $this->inReplyTo;
    }

    public function setInReplyTo(mixed $inReplyTo): self
    {
        $this->inReplyTo = new InReplyTo($inReplyTo instanceof AbstractValue ? $inReplyTo->toValue() : $inReplyTo);

        return $this;
    }

    public function getInternetMessageId(): InternetMessageId
    {
        return $this->internetMessageId;
    }

    public function setInternetMessageId(mixed $internetMessageId): self
    {
        $this->internetMessageId = new InternetMessageId($internetMessageId instanceof AbstractValue ? $internetMessageId->toValue() : $internetMessageId);

        return $this;
    }

    public function getMailboxId(): MailboxId
    {
        return $this->mailboxId;
    }

    public function setMailboxId(mixed $mailboxId): self
    {
        $this->mailboxId = new MailboxId($mailboxId instanceof AbstractValue ? $mailboxId->toValue() : $mailboxId);

        return $this;
    }

    public function getExternalId(): ExternalId
    {
        return $this->externalId;
    }

    public function setExternalId(mixed $externalId): self
    {
        $this->externalId = new ExternalId($externalId instanceof AbstractValue ? $externalId->toValue() : $externalId);

        return $this;
    }

    public function getReceivedAt(): ReceivedAt
    {
        return $this->receivedAt;
    }

    public function setReceivedAt(mixed $receivedAt): self
    {
        $this->receivedAt = new ReceivedAt($receivedAt instanceof AbstractValue ? $receivedAt->toValue() : $receivedAt);

        return $this;
    }

    public function getReferenceIds(): ReferenceIds
    {
        return $this->referenceIds;
    }

    public function setReferenceIds(mixed $referenceIds): self
    {
        $this->referenceIds = new ReferenceIds($referenceIds instanceof AbstractValue ? $referenceIds->toValue() : $referenceIds);

        return $this;
    }

    public function getReplyText(): ReplyText
    {
        return $this->replyText;
    }

    public function setReplyText(mixed $replyText): self
    {
        $this->replyText = new ReplyText($replyText instanceof AbstractValue ? $replyText->toValue() : $replyText);

        return $this;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function setStatus(mixed $status): self
    {
        $this->status = new Status($status instanceof AbstractValue ? $status->toValue() : $status);

        return $this;
    }

    public function getSubject(): Subject
    {
        return $this->subject;
    }

    public function setSubject(mixed $subject): self
    {
        $this->subject = new Subject($subject instanceof AbstractValue ? $subject->toValue() : $subject);

        return $this;
    }

    public function getTeamId(): TeamId
    {
        return $this->teamId;
    }

    public function setTeamId(mixed $teamId): self
    {
        $this->teamId = new TeamId($teamId instanceof AbstractValue ? $teamId->toValue() : $teamId);

        return $this;
    }

    public function getTicketId(): TicketId
    {
        return $this->ticketId;
    }

    public function setTicketId(mixed $ticketId): self
    {
        $this->ticketId = new TicketId($ticketId instanceof AbstractValue ? $ticketId->toValue() : $ticketId);

        return $this;
    }

    public function getToRecipients(): ToRecipients
    {
        return $this->toRecipients;
    }

    public function setToRecipients(mixed $toRecipients): self
    {
        $this->toRecipients = new ToRecipients($toRecipients instanceof AbstractValue ? $toRecipients->toValue() : $toRecipients);

        return $this;
    }

    public function getUpdatedAt(): UpdatedAt
    {
        return $this->updatedAt;
    }

    /**
     * The message's headers by lower-case name. When a header repeats, the
     * first one, which is the one nearest the top, is kept.
     *
     * @param  array<string, mixed>  $message
     * @return array<string, string>
     */
    private static function headers(array $message): array
    {
        $headers = [];
        $entries = Arr::get($message, 'internetMessageHeaders');

        foreach (is_array($entries) ? $entries : [] as $entry) {
            $name = is_array($entry) ? ($entry['name'] ?? null) : null;
            $value = is_array($entry) ? ($entry['value'] ?? null) : null;

            if (is_string($name) && is_string($value) && ! isset($headers[mb_strtolower($name)])) {
                $headers[mb_strtolower($name)] = $value;
            }
        }

        return $headers;
    }

    /**
     * The ids in a References header, which lists them separated by space.
     *
     * @return list<string>|null
     */
    private static function referenceIds(?string $header): ?array
    {
        if ($header === null) {
            return null;
        }

        $ids = [];

        foreach (preg_split('/[\s,]+/', $header) ?: [] as $id) {
            $id = InternetMessageId::normalize($id);

            if ($id !== null) {
                $ids[] = $id;
            }
        }

        return $ids === [] ? null : $ids;
    }

    private static function address(mixed $address): ?string
    {
        $address = self::text($address);

        return $address === null ? null : mb_strtolower($address);
    }

    /**
     * @return list<string>|null
     */
    private static function addresses(mixed $recipients): ?array
    {
        $addresses = [];

        foreach (is_array($recipients) ? $recipients : [] as $recipient) {
            $address = self::address(Arr::get(is_array($recipient) ? $recipient : [], 'emailAddress.address'));

            if ($address !== null) {
                $addresses[] = $address;
            }
        }

        return $addresses === [] ? null : $addresses;
    }

    /**
     * A header as people read it: mail programs encode anything that is not
     * plain ASCII, such as "=?UTF-8?B?…?=".
     */
    private static function decodeHeader(string $value): string
    {
        $decoded = iconv_mime_decode($value, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');

        return $decoded === false ? $value : $decoded;
    }

    /**
     * The name and address in a From header, such as "Kim Lee <kim@acme.test>".
     *
     * @return array{name: ?string, address: ?string}
     */
    private static function sender(string $header): array
    {
        if (preg_match('/^(.*)<([^<>\s]+@[^<>\s]+)>\s*$/', $header, $matches) === 1) {
            return ['name' => self::text(mb_trim($matches[1], " \n\r\t\v\0\"'")), 'address' => self::address($matches[2])];
        }

        return ['name' => null, 'address' => self::addressesIn($header)[0] ?? null];
    }

    /**
     * Every email address in a header, in lower case.
     *
     * @return list<string>|null
     */
    private static function addressesIn(string $header): ?array
    {
        preg_match_all('/[^\s<>,;:"\'()]+@[^\s<>,;:"\'()]+/', $header, $matches);

        $addresses = array_values(array_unique(array_map(mb_strtolower(...), $matches[0])));

        return $addresses === [] ? null : $addresses;
    }

    private static function text(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = mb_trim($value);

        return $value === '' ? null : $value;
    }

    private static function date(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->utc();
        } catch (Throwable) {
            return null;
        }
    }
}
