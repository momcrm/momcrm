<?php

declare(strict_types=1);

namespace App\Service;

use App\AuditLog\AuditObserver;
use App\AuditLog\Contracts\Auditable;
use App\Contact\ContactEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\CustomField\Properties\CustomFieldValues;
use App\Location\LocationEloquentModel;
use App\Rate\RateEloquentModel;
use App\Service\Factories\ServiceFactory;
use App\Service\Properties\ArchivedAt;
use App\Service\Properties\AutoRenew;
use App\Service\Properties\BillingPeriodInMonths;
use App\Service\Properties\BillingStartsOn;
use App\Service\Properties\ContactId;
use App\Service\Properties\CustomerId;
use App\Service\Properties\EndsOn;
use App\Service\Properties\Id;
use App\Service\Properties\LocationId;
use App\Service\Properties\Quantity;
use App\Service\Properties\RateId;
use App\Shared\Table;
use App\Team\Scopes\CurrentTeamScope;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property CarbonImmutable|null $archived_at
 * @property bool $auto_renew
 * @property int|null $billing_period_in_months
 * @property CarbonImmutable|null $billing_starts_on
 * @property string|null $contact_id
 * @property CarbonImmutable|null $created_at
 * @property string $customer_id
 * @property CarbonImmutable|null $ends_on
 * @property string $id
 * @property string|null $location_id
 * @property string $name
 * @property string|null $note
 * @property int $quantity
 * @property string|null $rate_id
 * @property string|null $short_name
 * @property string $status
 * @property string $team_id
 * @property CarbonImmutable|null $updated_at
 */
#[ObservedBy(AuditObserver::class)]
#[ScopedBy(CurrentTeamScope::class)]
final class ServiceEloquentModel extends Model implements Auditable
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    public function auditTeamId(): string
    {
        return $this->team_id;
    }

    public function getTable(): string
    {
        return Table::Services->value;
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
            relation: ServiceRelationship::Customer->value,
        );
    }

    /**
     * @return BelongsTo<LocationEloquentModel, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(
            related: LocationEloquentModel::class,
            foreignKey: LocationId::getDatabaseTableColumnName(),
            relation: ServiceRelationship::Location->value,
        );
    }

    /**
     * @return BelongsTo<RateEloquentModel, $this>
     */
    public function rate(): BelongsTo
    {
        return $this->belongsTo(
            related: RateEloquentModel::class,
            foreignKey: RateId::getDatabaseTableColumnName(),
            relation: ServiceRelationship::Rate->value,
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
            relation: ServiceRelationship::Contact->value,
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            CustomFieldValues::getDatabaseTableColumnName() => 'array',
            Quantity::getDatabaseTableColumnName() => 'integer',
            BillingPeriodInMonths::getDatabaseTableColumnName() => 'integer',
            BillingStartsOn::getDatabaseTableColumnName() => 'immutable_date',
            EndsOn::getDatabaseTableColumnName() => 'immutable_date',
            AutoRenew::getDatabaseTableColumnName() => 'boolean',
            ArchivedAt::getDatabaseTableColumnName() => 'immutable_datetime',
        ];
    }

    protected static function newFactory(): ServiceFactory
    {
        return ServiceFactory::new();
    }
}
