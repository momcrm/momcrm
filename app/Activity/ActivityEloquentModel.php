<?php

declare(strict_types=1);

namespace App\Activity;

use App\Activity\Properties\CreatedAt;
use App\Activity\Properties\Details;
use App\Activity\Properties\Id;
use App\Activity\Properties\UserId;
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
final class ActivityEloquentModel extends Model
{
    use HasUuids;
    use MassPrunable;

    public const UPDATED_AT = null;

    protected $guarded = [];

    public function getTable(): string
    {
        return Table::Activities->value;
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
            relation: ActivityRelationship::Actor->value,
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
            now()->subDays((int) config('audit.activity_retention_days')),
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            Details::getDatabaseTableColumnName() => 'array',
        ];
    }
}
