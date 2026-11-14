<?php

declare(strict_types=1);

namespace App\Ticket;

use App\AuditLog\AuditObserver;
use App\AuditLog\Contracts\Auditable;
use App\Contact\ContactEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Shared\Table;
use App\Team\Scopes\CurrentTeamScope;
use App\Ticket\Factories\TicketFactory;
use App\Ticket\Properties\AssigneeId;
use App\Ticket\Properties\ClosedAt;
use App\Ticket\Properties\ContactId;
use App\Ticket\Properties\CustomerId;
use App\Ticket\Properties\Id;
use App\Ticket\Properties\Number;
use App\User\UserEloquentModel;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string|null $assignee_id
 * @property CarbonImmutable|null $closed_at
 * @property string|null $contact_id
 * @property CarbonImmutable|null $created_at
 * @property string $customer_id
 * @property string|null $description
 * @property string $id
 * @property string|null $location_id
 * @property int $number
 * @property string $priority
 * @property string $status
 * @property string $team_id
 * @property string $title
 * @property CarbonImmutable|null $updated_at
 */
#[ObservedBy(AuditObserver::class)]
#[ScopedBy(CurrentTeamScope::class)]
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
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            Number::getDatabaseTableColumnName() => 'integer',
            ClosedAt::getDatabaseTableColumnName() => 'immutable_datetime',
        ];
    }

    protected static function newFactory(): TicketFactory
    {
        return TicketFactory::new();
    }
}
