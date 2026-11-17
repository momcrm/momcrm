<?php

declare(strict_types=1);

namespace App\Contract;

use App\AuditLog\AuditObserver;
use App\AuditLog\Contracts\Auditable;
use App\Contract\Factories\ContractFactory;
use App\Contract\Properties\CustomerId;
use App\Contract\Properties\Id;
use App\Customer\CustomerEloquentModel;
use App\CustomField\Properties\CustomFieldValues;
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
 * @property string|null $ends_on
 * @property string $id
 * @property string|null $notes
 * @property string|null $purchase_order
 * @property string|null $signed_on
 * @property string|null $starts_on
 * @property string $status
 * @property string $team_id
 * @property string $title
 * @property string $type
 * @property CarbonImmutable|null $updated_at
 */
#[ObservedBy(AuditObserver::class)]
#[ScopedBy(CurrentTeamScope::class)]
final class ContractEloquentModel extends Model implements Auditable
{
    /** @use HasFactory<ContractFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    public function auditTeamId(): string
    {
        return $this->team_id;
    }

    public function getTable(): string
    {
        return Table::Contracts->value;
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
            relation: ContractRelationship::Customer->value,
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

    protected static function newFactory(): ContractFactory
    {
        return ContractFactory::new();
    }
}
