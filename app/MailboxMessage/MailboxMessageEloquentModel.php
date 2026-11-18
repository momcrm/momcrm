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
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Not audited: these rows are a record of mail, written by the mailbox sync
 * and never edited by people.
 *
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
 * @property string|null $microsoft_id
 * @property CarbonImmutable|null $received_at
 * @property array<int, string>|null $reference_ids
 * @property string $status
 * @property string|null $subject
 * @property string $team_id
 * @property string|null $ticket_id
 * @property array<int, string>|null $to_recipients
 * @property CarbonImmutable|null $updated_at
 */
#[ScopedBy(CurrentTeamScope::class)]
final class MailboxMessageEloquentModel extends Model
{
    /** @use HasFactory<MailboxMessageFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

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
