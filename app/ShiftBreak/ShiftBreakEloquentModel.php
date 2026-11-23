<?php

declare(strict_types=1);

namespace App\ShiftBreak;

use App\AuditLog\AuditObserver;
use App\AuditLog\Contracts\Auditable;
use App\Shared\Table;
use App\ShiftBreak\Factories\ShiftBreakFactory;
use App\ShiftBreak\Properties\EndedAt;
use App\ShiftBreak\Properties\Id;
use App\ShiftBreak\Properties\StartedAt;
use App\Team\Scopes\CurrentTeamScope;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $ended_at
 * @property string $id
 * @property int|null $is_open
 * @property string $shift_id
 * @property CarbonImmutable $started_at
 * @property string $team_id
 * @property CarbonImmutable|null $updated_at
 */
#[ObservedBy(AuditObserver::class)]
#[ScopedBy(CurrentTeamScope::class)]
final class ShiftBreakEloquentModel extends Model implements Auditable
{
    /** @use HasFactory<ShiftBreakFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    public function auditTeamId(): string
    {
        return $this->team_id;
    }

    public function getTable(): string
    {
        return Table::ShiftBreaks->value;
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
            StartedAt::getDatabaseTableColumnName() => 'immutable_datetime',
            EndedAt::getDatabaseTableColumnName() => 'immutable_datetime',
        ];
    }

    protected static function newFactory(): ShiftBreakFactory
    {
        return ShiftBreakFactory::new();
    }
}
