<?php

declare(strict_types=1);

namespace App\ArticleLink;

use App\ArticleLink\Factories\ArticleLinkFactory;
use App\ArticleLink\Properties\Id;
use App\AuditLog\AuditObserver;
use App\AuditLog\Contracts\Auditable;
use App\Shared\Table;
use App\Team\Scopes\CurrentTeamScope;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $article_id
 * @property CarbonImmutable|null $created_at
 * @property string|null $created_by_id
 * @property string $id
 * @property string $linkable_id
 * @property string $linkable_type
 * @property string $team_id
 * @property CarbonImmutable|null $updated_at
 */
#[ObservedBy(AuditObserver::class)]
#[ScopedBy(CurrentTeamScope::class)]
final class ArticleLinkEloquentModel extends Model implements Auditable
{
    /** @use HasFactory<ArticleLinkFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    public function auditTeamId(): string
    {
        return $this->team_id;
    }

    public function getTable(): string
    {
        return Table::ArticleLinks->value;
    }

    public function getKeyName(): string
    {
        return Id::getDatabaseTableColumnName();
    }

    protected static function newFactory(): ArticleLinkFactory
    {
        return ArticleLinkFactory::new();
    }
}
