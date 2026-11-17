<?php

declare(strict_types=1);

namespace App\Asset;

use App\Asset\Factories\AssetFactory;
use App\Asset\Properties\CustomerId;
use App\Asset\Properties\Id;
use App\Asset\Properties\LocationId;
use App\AuditLog\AuditObserver;
use App\AuditLog\Contracts\Auditable;
use App\Choice\HasLabels;
use App\Choice\LabelTargetEnum;
use App\Customer\CustomerEloquentModel;
use App\CustomField\Properties\CustomFieldValues;
use App\Location\LocationEloquentModel;
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
 * @property CarbonImmutable|null $created_at
 * @property string $customer_id
 * @property string|null $description
 * @property string $id
 * @property string|null $location_id
 * @property string $name
 * @property string|null $serial_number
 * @property string $status
 * @property string $team_id
 * @property CarbonImmutable|null $updated_at
 */
#[ObservedBy(AuditObserver::class)]
#[ScopedBy(CurrentTeamScope::class)]
final class AssetEloquentModel extends Model implements Auditable
{
    /** @use HasFactory<AssetFactory> */
    use HasFactory;

    use HasLabels;
    use HasUuids;

    protected $guarded = [];

    public function auditTeamId(): string
    {
        return $this->team_id;
    }

    public function getTable(): string
    {
        return Table::Assets->value;
    }

    public function getKeyName(): string
    {
        return Id::getDatabaseTableColumnName();
    }

    public static function labelTarget(): LabelTargetEnum
    {
        return LabelTargetEnum::Asset;
    }

    /**
     * @return BelongsTo<CustomerEloquentModel, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(
            related: CustomerEloquentModel::class,
            foreignKey: CustomerId::getDatabaseTableColumnName(),
            relation: AssetRelationship::Customer->value,
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
            relation: AssetRelationship::Location->value,
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            CustomFieldValues::getDatabaseTableColumnName() => 'array',
        ];
    }

    protected static function newFactory(): AssetFactory
    {
        return AssetFactory::new();
    }
}
