<?php

declare(strict_types=1);

namespace App\Article;

use App\Article\Factories\ArticleFactory;
use App\Article\Properties\ArchivedAt;
use App\Article\Properties\AuthorId;
use App\Article\Properties\CategoryId;
use App\Article\Properties\CustomerId;
use App\Article\Properties\EditedById;
use App\Article\Properties\Id;
use App\Article\Properties\PublishedAt;
use App\ArticleCategory\ArticleCategoryEloquentModel;
use App\AuditLog\AuditObserver;
use App\AuditLog\Contracts\Auditable;
use App\Choice\HasLabels;
use App\Choice\LabelTargetEnum;
use App\Customer\CustomerEloquentModel;
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
 * @property string|null $category_id
 * @property string|null $customer_id
 * @property string|null $body_text
 * @property string|null $change_note
 * @property string $classification
 * @property CarbonImmutable|null $created_at
 * @property string|null $edited_by_id
 * @property string $id
 * @property string|null $last_reviewed_on
 * @property string|null $next_review_on
 * @property CarbonImmutable|null $published_at
 * @property string $status
 * @property string|null $summary
 * @property string $team_id
 * @property string $title
 * @property CarbonImmutable|null $updated_at
 * @property int $version_major
 * @property int $version_minor
 */
#[ObservedBy(AuditObserver::class)]
#[ScopedBy(CurrentTeamScope::class)]
final class ArticleEloquentModel extends Model implements Auditable
{
    /** @use HasFactory<ArticleFactory> */
    use HasFactory;

    use HasLabels;
    use HasUuids;

    protected $guarded = [];

    /**
     * The body is left out of the audit log, which would otherwise hold the
     * whole article twice on every save.
     *
     * @var list<string>
     */
    protected $hidden = ['body', 'body_text'];

    public static function labelTarget(): LabelTargetEnum
    {
        return LabelTargetEnum::Article;
    }

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
     * @return BelongsTo<ArticleCategoryEloquentModel, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(
            related: ArticleCategoryEloquentModel::class,
            foreignKey: CategoryId::getDatabaseTableColumnName(),
            relation: ArticleRelationship::Category->value,
        );
    }

    /**
     * @return BelongsTo<CustomerEloquentModel, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(
            related: CustomerEloquentModel::class,
            foreignKey: CustomerId::getDatabaseTableColumnName(),
            relation: ArticleRelationship::Customer->value,
        );
    }

    /**
     * @return BelongsTo<UserEloquentModel, $this>
     */
    public function editedBy(): BelongsTo
    {
        return $this->belongsTo(
            related: UserEloquentModel::class,
            foreignKey: EditedById::getDatabaseTableColumnName(),
            relation: ArticleRelationship::EditedBy->value,
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
