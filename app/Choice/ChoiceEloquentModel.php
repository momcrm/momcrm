<?php

declare(strict_types=1);

namespace App\Choice;

use App\AuditLog\AuditObserver;
use App\AuditLog\Contracts\Auditable;
use App\Choice\Factories\ChoiceFactory;
use App\Choice\Properties\Id;
use App\Shared\Table;
use App\Team\Scopes\CurrentTeamScope;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property CarbonImmutable|null $archived_at
 * @property string|null $category
 * @property string $color
 * @property CarbonImmutable|null $created_at
 * @property string|null $icon
 * @property string $id
 * @property bool $is_default
 * @property string $list
 * @property string $name
 * @property int $position
 * @property string $team_id
 * @property CarbonImmutable|null $updated_at
 */
#[ObservedBy(AuditObserver::class)]
#[ScopedBy(CurrentTeamScope::class)]
final class ChoiceEloquentModel extends Model implements Auditable
{
    /** @use HasFactory<ChoiceFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    public function auditTeamId(): string
    {
        return $this->team_id;
    }

    public function getTable(): string
    {
        return Table::Choices->value;
    }

    public function getKeyName(): string
    {
        return Id::getDatabaseTableColumnName();
    }

    protected static function newFactory(): ChoiceFactory
    {
        return ChoiceFactory::new();
    }
}
