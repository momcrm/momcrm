<?php

declare(strict_types=1);

namespace App\Project\Actions;

use App\Project\Project;
use App\Project\ProjectEloquentModel;
use App\Team\Scopes\CurrentTeamScope;

final readonly class UpdateProject
{
    public function __construct(
        private Project $project,
    ) {}

    public function __invoke(): Project
    {
        ProjectEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->find($this->project->getId()->toString())
            ?->update($this->project->forDatabaseUpdate());

        return $this->project;
    }

    public static function fromProject(Project $project): Project
    {
        return new self($project)->handle();
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
