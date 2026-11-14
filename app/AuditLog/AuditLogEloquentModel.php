<?php

declare(strict_types=1);

namespace App\AuditLog;

use App\AuditLog\Properties\CreatedAt;
use App\AuditLog\Properties\Id;
use App\AuditLog\Properties\NewValues;
use App\AuditLog\Properties\OldValues;
use App\AuditLog\Properties\UserId;
use App\Shared\Table;
use App\User\UserEloquentModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only: rows are never updated, and are pruned after the retention
 * period configured in config/audit.php.
 */
final class AuditLogEloquentModel extends Model
{
    use HasUuids;
    use MassPrunable;

    public const UPDATED_AT = null;

    protected $guarded = [];

    public function getTable(): string
    {
        return Table::AuditLogs->value;
    }

    public function getKeyName(): string
    {
        return Id::getDatabaseTableColumnName();
    }

    /**
     * The user who performed the action, if any.
     *
     * @return BelongsTo<UserEloquentModel, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(
            related: UserEloquentModel::class,
            foreignKey: UserId::getDatabaseTableColumnName(),
            relation: AuditLogRelationship::Actor->value,
        );
    }

    /**
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        return self::query()->where(
            CreatedAt::getDatabaseTableColumnName(),
            '<=',
            now()->subDays((int) config('audit.audit_log_retention_days')),
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            OldValues::getDatabaseTableColumnName() => 'array',
            NewValues::getDatabaseTableColumnName() => 'array',
        ];
    }
}
