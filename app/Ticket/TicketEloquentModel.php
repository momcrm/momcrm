<?php

declare(strict_types=1);

namespace App\Ticket;

use App\AuditLog\AuditObserver;
use App\AuditLog\Contracts\Auditable;
use App\Contact\ContactEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Label\LabelEloquentModel;
use App\Project\ProjectEloquentModel;
use App\Shared\Table;
use App\Team\Scopes\CurrentTeamScope;
use App\Ticket\Factories\TicketFactory;
use App\Ticket\Properties\ArchivedAt;
use App\Ticket\Properties\AssigneeId;
use App\Ticket\Properties\ClosedAt;
use App\Ticket\Properties\ContactId;
use App\Ticket\Properties\CustomerId;
use App\Ticket\Properties\EstimateMinutes;
use App\Ticket\Properties\Id;
use App\Ticket\Properties\Number;
use App\Ticket\Properties\ParentId;
use App\Ticket\Properties\Position;
use App\Ticket\Properties\ProjectId;
use App\Ticket\Scopes\VisibleTicketScope;
use App\User\UserEloquentModel;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property CarbonImmutable|null $archived_at
 * @property string|null $assignee_id
 * @property CarbonImmutable|null $closed_at
 * @property string|null $contact_id
 * @property CarbonImmutable|null $created_at
 * @property string|null $customer_id
 * @property string|null $description
 * @property string|null $due_on
 * @property int|null $estimate_minutes
 * @property string $id
 * @property string|null $location_id
 * @property int $number
 * @property string|null $parent_id
 * @property int $position
 * @property string|null $priority_id
 * @property string|null $project_id
 * @property string|null $starts_on
 * @property string|null $status_id
 * @property string $team_id
 * @property string $title
 * @property CarbonImmutable|null $updated_at
 */
#[ObservedBy(AuditObserver::class)]
#[ScopedBy([CurrentTeamScope::class, VisibleTicketScope::class])]
final class TicketEloquentModel extends Model implements Auditable
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    public function auditTeamId(): string
    {
        return $this->team_id;
    }

    public function getTable(): string
    {
        return Table::Tickets->value;
    }

    public function getKeyName(): string
    {
        return Id::getDatabaseTableColumnName();
    }

    /**
     * @return BelongsTo<CustomerEloquentModel, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(
            related: CustomerEloquentModel::class,
            foreignKey: CustomerId::getDatabaseTableColumnName(),
            relation: TicketRelationship::Customer->value,
        );
    }

    /**
     * @return BelongsTo<ContactEloquentModel, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(
            related: ContactEloquentModel::class,
            foreignKey: ContactId::getDatabaseTableColumnName(),
            relation: TicketRelationship::Contact->value,
        );
    }

    /**
     * @return BelongsTo<UserEloquentModel, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(
            related: UserEloquentModel::class,
            foreignKey: AssigneeId::getDatabaseTableColumnName(),
            relation: TicketRelationship::Assignee->value,
        );
    }

    /**
     * @return BelongsTo<ProjectEloquentModel, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(
            related: ProjectEloquentModel::class,
            foreignKey: ProjectId::getDatabaseTableColumnName(),
            relation: TicketRelationship::Project->value,
        );
    }

    /**
     * The ticket this one is a subtask of.
     *
     * @return BelongsTo<self, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(
            related: self::class,
            foreignKey: ParentId::getDatabaseTableColumnName(),
            relation: TicketRelationship::Parent->value,
        );
    }

    /**
     * @return HasMany<self, $this>
     */
    public function subtasks(): HasMany
    {
        return $this->hasMany(self::class, ParentId::getDatabaseTableColumnName());
    }

    /**
     * @return BelongsToMany<LabelEloquentModel, $this>
     */
    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(
            related: LabelEloquentModel::class,
            table: Table::LabelTicket->value,
            foreignPivotKey: 'ticket_id',
            relatedPivotKey: 'label_id',
            relation: TicketRelationship::Labels->value,
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            Number::getDatabaseTableColumnName() => 'integer',
            Position::getDatabaseTableColumnName() => 'integer',
            EstimateMinutes::getDatabaseTableColumnName() => 'integer',
            ClosedAt::getDatabaseTableColumnName() => 'immutable_datetime',
            ArchivedAt::getDatabaseTableColumnName() => 'immutable_datetime',
        ];
    }

    protected static function newFactory(): TicketFactory
    {
        return TicketFactory::new();
    }
}
