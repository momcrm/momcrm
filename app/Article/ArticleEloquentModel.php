<?php

declare(strict_types=1);

namespace App\Article;

use App\Article\Factories\ArticleFactory;
use App\Article\Properties\ArchivedAt;
use App\Article\Properties\AuthorId;
use App\Article\Properties\Id;
use App\Article\Properties\PublishedAt;
use App\AuditLog\AuditObserver;
use App\AuditLog\Contracts\Auditable;
use App\Shared\Table;
use App\Team\Scopes\CurrentTeamScope;
use App\User\UserEloquentModel;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property CarbonImmutable|null $archived_at
 * @property string|null $author_id
 * @property string|null $body
 * @property string|null $body_text
 * @property string $classification
 * @property CarbonImmutable|null $created_at
 * @property string $id
 * @property CarbonImmutable|null $published_at
 * @property string $status
 * @property string|null $summary
 * @property string $team_id
 * @property string $title
 * @property CarbonImmutable|null $updated_at
 */
#[ObservedBy(AuditObserver::class)]
#[ScopedBy(CurrentTeamScope::class)]
final class ArticleEloquentModel extends Model implements Auditable
{
    /** @use HasFactory<ArticleFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    /**
     * The body is left out of the audit log, which would otherwise hold the
     * whole article twice on every save.
     *
     * @var list<string>
     */
    protected $hidden = ['body', 'body_text'];

    public function auditTeamId(): string
    {
        return $this->team_id;
    }

    public function getTable(): string
    {
        return Table::Articles->value;
    }

    public function getKeyName(): string
    {
        return Id::getDatabaseTableColumnName();
    }

    /**
     * @return BelongsTo<UserEloquentModel, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(
            related: UserEloquentModel::class,
            foreignKey: AuthorId::getDatabaseTableColumnName(),
            relation: ArticleRelationship::Author->value,
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            ArchivedAt::getDatabaseTableColumnName() => 'immutable_datetime',
            PublishedAt::getDatabaseTableColumnName() => 'immutable_datetime',
        ];
    }

    protected static function newFactory(): ArticleFactory
    {
        return ArticleFactory::new();
    }
}
