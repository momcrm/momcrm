<?php

declare(strict_types=1);

namespace App\PaymentAllocation;

use App\AuditLog\AuditObserver;
use App\AuditLog\Contracts\Auditable;
use App\Invoice\InvoiceEloquentModel;
use App\Payment\PaymentEloquentModel;
use App\PaymentAllocation\Factories\PaymentAllocationFactory;
use App\PaymentAllocation\Properties\AmountInCents;
use App\PaymentAllocation\Properties\Id;
use App\PaymentAllocation\Properties\InvoiceId;
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

/**
 * Part of a payment applied to one invoice.
 *
 * @property int $amount_in_cents
 * @property CarbonImmutable|null $created_at
 * @property string $id
 * @property string $invoice_id
 * @property string $payment_id
 * @property string $team_id
 * @property CarbonImmutable|null $updated_at
 */
#[ObservedBy(AuditObserver::class)]
#[ScopedBy(CurrentTeamScope::class)]
final class PaymentAllocationEloquentModel extends Model implements Auditable
{
    /** @use HasFactory<PaymentAllocationFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    public function auditTeamId(): string
    {
        return $this->team_id;
    }

    public function getTable(): string
    {
        return Table::PaymentAllocations->value;
    }

    public function getKeyName(): string
    {
        return Id::getDatabaseTableColumnName();
    }

    /**
     * @return BelongsTo<InvoiceEloquentModel, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(
            related: InvoiceEloquentModel::class,
            foreignKey: InvoiceId::getDatabaseTableColumnName(),
            relation: PaymentAllocationRelationship::Invoice->value,
        );
    }

    /**
     * @return BelongsTo<PaymentEloquentModel, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(
            related: PaymentEloquentModel::class,
            foreignKey: PaymentId::getDatabaseTableColumnName(),
            relation: PaymentAllocationRelationship::Payment->value,
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            AmountInCents::getDatabaseTableColumnName() => 'integer',
        ];
    }

    protected static function newFactory(): PaymentAllocationFactory
    {
        return PaymentAllocationFactory::new();
    }
}
