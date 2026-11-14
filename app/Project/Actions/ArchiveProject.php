<?php

declare(strict_types=1);

namespace App\Project\Actions;

use App\Activity\Actions\RecordActivity;
use App\Activity\ActivityTypeEnum;
use App\Project\Project;
use App\Project\ProjectEloquentModel;
use App\Team\Scopes\CurrentTeamScope;
use Carbon\CarbonImmutable;

/**
 * Moves a project to the archive, or brings it back. Archived projects drop out
 * of lists and pickers; their tickets stay as they are.
 */
final readonly class ArchiveProject
{
    public function __construct(
        private Project $project,
        private bool $archive = true,
        private ?string $userId = null,
    ) {}

    public function __invoke(): Project
    {
        $model = ProjectEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->find($this->project->getId()->toString());

        if ($model === null || $this->project->isArchived() === $this->archive) {
            return $this->project;
        }

        $this->project->setArchivedAt($this->archive ? CarbonImmutable::now() : null);
        $model->update($this->project->forDatabaseUpdate());

        RecordActivity::for(
            type: $this->archive ? ActivityTypeEnum::ProjectArchived : ActivityTypeEnum::ProjectRestored,
            userId: $this->userId,
            teamId: $this->project->getTeamId()->toString(),
            subject: $model,
            details: ['name' => $this->project->getName()->toString()],
        );

        return $this->project;
    }

    public static function archive(Project $project, ?string $userId = null): Project
    {
        return new self($project, archive: true, userId: $userId)->handle();
    }

    public static function restore(Project $project, ?string $userId = null): Project
    {
        return new self($project, archive: false, userId: $userId)->handle();
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
