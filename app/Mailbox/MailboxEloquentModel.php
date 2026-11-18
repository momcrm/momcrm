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
 * The refresh token is encrypted with the app key and hidden, so it stays out
 * of arrays, JSON and the audit log.
 *
 * @property string $address
 * @property bool $auto_create_tickets
 * @property string|null $connected_by_id
 * @property CarbonImmutable|null $created_at
 * @property string|null $display_name
 * @property string|null $group_id
 * @property string $id
 * @property bool $is_shared
 * @property string|null $last_error
 * @property CarbonImmutable|null $last_synced_at
 * @property string $refresh_token
 * @property string $status
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

    public const string REFRESH_TOKEN = 'refresh_token';

    protected $guarded = [];

    protected $hidden = [self::REFRESH_TOKEN];

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
            self::REFRESH_TOKEN => 'encrypted',
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
