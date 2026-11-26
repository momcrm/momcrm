<?php

declare(strict_types=1);

namespace App\MailboxMessage;

use App\MailboxMessage\Factories\MailboxMessageFactory;
use App\MailboxMessage\Properties\Id;
use App\MailboxMessage\Properties\ReceivedAt;
use App\MailboxMessage\Properties\ReferenceIds;
use App\MailboxMessage\Properties\ToRecipients;
use App\Shared\Table;
use App\Team\Scopes\CurrentTeamScope;
use App\Ticket\Contracts\ChangesTicket;
use App\Ticket\TicketChangeObserver;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Not audited: these rows are a record of mail, written by the mailbox sync
 * and never edited by people.
 *
 * @property string|null $body_html
 * @property string|null $body_text
 * @property string|null $comment_id
 * @property CarbonImmutable|null $created_at
 * @property string $direction
 * @property string|null $error
 * @property string|null $from_address
 * @property string|null $from_name
 * @property string $id
 * @property string|null $in_reply_to
 * @property string $internet_message_id
 * @property string|null $mailbox_id
 * @property string|null $external_id
 * @property CarbonImmutable|null $received_at
 * @property array<int, string>|null $reference_ids
 * @property string $status
 * @property string|null $subject
 * @property string $team_id
 * @property string|null $ticket_id
 * @property array<int, string>|null $to_recipients
 * @property CarbonImmutable|null $updated_at
 */
#[ObservedBy(TicketChangeObserver::class)]
#[ScopedBy(CurrentTeamScope::class)]
final class MailboxMessageEloquentModel extends Model implements ChangesTicket
{
    /** @use HasFactory<MailboxMessageFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    public function changedTicketTeamId(): string
    {
        return $this->team_id;
    }

    /**
     * An email on a ticket, such as a reply whose delivery the ticket shows.
     *
     * @return list<string>
     */
    public function changedTicketIds(): array
    {
        return $this->ticket_id === null ? [] : [$this->ticket_id];
    }

    public function getTable(): string
    {
        return Table::MailboxMessages->value;
    }

    public function getKeyName(): string
    {
        return Id::getDatabaseTableColumnName();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            ReceivedAt::getDatabaseTableColumnName() => 'immutable_datetime',
            ReferenceIds::getDatabaseTableColumnName() => 'array',
            ToRecipients::getDatabaseTableColumnName() => 'array',
        ];
    }

    protected static function newFactory(): MailboxMessageFactory
    {
        return MailboxMessageFactory::new();
    }
}
