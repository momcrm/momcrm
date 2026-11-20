<?php

declare(strict_types=1);

namespace App\ArticleVersion;

use App\ArticleVersion\Factories\ArticleVersionFactory;
use App\ArticleVersion\Properties\EditedById;
use App\ArticleVersion\Properties\Id;
use App\ArticleVersion\Properties\SavedAt;
use App\Shared\Table;
use App\Team\Scopes\CurrentTeamScope;
use App\User\UserEloquentModel;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Not audited: a version is itself the record of a change.
 *
 * @property string $article_id
 * @property string|null $body
 * @property string|null $body_text
 * @property string|null $change_note
 * @property string $classification
 * @property CarbonImmutable|null $created_at
 * @property string|null $edited_by_id
 * @property string $id
 * @property CarbonImmutable|null $saved_at
 * @property string|null $summary
 * @property string $team_id
 * @property string $title
 * @property CarbonImmutable|null $updated_at
 * @property int $version_major
 * @property int $version_minor
 */
#[ScopedBy(CurrentTeamScope::class)]
final class ArticleVersionEloquentModel extends Model
{
    /** @use HasFactory<ArticleVersionFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    public function getTable(): string
    {
        return Table::ArticleVersions->value;
    }

    public function getKeyName(): string
    {
        return Id::getDatabaseTableColumnName();
    }

    /**
     * @return BelongsTo<UserEloquentModel, $this>
     */
    public function editedBy(): BelongsTo
    {
        return $this->belongsTo(
            related: UserEloquentModel::class,
            foreignKey: EditedById::getDatabaseTableColumnName(),
            relation: ArticleVersionRelationship::EditedBy->value,
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            SavedAt::getDatabaseTableColumnName() => 'immutable_datetime',
        ];
    }

    protected static function newFactory(): ArticleVersionFactory
    {
        return ArticleVersionFactory::new();
    }
}
