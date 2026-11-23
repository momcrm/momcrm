<?php

declare(strict_types=1);

namespace App\LeaveRequest;

use App\AuditLog\AuditObserver;
use App\AuditLog\Contracts\Auditable;
use App\LeaveRequest\Factories\LeaveRequestFactory;
use App\LeaveRequest\Properties\Id;
use App\LeaveRequest\Properties\ReviewedAt;
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
 * @property string $ends_on
 * @property string $id
 * @property string $leave_type_id
 * @property string $part
 * @property string|null $reason
 * @property string|null $review_note
 * @property CarbonImmutable|null $reviewed_at
 * @property string|null $reviewed_by
 * @property string $starts_on
 * @property string $status
 * @property string $team_id
 * @property CarbonImmutable|null $updated_at
 * @property string $user_id
 */
#[ObservedBy(AuditObserver::class)]
#[ScopedBy(CurrentTeamScope::class)]
final class LeaveRequestEloquentModel extends Model implements Auditable
{
    /** @use HasFactory<LeaveRequestFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    public function auditTeamId(): string
    {
        return $this->team_id;
    }

    public function getTable(): string
    {
        return Table::LeaveRequests->value;
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
        return [ReviewedAt::getDatabaseTableColumnName() => 'immutable_datetime'];
    }

    protected static function newFactory(): LeaveRequestFactory
    {
        return LeaveRequestFactory::new();
    }
}
