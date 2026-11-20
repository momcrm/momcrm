<?php

declare(strict_types=1);

namespace App\BankRule;

use App\AuditLog\AuditObserver;
use App\AuditLog\Contracts\Auditable;
use App\BankRule\Factories\BankRuleFactory;
use App\BankRule\Properties\CustomerId;
use App\BankRule\Properties\Id;
use App\Customer\CustomerEloquentModel;
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
 * @property string $contains
 * @property CarbonImmutable|null $created_at
 * @property string|null $customer_id
 * @property string $id
 * @property string $kind
 * @property string $team_id
 * @property CarbonImmutable|null $updated_at
 */
#[ObservedBy(AuditObserver::class)]
#[ScopedBy(CurrentTeamScope::class)]
final class BankRuleEloquentModel extends Model implements Auditable
{
    /** @use HasFactory<BankRuleFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    public function auditTeamId(): string
    {
        return $this->team_id;
    }

    public function getTable(): string
    {
        return Table::BankRules->value;
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
            relation: BankRuleRelationship::Customer->value,
        );
    }

    protected static function newFactory(): BankRuleFactory
    {
        return BankRuleFactory::new();
    }
}
