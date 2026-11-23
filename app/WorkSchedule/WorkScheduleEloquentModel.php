<?php

declare(strict_types=1);

namespace App\WorkSchedule;

use App\AuditLog\AuditObserver;
use App\AuditLog\Contracts\Auditable;
use App\Shared\Table;
use App\Team\Scopes\CurrentTeamScope;
use App\WorkSchedule\Factories\WorkScheduleFactory;
use App\WorkSchedule\Properties\Days;
use App\WorkSchedule\Properties\Id;
use App\WorkSchedule\Properties\IsDefault;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property CarbonImmutable|null $created_at
 * @property array<string, array{starts_at: string, ends_at: string, break_minutes: int}|null> $days
 * @property int $grace_minutes
 * @property string $id
 * @property bool $is_default
 * @property string $name
 * @property string $team_id
 * @property CarbonImmutable|null $updated_at
 */
#[ObservedBy(AuditObserver::class)]
#[ScopedBy(CurrentTeamScope::class)]
final class WorkScheduleEloquentModel extends Model implements Auditable
{
    /** @use HasFactory<WorkScheduleFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    public function auditTeamId(): string
    {
        return $this->team_id;
    }

    public function getTable(): string
    {
        return Table::WorkSchedules->value;
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
            Days::getDatabaseTableColumnName() => 'array',
            IsDefault::getDatabaseTableColumnName() => 'boolean',
        ];
    }

    protected static function newFactory(): WorkScheduleFactory
    {
        return WorkScheduleFactory::new();
    }
}
