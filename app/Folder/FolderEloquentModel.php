<?php

declare(strict_types=1);

namespace App\Folder;

use App\AuditLog\AuditObserver;
use App\AuditLog\Contracts\Auditable;
use App\Customer\CustomerEloquentModel;
use App\Folder\Factories\FolderFactory;
use App\Folder\Properties\CustomerId;
use App\Folder\Properties\Id;
use App\Folder\Properties\ParentId;
use App\Shared\Table;
use App\Team\Scopes\CurrentTeamScope;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property CarbonImmutable|null $created_at
 * @property string $customer_id
 * @property string $id
 * @property string $name
 * @property string|null $parent_id
 * @property string $team_id
 * @property CarbonImmutable|null $updated_at
 */
#[ObservedBy(AuditObserver::class)]
#[ScopedBy(CurrentTeamScope::class)]
final class FolderEloquentModel extends Model implements Auditable
{
    /** @use HasFactory<FolderFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    public function auditTeamId(): string
    {
        return $this->team_id;
    }

    public function getTable(): string
    {
        return Table::Folders->value;
    }

    public function getKeyName(): string
    {
        return Id::getDatabaseTableColumnName();
    }

    /**
     * @return BelongsTo<CustomerEloquentModel, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(
            related: CustomerEloquentModel::class,
            foreignKey: CustomerId::getDatabaseTableColumnName(),
            relation: FolderRelationship::Customer->value,
        );
    }

    /**
     * @return BelongsTo<FolderEloquentModel, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(
            related: self::class,
            foreignKey: ParentId::getDatabaseTableColumnName(),
            relation: FolderRelationship::Parent->value,
        );
    }

    protected static function newFactory(): FolderFactory
    {
        return FolderFactory::new();
    }
}
