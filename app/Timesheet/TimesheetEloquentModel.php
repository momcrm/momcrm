<?php

declare(strict_types=1);

namespace App\Timesheet;

use App\AuditLog\AuditObserver;
use App\AuditLog\Contracts\Auditable;
use App\Shared\Table;
use App\Team\Scopes\CurrentTeamScope;
use App\Timesheet\Factories\TimesheetFactory;
use App\Timesheet\Properties\Id;
use App\Timesheet\Properties\ReviewedAt;
use App\Timesheet\Properties\SubmittedAt;
use App\Timesheet\Properties\Totals;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property CarbonImmutable|null $created_at
 * @property string $ends_on
 * @property string $id
 * @property string|null $review_note
 * @property CarbonImmutable|null $reviewed_at
 * @property string|null $reviewed_by
 * @property string $starts_on
 * @property string $status
 * @property CarbonImmutable|null $submitted_at
 * @property string $team_id
 * @property array<string, mixed>|null $totals
 * @property CarbonImmutable|null $updated_at
 * @property string $user_id
 */
#[ObservedBy(AuditObserver::class)]
#[ScopedBy(CurrentTeamScope::class)]
final class TimesheetEloquentModel extends Model implements Auditable
{
    /** @use HasFactory<TimesheetFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    public function auditTeamId(): string
    {
        return $this->team_id;
    }

    public function getTable(): string
    {
        return Table::Timesheets->value;
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
            SubmittedAt::getDatabaseTableColumnName() => 'immutable_datetime',
            ReviewedAt::getDatabaseTableColumnName() => 'immutable_datetime',
            Totals::getDatabaseTableColumnName() => 'array',
        ];
    }

    protected static function newFactory(): TimesheetFactory
    {
        return TimesheetFactory::new();
    }
}
