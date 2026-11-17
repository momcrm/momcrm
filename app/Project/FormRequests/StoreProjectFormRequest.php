<?php

declare(strict_types=1);

namespace App\Project\FormRequests;

use App\Choice\LabelTargetEnum;
use App\CustomField\CustomFieldTargetEnum;
use App\CustomField\CustomFieldValueRules;
use App\CustomField\Properties\CustomFieldValues;
use App\Project\Actions\CreateProject;
use App\Project\Project;
use App\Project\ProjectEloquentModel;
use App\Project\ProjectValidationRules;
use App\Project\Properties\Hue;
use App\Shared\HuePalette;
use App\Shared\Types;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

final class StoreProjectFormRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::authorize('create', Project::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $teamId = (string) $this->user()?->current_team_id;

        return [
            ...ProjectValidationRules::for($this, $teamId),
            ...CustomFieldValueRules::for($teamId, CustomFieldTargetEnum::Project),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ProjectValidationRules::messages();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return CustomFieldValueRules::attributes((string) $this->user()?->current_team_id, CustomFieldTargetEnum::Project);
    }

    public function process(): RedirectResponse
    {
        $validated = $this->validated();
        $userId = (string) $this->user()?->id;
        $project = Project::new()
            ->setPropertyIfExists(Types::stringKeyed(Arr::except($validated, [ProjectValidationRules::MEMBER_IDS, LabelTargetEnum::FIELD, CustomFieldValues::getNameForValidation()])))
            ->setCustomFields(CustomFieldValueRules::merge((string) $this->user()?->current_team_id, CustomFieldTargetEnum::Project, new CustomFieldValues(null), $validated[CustomFieldValues::getNameForValidation()] ?? []))
            ->setTeamId((string) $this->user()?->current_team_id);

        if ($project->getLeadId()->isNull()) {
            $project->setLeadId($userId);
        }

        if (! array_key_exists(Hue::getNameForValidation(), $validated)) {
            $project->setHue(HuePalette::forName('project', $project->getName()->toString()));
        }

        $project = CreateProject::fromProject($project, createdById: $userId, memberIds: [$userId, ...Types::stringList($validated[ProjectValidationRules::MEMBER_IDS] ?? [])]);

        LabelTargetEnum::syncFrom($validated, ProjectEloquentModel::query()->findOrFail($project->getId()->toString())->labels());

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name created.', ['name' => $project->getName()->toString()])]);

        return to_route('projects.show', $project->getId()->toString());
    }
}
