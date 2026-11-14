<?php

declare(strict_types=1);

namespace App\Project\Actions;

use App\Activity\Actions\RecordActivity;
use App\Activity\ActivityTypeEnum;
use App\Project\Project;
use App\Project\ProjectEloquentModel;
use App\Project\ProjectStatusEnum;
use App\Project\ProjectVisibilityEnum;
use App\Project\Properties\Id;
use App\Shared\Actions\NextTeamNumber;
use App\Shared\ColorEnum;
use App\Shared\Table;
use Illuminate\Support\Facades\DB;

/**
 * Creates a project with the team's next number. Its lead and the given
 * members become its members.
 */
final readonly class CreateProject
{
    /**
     * @param  array<int, string>  $memberIds
     */
    public function __construct(
        private Project $project,
        private ?string $createdById = null,
        private array $memberIds = [],
    ) {}

    public function __invoke(): Project
    {
        return DB::transaction(function (): Project {
            if ($this->project->getId()->isNull()) {
                $this->project->setId(Id::generate());
            }

            if ($this->project->getStatus()->isNull()) {
                $this->project->setStatus(ProjectStatusEnum::Planning);
            }

            if ($this->project->getVisibility()->isNull()) {
                $this->project->setVisibility(ProjectVisibilityEnum::Open);
            }

            if ($this->project->getColor()->isNull()) {
                $this->project->setColor(ColorEnum::Indigo);
            }

            $teamId = $this->project->getTeamId()->toString();
            $this->project->setNumber(NextTeamNumber::for(Table::Projects, $teamId));

            $model = ProjectEloquentModel::query()->create($this->project->forDatabaseCreate());

            $model->members()->sync(array_values(array_unique(array_filter([
                $this->project->getLeadId()->toNullableString(),
                ...$this->memberIds,
            ]))));

            RecordActivity::for(
                type: ActivityTypeEnum::ProjectCreated,
                userId: $this->createdById,
                teamId: $teamId,
                subject: $model,
                details: ['name' => $this->project->getName()->toString()],
            );

            return Project::fromEloquentModel($model->refresh());
        });
    }

    /**
     * @param  array<int, string>  $memberIds
     */
    public static function fromProject(Project $project, ?string $createdById = null, array $memberIds = []): Project
    {
        return new self($project, $createdById, $memberIds)->handle();
    }

    public function handle(): Project
    {
        return $this->__invoke();
    }

    public function execute(): Project
    {
        return $this->__invoke();
    }
}
