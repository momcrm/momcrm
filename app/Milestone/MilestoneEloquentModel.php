<?php

declare(strict_types=1);

namespace App\Milestone;

use App\AuditLog\AuditObserver;
use App\AuditLog\Contracts\Auditable;
use App\CustomField\Properties\CustomFieldValues;
use App\Milestone\Factories\MilestoneFactory;
use App\Milestone\Properties\CompletedAt;
use App\Milestone\Properties\Id;
use App\Milestone\Properties\ProjectId;
use App\Project\ProjectEloquentModel;
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
 * @property CarbonImmutable|null $completed_at
 * @property CarbonImmutable|null $created_at
 * @property string $due_on
 * @property string $id
 * @property string $name
 * @property string $project_id
 * @property string $team_id
 * @property CarbonImmutable|null $updated_at
 */
#[ObservedBy(AuditObserver::class)]
#[ScopedBy(CurrentTeamScope::class)]
final class MilestoneEloquentModel extends Model implements Auditable
{
    /** @use HasFactory<MilestoneFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    public function auditTeamId(): string
    {
        return $this->team_id;
    }

    public function getTable(): string
    {
        return Table::Milestones->value;
    }

    public function getKeyName(): string
    {
        return Id::getDatabaseTableColumnName();
    }

    /**
     * @return BelongsTo<ProjectEloquentModel, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(
            related: ProjectEloquentModel::class,
            foreignKey: ProjectId::getDatabaseTableColumnName(),
            relation: MilestoneRelationship::Project->value,
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            CompletedAt::getDatabaseTableColumnName() => 'immutable_datetime',
            CustomFieldValues::getDatabaseTableColumnName() => 'array',
        ];
    }

    protected static function newFactory(): MilestoneFactory
    {
        return MilestoneFactory::new();
    }
}
