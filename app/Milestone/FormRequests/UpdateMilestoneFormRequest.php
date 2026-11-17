<?php

declare(strict_types=1);

namespace App\Milestone\FormRequests;

use App\CustomField\CustomFieldTargetEnum;
use App\CustomField\CustomFieldValueRules;
use App\CustomField\Properties\CustomFieldValues;
use App\Milestone\Actions\FindMilestone;
use App\Milestone\Actions\UpdateMilestone;
use App\Milestone\Exceptions\CouldNotFindMilestone;
use App\Milestone\Milestone;
use App\Milestone\Properties\DueOn;
use App\Milestone\Properties\Name;
use App\Shared\Types;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

/**
 * Renames a milestone, moves its due date, or changes its custom field
 * values. Whether it is done is toggled separately.
 */
final class UpdateMilestoneFormRequest extends FormRequest
{
    public Milestone $milestone;

    public function __construct()
    {
        parent::__construct();

        $this->milestone = Milestone::new();
    }

    /**
     * @throws CouldNotFindMilestone
     */
    public function authorize(): Response
    {
        $this->milestone = FindMilestone::visibleByIdForTeam((string) $this->route('milestone'), $this->user()?->current_team_id, (string) $this->user()?->id);

        if ($this->milestone->isNull()) {
            throw CouldNotFindMilestone::notFound();
        }

        return Gate::authorize('update', $this->milestone);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            Name::getNameForValidation() => Name::getValidationRulesForCreate($this),
            DueOn::getNameForValidation() => DueOn::getValidationRulesForCreate($this),
            ...CustomFieldValueRules::for($this->milestone->getTeamId()->toString(), CustomFieldTargetEnum::Milestone, $this->milestone->getCustomFields()),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return CustomFieldValueRules::attributes($this->milestone->getTeamId()->toString(), CustomFieldTargetEnum::Milestone);
    }

    public function process(): RedirectResponse
    {
        $validated = $this->validated();
        $customFields = CustomFieldValueRules::merge(
            $this->milestone->getTeamId()->toString(),
            CustomFieldTargetEnum::Milestone,
            $this->milestone->getCustomFields(),
            $validated[CustomFieldValues::getNameForValidation()] ?? [],
        );

        UpdateMilestone::fromMilestone(
            $this->milestone
                ->setPropertyIfExists(Types::stringKeyed(Arr::except($validated, CustomFieldValues::getNameForValidation())))
                ->setCustomFields($customFields),
        );

        return back();
    }
}
