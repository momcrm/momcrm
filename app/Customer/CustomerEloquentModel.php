<?php

declare(strict_types=1);

namespace App\Customer;

use App\AuditLog\AuditObserver;
use App\AuditLog\Contracts\Auditable;
use App\Contact\ContactEloquentModel;
use App\Contact\Properties\CustomerId;
use App\Customer\Factories\CustomerFactory;
use App\Customer\Properties\Id;
use App\Customer\Properties\IsSample;
use App\Customer\Properties\Number;
use App\Customer\Properties\TeamId;
use App\CustomField\Properties\CustomFieldValues;
use App\Service\Properties\CustomerId as ServiceCustomerId;
use App\Service\ServiceEloquentModel;
use App\Shared\Table;
use App\Team\Scopes\CurrentTeamScope;
use App\Team\TeamEloquentModel;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string|null $abn
 * @property string $billing_type
 * @property CarbonImmutable|null $created_at
 * @property string $currency
 * @property string|null $email
 * @property string $id
 * @property bool $is_sample
 * @property string $name
 * @property string|null $notes
 * @property int $number
 * @property string|null $phone
 * @property string|null $prefix
 * @property string $status
 * @property string $team_id
 * @property string|null $type_id
 * @property CarbonImmutable|null $updated_at
 * @property string|null $website
 */
#[ObservedBy(AuditObserver::class)]
#[ScopedBy(CurrentTeamScope::class)]
final class CustomerEloquentModel extends Model implements Auditable
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    public function auditTeamId(): string
    {
        return $this->team_id;
    }

    public function getTable(): string
    {
        return Table::Customers->value;
    }

    public function getKeyName(): string
    {
        return Id::getDatabaseTableColumnName();
    }

    /**
     * @return BelongsTo<TeamEloquentModel, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(
            related: TeamEloquentModel::class,
            foreignKey: TeamId::getDatabaseTableColumnName(),
            relation: CustomerRelationship::Team->value,
        );
    }

    /**
     * @return HasMany<ContactEloquentModel, $this>
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(
            related: ContactEloquentModel::class,
            foreignKey: CustomerId::getDatabaseTableColumnName(),
        );
    }

    /**
     * @return HasMany<ServiceEloquentModel, $this>
     */
    public function services(): HasMany
    {
        return $this->hasMany(
            related: ServiceEloquentModel::class,
            foreignKey: ServiceCustomerId::getDatabaseTableColumnName(),
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            CustomFieldValues::getDatabaseTableColumnName() => 'array',
            Number::getDatabaseTableColumnName() => 'integer',
            IsSample::getDatabaseTableColumnName() => 'boolean',
        ];
    }

    protected static function newFactory(): CustomerFactory
    {
        return CustomerFactory::new();
    }
}
