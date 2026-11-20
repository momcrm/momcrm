<?php

declare(strict_types=1);

namespace App\Rate;

use App\AuditLog\AuditObserver;
use App\AuditLog\Contracts\Auditable;
use App\Rate\Factories\RateFactory;
use App\Rate\Properties\Id;
use App\Rate\Properties\Prices;
use App\Shared\Table;
use App\Team\Scopes\CurrentTeamScope;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property CarbonImmutable|null $archived_at
 * @property int $billing_period_in_months
 * @property CarbonImmutable|null $created_at
 * @property string|null $description
 * @property string $id
 * @property string $name
 * @property string|null $parent_id
 * @property list<array{currency: string, price_in_cents: int, cost_in_cents: ?int}>|null $prices
 * @property bool $prorates_first_month
 * @property string $team_id
 * @property CarbonImmutable|null $updated_at
 */
#[ObservedBy(AuditObserver::class)]
#[ScopedBy(CurrentTeamScope::class)]
final class RateEloquentModel extends Model implements Auditable
{
    /** @use HasFactory<RateFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    public function auditTeamId(): string
    {
        return $this->team_id;
    }

    public function getTable(): string
    {
        return Table::Rates->value;
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
            Prices::getDatabaseTableColumnName() => 'array',
        ];
    }

    protected static function newFactory(): RateFactory
    {
        return RateFactory::new();
    }
}
