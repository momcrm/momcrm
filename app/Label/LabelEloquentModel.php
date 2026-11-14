<?php

declare(strict_types=1);

namespace App\Label;

use App\AuditLog\AuditObserver;
use App\AuditLog\Contracts\Auditable;
use App\Label\Factories\LabelFactory;
use App\Label\Properties\Id;
use App\Shared\Table;
use App\Team\Scopes\CurrentTeamScope;
use App\Ticket\TicketEloquentModel;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property string $color
 * @property CarbonImmutable|null $created_at
 * @property string $id
 * @property string $name
 * @property string $team_id
 * @property CarbonImmutable|null $updated_at
 */
#[ObservedBy(AuditObserver::class)]
#[ScopedBy(CurrentTeamScope::class)]
final class LabelEloquentModel extends Model implements Auditable
{
    /** @use HasFactory<LabelFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    public function auditTeamId(): string
    {
        return $this->team_id;
    }

    public function getTable(): string
    {
        return Table::Labels->value;
    }

    public function getKeyName(): string
    {
        return Id::getDatabaseTableColumnName();
    }

    /**
     * @return BelongsToMany<TicketEloquentModel, $this>
     */
    public function tickets(): BelongsToMany
    {
        return $this->belongsToMany(
            related: TicketEloquentModel::class,
            table: Table::LabelTicket->value,
            foreignPivotKey: 'label_id',
            relatedPivotKey: 'ticket_id',
            relation: LabelRelationship::Tickets->value,
        );
    }

    protected static function newFactory(): LabelFactory
    {
        return LabelFactory::new();
    }
}
