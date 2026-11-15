<?php

declare(strict_types=1);

namespace App\Project\FormRequests;

use App\Project\Actions\FindProject;
use App\Project\Actions\UpdateProject;
use App\Project\Exceptions\CouldNotFindProject;
use App\Project\Project;
use App\Project\ProjectEloquentModel;
use App\Project\ProjectValidationRules;
use App\Project\Properties\LeadId;
use App\Project\Properties\Visibility;
use App\Shared\Table;
use App\Shared\Types;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
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
    public function authorize(): Response
    {
        $this->loadProject();
        Gate::authorize('update', $this->project);

        if ($this->input(Visibility::getNameForValidation()) !== $this->project->getVisibility()->toNullableString()) {
            Gate::authorize('changeVisibility', $this->project);
        }

        return $this->changesMembers()
            ? Gate::authorize('manageMembers', $this->project)
            : Response::allow();
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
            $this->project->setPropertyIfExists(Types::stringKeyed(Arr::except($validated, ProjectValidationRules::MEMBER_IDS))),
            updatedById: (string) $this->user()?->id,
        );

        $model = ProjectEloquentModel::query()->findOrFail($project->getId()->toString());

        if (array_key_exists(ProjectValidationRules::MEMBER_IDS, $validated)) {
            $model->members()->sync(array_values(array_unique(array_filter([
                $project->getLeadId()->toNullableString(),
                ...Types::stringList($validated[ProjectValidationRules::MEMBER_IDS]),
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
            $this->project = FindProject::visibleByIdForTeam((string) $this->route('project'), $this->user()?->current_team_id, (string) $this->user()?->id);
        }

        if ($this->project->isNull()) {
            throw CouldNotFindProject::notFound();
        }

        return true;
    }

    /**
     * Whether the request changes who is in the project. The lead is always a
     * member, so they count as one of the requested members.
     */
    private function changesMembers(): bool
    {
        if (! $this->has(ProjectValidationRules::MEMBER_IDS)) {
            return false;
        }

        $current = DB::table(Table::ProjectUser->value)
            ->where('project_id', $this->project->getId()->toString())
            ->pluck('user_id')
            ->map(Types::string(...))
            ->sort()
            ->values()
            ->all();

        $leadId = $this->input(LeadId::getNameForValidation());
        $requested = collect((array) $this->input(ProjectValidationRules::MEMBER_IDS))
            ->push($leadId)
            ->filter(fn (mixed $id): bool => is_string($id) && $id !== '')
            ->unique()
            ->sort()
            ->values()
            ->all();

        return $current !== $requested;
    }
}
