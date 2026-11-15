<?php

declare(strict_types=1);

namespace App\Milestone\FormRequests;

use App\Milestone\Actions\CreateMilestone;
use App\Milestone\Milestone;
use App\Milestone\Properties\DueOn;
use App\Milestone\Properties\Name;
use App\Project\Actions\FindProject;
use App\Project\Exceptions\CouldNotFindProject;
use App\Project\Project;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class StoreMilestoneFormRequest extends FormRequest
{
    public Project $project;

    public function __construct()
    {
        parent::__construct();

        $this->project = Project::new();
    }

    /**
     * @throws CouldNotFindProject
     */
    public function authorize(): Response
    {
        $this->project = FindProject::visibleByIdForTeam((string) $this->route('project'), $this->user()?->current_team_id, (string) $this->user()?->getAuthIdentifier());

        if ($this->project->isNull()) {
            throw CouldNotFindProject::notFound();
        }

        return Gate::authorize('create', Milestone::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            Name::getNameForValidation() => Name::getValidationRulesForCreate($this),
            DueOn::getNameForValidation() => DueOn::getValidationRulesForCreate($this),
        ];
    }

    public function process(): RedirectResponse
    {
        CreateMilestone::fromMilestone(Milestone::new()
            ->setPropertyIfExists($this->validated())
            ->setTeamId($this->project->getTeamId())
            ->setProjectId($this->project->getId()));

        return back();
    }
}
