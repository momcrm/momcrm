<?php

declare(strict_types=1);

namespace App\Project\FormRequests;

use App\Project\Actions\CreateProject;
use App\Project\Project;
use App\Project\ProjectValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Inertia\Inertia;

final class StoreProjectFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->current_team_id !== null;
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
        $userId = (string) $this->user()?->getAuthIdentifier();
        $project = Project::new()
            ->setPropertyIfExists(Arr::except($validated, ProjectValidationRules::MEMBER_IDS))
            ->setTeamId((string) $this->user()?->current_team_id);

        if ($project->getLeadId()->isNull()) {
            $project->setLeadId($userId);
        }

        $project = CreateProject::fromProject($project, createdById: $userId, memberIds: [$userId, ...($validated[ProjectValidationRules::MEMBER_IDS] ?? [])]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name created.', ['name' => $project->getName()->toString()])]);

        return to_route('projects.show', $project->getId()->toString());
    }
}
