<?php

declare(strict_types=1);

namespace App\Team;

use App\Shared\Table;
use App\Team\Factories\TeamFactory;
use App\Team\Properties\Id;
use App\Team\Properties\IsPersonal;
use App\Team\Properties\OwnerId;
use App\TeamUser\Properties\Role;
use App\TeamUser\Properties\TeamId;
use App\TeamUser\Properties\UserId;
use App\User\UserEloquentModel;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property string $id
 * @property string $owner_id
 * @property string $name
 * @property bool $personal_team
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
final class TeamEloquentModel extends Model
{
    /** @use HasFactory<TeamFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    public function getTable(): string
    {
        return Table::Teams->value;
    }

    public function getKeyName(): string
    {
        return Id::getDatabaseTableColumnName();
    }

    /**
     * Related tables reference this model as TeamId's column, not the class-derived default.
     */
    public function getForeignKey(): string
    {
        return TeamId::getDatabaseTableColumnName();
    }

    /**
     * @return BelongsTo<UserEloquentModel, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(
            related: UserEloquentModel::class,
            foreignKey: OwnerId::getDatabaseTableColumnName(),
            relation: TeamRelationship::Owner->value,
        );
    }

    /**
     * @return BelongsToMany<UserEloquentModel, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            related: UserEloquentModel::class,
            table: Table::TeamUser->value,
            foreignPivotKey: TeamId::getDatabaseTableColumnName(),
            relatedPivotKey: UserId::getDatabaseTableColumnName(),
            relation: TeamRelationship::Users->value,
        )->withPivot(Role::getDatabaseTableColumnName())->withTimestamps();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            IsPersonal::getDatabaseTableColumnName() => 'boolean',
        ];
    }

    protected static function newFactory(): TeamFactory
    {
        return TeamFactory::new();
    }
}
