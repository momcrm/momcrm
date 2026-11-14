<?php

declare(strict_types=1);

namespace App\Project\Actions;

use App\Activity\Actions\RecordActivity;
use App\Activity\ActivityTypeEnum;
use App\Project\Notifications\ProjectAtRiskNotification;
use App\Project\Project;
use App\Project\ProjectEloquentModel;
use App\Project\ProjectStatusEnum;
use App\Team\Scopes\CurrentTeamScope;
use App\User\UserEloquentModel;
use Illuminate\Support\Facades\Notification;

/**
 * Saves a project. A new status is recorded in the team's activity; marking a
 * project at risk tells its other members.
 */
final readonly class UpdateProject
{
    public function __construct(
        private Project $project,
        private ?string $updatedById = null,
    ) {}

    public function __invoke(): Project
    {
        $model = ProjectEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->find($this->project->getId()->toString());

        if ($model === null) {
            return $this->project;
        }

        $before = Project::fromEloquentModel($model);
        $model->update($this->project->forDatabaseUpdate());
        $status = $this->project->getStatus()->toEnum();

        if (! $before->getStatus()->equalsTo($status->value)) {
            RecordActivity::for(
                type: ActivityTypeEnum::ProjectStatusChanged,
                userId: $this->updatedById,
                teamId: $model->team_id,
                subject: $model,
                details: ['name' => $model->name, 'from' => $before->getStatus()->toEnum()->label(), 'to' => $status->label()],
            );

            if ($status === ProjectStatusEnum::AtRisk) {
                $members = $model->members()->get()->reject(fn (UserEloquentModel $member): bool => $member->id === $this->updatedById);

                if ($members->isNotEmpty()) {
                    Notification::send($members, new ProjectAtRiskNotification(
                        $model,
                        $this->updatedById === null ? null : UserEloquentModel::query()->find($this->updatedById),
                    ));
                }
            }
        }

        return $this->project;
    }

    public static function fromProject(Project $project, ?string $updatedById = null): Project
    {
        return new self($project, $updatedById)->handle();
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
