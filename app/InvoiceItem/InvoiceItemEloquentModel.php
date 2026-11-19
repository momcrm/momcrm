<?php

declare(strict_types=1);

namespace App\InvoiceItem;

use App\AuditLog\AuditObserver;
use App\AuditLog\Contracts\Auditable;
use App\Invoice\InvoiceEloquentModel;
use App\InvoiceItem\Factories\InvoiceItemFactory;
use App\InvoiceItem\Properties\Id;
use App\InvoiceItem\Properties\InvoiceId;
use App\InvoiceItem\Properties\Position;
use App\InvoiceItem\Properties\Quantity;
use App\InvoiceItem\Properties\RentalEndsOn;
use App\InvoiceItem\Properties\RentalStartsOn;
use App\InvoiceItem\Properties\UnitPriceInCents;
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
 * @property string $description
 * @property string $id
 * @property string $invoice_id
 * @property int $position
 * @property int $quantity
 * @property CarbonImmutable|null $rental_ends_on
 * @property CarbonImmutable|null $rental_starts_on
 * @property string|null $service_id
 * @property string $team_id
 * @property int $unit_price_in_cents
 * @property CarbonImmutable|null $updated_at
 */
#[ObservedBy(AuditObserver::class)]
#[ScopedBy(CurrentTeamScope::class)]
final class InvoiceItemEloquentModel extends Model implements Auditable
{
    /** @use HasFactory<InvoiceItemFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    public function auditTeamId(): string
    {
        return $this->team_id;
    }

    public function getTable(): string
    {
        return Table::InvoiceItems->value;
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
            relation: InvoiceItemRelationship::Invoice->value,
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            Position::getDatabaseTableColumnName() => 'integer',
            Quantity::getDatabaseTableColumnName() => 'integer',
            UnitPriceInCents::getDatabaseTableColumnName() => 'integer',
            RentalStartsOn::getDatabaseTableColumnName() => 'immutable_date',
            RentalEndsOn::getDatabaseTableColumnName() => 'immutable_date',
        ];
    }

    protected static function newFactory(): InvoiceItemFactory
    {
        return InvoiceItemFactory::new();
    }
}
