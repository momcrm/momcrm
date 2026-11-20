<?php

declare(strict_types=1);

namespace App\Invoice;

use App\AuditLog\AuditObserver;
use App\AuditLog\Contracts\Auditable;
use App\Customer\CustomerEloquentModel;
use App\CustomField\Properties\CustomFieldValues;
use App\Invoice\Factories\InvoiceFactory;
use App\Invoice\Properties\CustomerId;
use App\Invoice\Properties\DueOn;
use App\Invoice\Properties\Id;
use App\Invoice\Properties\IssuedOn;
use App\Invoice\Properties\Number;
use App\Invoice\Properties\PaidAt;
use App\Invoice\Properties\SentAt;
use App\Invoice\Properties\TaxRateBasisPoints;
use App\InvoiceItem\InvoiceItemEloquentModel;
use App\InvoiceItem\Properties\InvoiceId;
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
 * @property CarbonImmutable|null $created_at
 * @property string $currency
 * @property string $customer_id
 * @property CarbonImmutable $due_on
 * @property string $id
 * @property CarbonImmutable $issued_on
 * @property string|null $notes
 * @property int $number
 * @property CarbonImmutable|null $paid_at
 * @property string|null $purchase_order
 * @property CarbonImmutable|null $sent_at
 * @property string $status
 * @property int $tax_rate_basis_points
 * @property string $team_id
 * @property CarbonImmutable|null $updated_at
 */
#[ObservedBy(AuditObserver::class)]
#[ScopedBy(CurrentTeamScope::class)]
final class InvoiceEloquentModel extends Model implements Auditable
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    public function auditTeamId(): string
    {
        return $this->team_id;
    }

    public function getTable(): string
    {
        return Table::Invoices->value;
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
            relation: InvoiceRelationship::Customer->value,
        );
    }

    /**
     * @return HasMany<InvoiceItemEloquentModel, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(
            related: InvoiceItemEloquentModel::class,
            foreignKey: InvoiceId::getDatabaseTableColumnName(),
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
            IssuedOn::getDatabaseTableColumnName() => 'immutable_date',
            DueOn::getDatabaseTableColumnName() => 'immutable_date',
            TaxRateBasisPoints::getDatabaseTableColumnName() => 'integer',
            SentAt::getDatabaseTableColumnName() => 'immutable_datetime',
            PaidAt::getDatabaseTableColumnName() => 'immutable_datetime',
        ];
    }

    protected static function newFactory(): InvoiceFactory
    {
        return InvoiceFactory::new();
    }
}
