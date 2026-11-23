<?php

declare(strict_types=1);

namespace App\Holiday;

use App\AuditLog\AuditObserver;
use App\AuditLog\Contracts\Auditable;
use App\Holiday\Factories\HolidayFactory;
use App\Holiday\Properties\Id;
use App\Shared\Table;
use App\Team\Scopes\CurrentTeamScope;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property CarbonImmutable|null $created_at
 * @property string $date
 * @property string $id
 * @property string $name
 * @property string $team_id
 * @property CarbonImmutable|null $updated_at
 */
#[ObservedBy(AuditObserver::class)]
#[ScopedBy(CurrentTeamScope::class)]
final class HolidayEloquentModel extends Model implements Auditable
{
    /** @use HasFactory<HolidayFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    public function auditTeamId(): string
    {
        return $this->team_id;
    }

    public function getTable(): string
    {
        return Table::Holidays->value;
    }

    public function getKeyName(): string
    {
        return Id::getDatabaseTableColumnName();
    }

    protected static function newFactory(): HolidayFactory
    {
        return HolidayFactory::new();
    }
}
