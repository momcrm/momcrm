<?php

declare(strict_types=1);

namespace App\Shift;

use App\AuditLog\AuditObserver;
use App\AuditLog\Contracts\Auditable;
use App\Shared\Table;
use App\Shift\Factories\ShiftFactory;
use App\Shift\Properties\ClockedInAt;
use App\Shift\Properties\ClockedOutAt;
use App\Shift\Properties\Id;
use App\Shift\Properties\UserId;
use App\ShiftBreak\Properties\ShiftId;
use App\ShiftBreak\ShiftBreakEloquentModel;
use App\Team\Scopes\CurrentTeamScope;
use App\User\UserEloquentModel;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property CarbonImmutable $clocked_in_at
 * @property CarbonImmutable|null $clocked_out_at
 * @property string|null $clock_in_ip
 * @property CarbonImmutable|null $created_at
 * @property string $id
 * @property int|null $is_open
 * @property string|null $note
 * @property string $team_id
 * @property CarbonImmutable|null $updated_at
 * @property string $user_id
 * @property string $work_date
 */
#[ObservedBy(AuditObserver::class)]
#[ScopedBy(CurrentTeamScope::class)]
final class ShiftEloquentModel extends Model implements Auditable
{
    /** @use HasFactory<ShiftFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    public function auditTeamId(): string
    {
        return $this->team_id;
    }

    public function getTable(): string
    {
        return Table::Shifts->value;
    }

    public function getKeyName(): string
    {
        return Id::getDatabaseTableColumnName();
    }

    /**
     * @return HasMany<ShiftBreakEloquentModel, $this>
     */
    public function breaks(): HasMany
    {
        return $this->hasMany(ShiftBreakEloquentModel::class, ShiftId::getDatabaseTableColumnName());
    }

    /**
     * @return BelongsTo<UserEloquentModel, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(
            related: UserEloquentModel::class,
            foreignKey: UserId::getDatabaseTableColumnName(),
            relation: ShiftRelationship::User->value,
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            ClockedInAt::getDatabaseTableColumnName() => 'immutable_datetime',
            ClockedOutAt::getDatabaseTableColumnName() => 'immutable_datetime',
        ];
    }

    protected static function newFactory(): ShiftFactory
    {
        return ShiftFactory::new();
    }
}
