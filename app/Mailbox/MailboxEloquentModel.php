<?php

declare(strict_types=1);

namespace App\Mailbox;

use App\AuditLog\AuditObserver;
use App\AuditLog\Contracts\Auditable;
use App\Mailbox\Factories\MailboxFactory;
use App\Mailbox\Properties\AutoCreateTickets;
use App\Mailbox\Properties\Id;
use App\Mailbox\Properties\IsShared;
use App\Mailbox\Properties\LastSyncedAt;
use App\Mailbox\Properties\SyncedThrough;
use App\Shared\Table;
use App\Team\Scopes\CurrentTeamScope;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $address
 * @property bool $auto_create_tickets
 * @property string $connector_id
 * @property CarbonImmutable|null $created_at
 * @property string|null $display_name
 * @property string|null $group_id
 * @property string $id
 * @property bool $is_shared
 * @property string|null $last_error
 * @property CarbonImmutable|null $last_synced_at
 * @property CarbonImmutable|null $synced_through
 * @property string $team_id
 * @property CarbonImmutable|null $updated_at
 */
#[ObservedBy(AuditObserver::class)]
#[ScopedBy(CurrentTeamScope::class)]
final class MailboxEloquentModel extends Model implements Auditable
{
    /** @use HasFactory<MailboxFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    public function auditTeamId(): string
    {
        return $this->team_id;
    }

    public function getTable(): string
    {
        return Table::Mailboxes->value;
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
            AutoCreateTickets::getDatabaseTableColumnName() => 'boolean',
            IsShared::getDatabaseTableColumnName() => 'boolean',
            LastSyncedAt::getDatabaseTableColumnName() => 'immutable_datetime',
            SyncedThrough::getDatabaseTableColumnName() => 'immutable_datetime',
        ];
    }

    protected static function newFactory(): MailboxFactory
    {
        return MailboxFactory::new();
    }
}
