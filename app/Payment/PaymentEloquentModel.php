<?php

declare(strict_types=1);

namespace App\Payment;

use App\AuditLog\AuditObserver;
use App\AuditLog\Contracts\Auditable;
use App\Customer\CustomerEloquentModel;
use App\Payment\Factories\PaymentFactory;
use App\Payment\Properties\AmountInCents;
use App\Payment\Properties\CustomerId;
use App\Payment\Properties\Id;
use App\Payment\Properties\Number;
use App\Payment\Properties\PaidOn;
use App\PaymentAllocation\PaymentAllocationEloquentModel;
use App\PaymentAllocation\Properties\PaymentId;
use App\Shared\Table;
use App\Team\Scopes\CurrentTeamScope;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $amount_in_cents
 * @property CarbonImmutable|null $created_at
 * @property string $currency
 * @property string $customer_id
 * @property string $id
 * @property string $method
 * @property string|null $notes
 * @property int $number
 * @property CarbonImmutable $paid_on
 * @property string|null $reference
 * @property string $team_id
 * @property CarbonImmutable|null $updated_at
 */
#[ObservedBy(AuditObserver::class)]
#[ScopedBy(CurrentTeamScope::class)]
final class PaymentEloquentModel extends Model implements Auditable
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    public function auditTeamId(): string
    {
        return $this->team_id;
    }

    public function getTable(): string
    {
        return Table::Payments->value;
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
            relation: PaymentRelationship::Customer->value,
        );
    }

    /**
     * @return HasMany<PaymentAllocationEloquentModel, $this>
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(
            related: PaymentAllocationEloquentModel::class,
            foreignKey: PaymentId::getDatabaseTableColumnName(),
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            AmountInCents::getDatabaseTableColumnName() => 'integer',
            Number::getDatabaseTableColumnName() => 'integer',
            PaidOn::getDatabaseTableColumnName() => 'immutable_date',
        ];
    }

    protected static function newFactory(): PaymentFactory
    {
        return PaymentFactory::new();
    }
}
