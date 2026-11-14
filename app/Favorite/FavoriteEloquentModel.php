<?php

declare(strict_types=1);

namespace App\Favorite;

use App\Favorite\Factories\FavoriteFactory;
use App\Favorite\Properties\FavoritableId;
use App\Favorite\Properties\FavoritableType;
use App\Favorite\Properties\Id;
use App\Shared\Table;
use App\Team\Scopes\CurrentTeamScope;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A project, ticket or customer someone starred. Personal, so not audited.
 *
 * @property CarbonImmutable|null $created_at
 * @property string $favoritable_id
 * @property string $favoritable_type
 * @property string $id
 * @property string $team_id
 * @property string $user_id
 */
#[ScopedBy(CurrentTeamScope::class)]
final class FavoriteEloquentModel extends Model
{
    /** @use HasFactory<FavoriteFactory> */
    use HasFactory;

    use HasUuids;

    public const null UPDATED_AT = null;

    protected $guarded = [];

    public function getTable(): string
    {
        return Table::Favorites->value;
    }

    public function getKeyName(): string
    {
        return Id::getDatabaseTableColumnName();
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function favoritable(): MorphTo
    {
        return $this->morphTo(
            name: FavoriteRelationship::Favoritable->value,
            type: FavoritableType::getDatabaseTableColumnName(),
            id: FavoritableId::getDatabaseTableColumnName(),
        );
    }

    protected static function newFactory(): FavoriteFactory
    {
        return FavoriteFactory::new();
    }
}
