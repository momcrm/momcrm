<?php

declare(strict_types=1);

namespace App\Charge;

use App\AuditLog\AuditObserver;
use App\AuditLog\Contracts\Auditable;
use App\Charge\Factories\ChargeFactory;
use App\Charge\Properties\ChargedOn;
use App\Charge\Properties\Id;
use App\Charge\Properties\Quantity;
use App\Charge\Properties\UnitPriceInCents;
use App\Shared\Table;
use App\Team\Scopes\CurrentTeamScope;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property CarbonImmutable $charged_on
 * @property CarbonImmutable|null $created_at
 * @property string $currency
 * @property string $customer_id
 * @property string $description
 * @property string $id
 * @property string|null $product_id
 * @property int $quantity
 * @property string $team_id
 * @property int $unit_price_in_cents
 * @property CarbonImmutable|null $updated_at
 */
#[ObservedBy(AuditObserver::class)]
#[ScopedBy(CurrentTeamScope::class)]
final class ChargeEloquentModel extends Model implements Auditable
{
    /** @use HasFactory<ChargeFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    public function auditTeamId(): string
    {
        return $this->team_id;
    }

    public function getTable(): string
    {
        return Table::Charges->value;
    }

    public function getKeyName(): string
    {
        return Id::getDatabaseTableColumnName();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            ChargedOn::getDatabaseTableColumnName() => 'immutable_date',
            Quantity::getDatabaseTableColumnName() => 'integer',
            UnitPriceInCents::getDatabaseTableColumnName() => 'integer',
        ];
    }

    protected static function newFactory(): ChargeFactory
    {
        return ChargeFactory::new();
    }
}
