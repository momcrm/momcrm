<?php

declare(strict_types=1);

namespace App\MailboxMessage;

use App\MailboxMessage\Factories\MailboxMessageFactory;
use App\MailboxMessage\Properties\BodyHtml;
use App\MailboxMessage\Properties\BodyText;
use App\MailboxMessage\Properties\CommentId;
use App\MailboxMessage\Properties\CreatedAt;
use App\MailboxMessage\Properties\Direction;
use App\MailboxMessage\Properties\Error;
use App\MailboxMessage\Properties\ExternalId;
use App\MailboxMessage\Properties\FromAddress;
use App\MailboxMessage\Properties\FromName;
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
use App\Shared\Types;
use Illuminate\Support\Collection;
use Mom\Connectors\Mail\InboundMail;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * An email read from, or sent through, a connected mailbox.
 *
 * @extends AbstractData<MailboxMessageEloquentModel>
 */
final class MailboxMessage extends AbstractData
{
    public function __construct(
        public BodyHtml $bodyHtml,
        public BodyText $bodyText,
        public CommentId $commentId,
        public CreatedAt $createdAt,
        public Direction $direction,
        public Error $error,
        public FromAddress $fromAddress,
        public FromName $fromName,
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
     * An email a mail client read from a mailbox, as the message we keep.
     */
    public static function fromInboundMail(InboundMail $mail): self
    {
        return self::new()
            ->setDirection(MailboxMessageDirectionEnum::Inbound)
            ->setExternalId($mail->externalId)
            ->setInternetMessageId(InternetMessageId::normalize($mail->messageId))
            ->setInReplyTo(InternetMessageId::normalize($mail->inReplyTo))
            ->setReferenceIds($mail->references === [] ? null : $mail->references)
            ->setFromAddress($mail->fromAddress)
            ->setFromName($mail->fromName)
            ->setToRecipients($mail->to === [] ? null : $mail->to)
            ->setSubject($mail->subject)
            ->setBodyText($mail->bodyText)
            ->setBodyHtml(BodyHtml::keepable($mail->bodyHtml))
            ->setReplyText($mail->replyText ?? $mail->bodyText)
            ->setReceivedAt($mail->receivedAt);
    }

    /**
     * A message we keep, as the email a mail client answers when it sends
     * a reply.
     */
    public function toInboundMail(): InboundMail
    {
        return new InboundMail(
            externalId: $this->getExternalId()->toNullableString(),
            messageId: $this->getInternetMessageId()->toNullableString(),
            inReplyTo: $this->getInReplyTo()->toNullableString(),
            references: Types::stringList($this->getReferenceIds()->toArray()),
            fromAddress: $this->getFromAddress()->toNullableString(),
            fromName: $this->getFromName()->toNullableString(),
            to: Types::stringList($this->getToRecipients()->toArray()),
            subject: $this->getSubject()->toNullableString(),
            bodyText: $this->getBodyText()->toNullableString(),
            receivedAt: $this->getReceivedAt()->toNullableCarbon(),
        );
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

    public function getBodyHtml(): BodyHtml
    {
        return $this->bodyHtml;
    }

    public function setBodyHtml(mixed $bodyHtml): self
    {
        $this->bodyHtml = new BodyHtml($bodyHtml instanceof AbstractValue ? $bodyHtml->toValue() : $bodyHtml);

        return $this;
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
}
