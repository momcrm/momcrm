<?php

declare(strict_types=1);

namespace App\Project;

use App\AuditLog\AuditObserver;
use App\AuditLog\Contracts\Auditable;
use App\Choice\HasLabels;
use App\Choice\LabelTargetEnum;
use App\Customer\CustomerEloquentModel;
use App\CustomField\Properties\CustomFieldValues;
use App\Group\GroupEloquentModel;
use App\Milestone\MilestoneEloquentModel;
use App\Milestone\Properties\ProjectId as MilestoneProjectId;
use App\Project\Factories\ProjectFactory;
use App\Project\Properties\ArchivedAt;
use App\Project\Properties\CustomerId;
use App\Project\Properties\GroupId;
use App\Project\Properties\Id;
use App\Project\Properties\IsSample;
use App\Project\Properties\LeadId;
use App\Project\Properties\Number;
use App\Shared\Table;
use App\Team\Scopes\CurrentTeamScope;
use App\Ticket\Properties\ProjectId as TicketProjectId;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property CarbonImmutable|null $archived_at
 * @property int|null $hue
 * @property CarbonImmutable|null $created_at
 * @property string|null $customer_id
 * @property string|null $description
 * @property string|null $due_on
 * @property string|null $group_id
 * @property string|null $icon
 * @property string $id
 * @property bool $is_sample
 * @property string|null $lead_id
 * @property string $name
 * @property int $number
 * @property string|null $starts_on
 * @property string $status
 * @property string $team_id
 * @property CarbonImmutable|null $updated_at
 * @property string $visibility
 */
#[ObservedBy(AuditObserver::class)]
#[ScopedBy(CurrentTeamScope::class)]
final class ProjectEloquentModel extends Model implements Auditable
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    use HasLabels;
    use HasUuids;

    protected $guarded = [];

    public function auditTeamId(): string
    {
        return $this->team_id;
    }

    public function getTable(): string
    {
        return Table::Projects->value;
    }

    public function getKeyName(): string
    {
        return Id::getDatabaseTableColumnName();
    }

    public static function labelTarget(): LabelTargetEnum
    {
        return LabelTargetEnum::Project;
    }

    /**
     * @return BelongsTo<CustomerEloquentModel, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(
            related: CustomerEloquentModel::class,
            foreignKey: CustomerId::getDatabaseTableColumnName(),
            relation: ProjectRelationship::Customer->value,
        );
    }

    /**
     * @return BelongsTo<UserEloquentModel, $this>
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(
            related: UserEloquentModel::class,
            foreignKey: LeadId::getDatabaseTableColumnName(),
            relation: ProjectRelationship::Lead->value,
        );
    }

    /**
     * @return BelongsTo<GroupEloquentModel, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(
            related: GroupEloquentModel::class,
            foreignKey: GroupId::getDatabaseTableColumnName(),
            relation: ProjectRelationship::Group->value,
        );
    }

    /**
     * The people working on the project. Restricted projects are visible only to them.
     *
     * @return BelongsToMany<UserEloquentModel, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(
            related: UserEloquentModel::class,
            table: Table::ProjectUser->value,
            foreignPivotKey: 'project_id',
            relatedPivotKey: 'user_id',
            relation: ProjectRelationship::Members->value,
        )->withTimestamps();
    }

    /**
     * @return HasMany<MilestoneEloquentModel, $this>
     */
    public function milestones(): HasMany
    {
        return $this->hasMany(MilestoneEloquentModel::class, MilestoneProjectId::getDatabaseTableColumnName());
    }

    /**
     * @return HasMany<TicketEloquentModel, $this>
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(TicketEloquentModel::class, TicketProjectId::getDatabaseTableColumnName());
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            CustomFieldValues::getDatabaseTableColumnName() => 'array',
            Number::getDatabaseTableColumnName() => 'integer',
            IsSample::getDatabaseTableColumnName() => 'boolean',
            ArchivedAt::getDatabaseTableColumnName() => 'immutable_datetime',
        ];
    }

    protected static function newFactory(): ProjectFactory
    {
        return ProjectFactory::new();
    }
}
