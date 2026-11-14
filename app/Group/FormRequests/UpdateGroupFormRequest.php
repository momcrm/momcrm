<?php

declare(strict_types=1);

namespace App\Group\FormRequests;

use App\Group\Actions\FindGroupForTeam;
use App\Group\Actions\SyncGroupMembers;
use App\Group\Exceptions\CouldNotFindGroup;
use App\Group\Group;
use App\Group\GroupEloquentModel;
use App\Group\GroupValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Inertia\Inertia;

final class UpdateGroupFormRequest extends FormRequest
{
    public ?GroupEloquentModel $group = null;

    /**
     * @throws CouldNotFindGroup
     */
    public function authorize(): bool
    {
        $this->group = FindGroupForTeam::byId((string) $this->route('group'), $this->user()?->current_team_id);

        if ($this->group === null) {
            throw CouldNotFindGroup::notFound();
        }

        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->boolean(GroupValidationRules::MEMBERS_SENT) && ! $this->has(GroupValidationRules::MEMBER_IDS)) {
            $this->merge([GroupValidationRules::MEMBER_IDS => []]);
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return GroupValidationRules::for($this, (string) $this->user()?->current_team_id, (string) $this->route('group'));
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return GroupValidationRules::messages();
    }

    public function process(): RedirectResponse
    {
        $validated = $this->validated();
        $group = $this->group ?? new GroupEloquentModel;

        $group->update(Group::fromEloquentModel($group)
            ->setPropertyIfExists(Arr::except($validated, [GroupValidationRules::LEAD_ID, GroupValidationRules::MEMBER_IDS]))
            ->forDatabaseUpdate());

        if (array_key_exists(GroupValidationRules::MEMBER_IDS, $validated)) {
            SyncGroupMembers::to($group, $validated[GroupValidationRules::LEAD_ID] ?? null, $validated[GroupValidationRules::MEMBER_IDS]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Group saved.')]);

        return back();
    }
}
