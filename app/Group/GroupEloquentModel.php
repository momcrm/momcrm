<?php

declare(strict_types=1);

namespace App\Group;

use App\AuditLog\AuditObserver;
use App\AuditLog\Contracts\Auditable;
use App\Group\Factories\GroupFactory;
use App\Group\Properties\Id;
use App\Project\ProjectEloquentModel;
use App\Project\Properties\GroupId as ProjectGroupId;
use App\Shared\Table;
use App\Team\Scopes\CurrentTeamScope;
use App\User\UserEloquentModel;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A group of people inside the team (Design, Support, ...) that projects can
 * belong to. Called "teams" in gr8r; renamed so it does not clash with the
 * workspace Team.
 *
 * @property int|null $hue
 * @property CarbonImmutable|null $created_at
 * @property string|null $description
 * @property string|null $icon
 * @property string $id
 * @property string $name
 * @property string $team_id
 * @property CarbonImmutable|null $updated_at
 */
#[ObservedBy(AuditObserver::class)]
#[ScopedBy(CurrentTeamScope::class)]
final class GroupEloquentModel extends Model implements Auditable
{
    /** @use HasFactory<GroupFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    public function auditTeamId(): string
    {
        return $this->team_id;
    }

    public function getTable(): string
    {
        return Table::Groups->value;
    }

    public function getKeyName(): string
    {
        return Id::getDatabaseTableColumnName();
    }

    /**
     * @return BelongsToMany<UserEloquentModel, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(
            related: UserEloquentModel::class,
            table: Table::GroupUser->value,
            foreignPivotKey: 'group_id',
            relatedPivotKey: 'user_id',
            relation: GroupRelationship::Members->value,
        )->withPivot('role')->withTimestamps();
    }

    /**
     * @return HasMany<ProjectEloquentModel, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(ProjectEloquentModel::class, ProjectGroupId::getDatabaseTableColumnName());
    }

    protected static function newFactory(): GroupFactory
    {
        return GroupFactory::new();
    }
}
