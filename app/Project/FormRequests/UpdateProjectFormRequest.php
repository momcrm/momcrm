<?php

declare(strict_types=1);

namespace App\Project\FormRequests;

use App\Project\Actions\FindProject;
use App\Project\Actions\UpdateProject;
use App\Project\Exceptions\CouldNotFindProject;
use App\Project\Project;
use App\Project\ProjectEloquentModel;
use App\Project\ProjectValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Inertia\Inertia;

/**
 * Saves a project's settings, whole or one field at a time (fields left out
 * keep their value). Members are replaced only when sent; the lead always
 * stays a member.
 */
final class UpdateProjectFormRequest extends FormRequest
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
    public function authorize(): bool
    {
        return $this->loadProject();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return ProjectValidationRules::for($this, (string) $this->user()?->current_team_id);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ProjectValidationRules::messages();
    }

    public function process(): RedirectResponse
    {
        $validated = $this->validated();
        $project = UpdateProject::fromProject(
            $this->project->setPropertyIfExists(Arr::except($validated, ProjectValidationRules::MEMBER_IDS)),
            updatedById: (string) $this->user()?->getAuthIdentifier(),
        );

        $model = ProjectEloquentModel::query()->findOrFail($project->getId()->toString());

        if (array_key_exists(ProjectValidationRules::MEMBER_IDS, $validated)) {
            $model->members()->sync(array_values(array_unique(array_filter([
                $project->getLeadId()->toNullableString(),
                ...$validated[ProjectValidationRules::MEMBER_IDS],
            ]))));
        } elseif ($project->getLeadId()->isNotNull()) {
            $model->members()->syncWithoutDetaching([$project->getLeadId()->toString()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project saved.')]);

        return back();
    }

    /**
     * Runs before authorize(), so it loads the project itself.
     *
     * @throws CouldNotFindProject
     */
    protected function prepareForValidation(): void
    {
        $this->loadProject();

        $this->merge(array_merge(
            Arr::except($this->project->toSummary(), ['id', 'number', 'archived_at']),
            $this->boolean(ProjectValidationRules::MEMBERS_SENT) ? [ProjectValidationRules::MEMBER_IDS => []] : [],
            $this->all(),
        ));
    }

    /**
     * @throws CouldNotFindProject
     */
    private function loadProject(): bool
    {
        if ($this->project->isNull()) {
            $this->project = FindProject::visibleByIdForTeam((string) $this->route('project'), $this->user()?->current_team_id, (string) $this->user()?->getAuthIdentifier());
        }

        if ($this->project->isNull()) {
            throw CouldNotFindProject::notFound();
        }

        return true;
    }
}
