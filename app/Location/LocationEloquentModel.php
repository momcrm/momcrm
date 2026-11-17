<?php

declare(strict_types=1);

namespace App\Location;

use App\AuditLog\AuditObserver;
use App\AuditLog\Contracts\Auditable;
use App\Customer\CustomerEloquentModel;
use App\CustomField\Properties\CustomFieldValues;
use App\Location\Factories\LocationFactory;
use App\Location\Properties\CustomerId;
use App\Location\Properties\Id;
use App\Location\Properties\IsPrimary;
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
 * @property string|null $country
 * @property CarbonImmutable|null $created_at
 * @property string $customer_id
 * @property string $id
 * @property bool $is_primary
 * @property string $name
 * @property string|null $postcode
 * @property string|null $state
 * @property string|null $street_one
 * @property string|null $street_two
 * @property string|null $suburb
 * @property string $team_id
 * @property CarbonImmutable|null $updated_at
 */
#[ObservedBy(AuditObserver::class)]
#[ScopedBy(CurrentTeamScope::class)]
final class LocationEloquentModel extends Model implements Auditable
{
    /** @use HasFactory<LocationFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    public function auditTeamId(): string
    {
        return $this->team_id;
    }

    public function getTable(): string
    {
        return Table::Locations->value;
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
            relation: LocationRelationship::Customer->value,
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            CustomFieldValues::getDatabaseTableColumnName() => 'array',
            IsPrimary::getDatabaseTableColumnName() => 'boolean',
        ];
    }

    protected static function newFactory(): LocationFactory
    {
        return LocationFactory::new();
    }
}
