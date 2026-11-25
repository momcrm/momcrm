<?php

declare(strict_types=1);

namespace App\Comment;

use App\AuditLog\AuditObserver;
use App\AuditLog\Contracts\Auditable;
use App\Comment\Factories\CommentFactory;
use App\Comment\Properties\Id;
use App\Comment\Properties\Mentions;
use App\Comment\Properties\TicketId;
use App\Comment\Properties\UserId;
use App\Shared\Table;
use App\Team\Scopes\CurrentTeamScope;
use App\Ticket\Contracts\ChangesTicket;
use App\Ticket\TicketChangeObserver;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $body
 * @property string|null $contact_id
 * @property CarbonImmutable|null $created_at
 * @property string $id
 * @property array<int, string>|null $mentions
 * @property string|null $sender_email
 * @property string|null $sender_name
 * @property string $team_id
 * @property string|null $record_id
 * @property string|null $ticket_id
 * @property CarbonImmutable|null $updated_at
 * @property string|null $user_id
 * @property string $visibility
 */
#[ObservedBy([AuditObserver::class, TicketChangeObserver::class])]
#[ScopedBy(CurrentTeamScope::class)]
final class CommentEloquentModel extends Model implements Auditable, ChangesTicket
{
    /** @use HasFactory<CommentFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    public function auditTeamId(): string
    {
        return $this->team_id;
    }

    public function changedTicketTeamId(): string
    {
        return $this->team_id;
    }

    /**
     * @return list<string>
     */
    public function changedTicketIds(): array
    {
        return $this->ticket_id === null ? [] : [$this->ticket_id];
    }

    public function getTable(): string
    {
        return Table::Comments->value;
    }

    public function getKeyName(): string
    {
        return Id::getDatabaseTableColumnName();
    }

    /**
     * @return BelongsTo<UserEloquentModel, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(
            related: UserEloquentModel::class,
            foreignKey: UserId::getDatabaseTableColumnName(),
            relation: CommentRelationship::Author->value,
        );
    }

    /**
     * @return BelongsTo<TicketEloquentModel, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(
            related: TicketEloquentModel::class,
            foreignKey: TicketId::getDatabaseTableColumnName(),
            relation: CommentRelationship::Ticket->value,
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            Mentions::getDatabaseTableColumnName() => 'array',
        ];
    }

    protected static function newFactory(): CommentFactory
    {
        return CommentFactory::new();
    }
}
