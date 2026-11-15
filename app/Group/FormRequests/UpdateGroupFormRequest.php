<?php

declare(strict_types=1);

namespace App\Group\FormRequests;

use App\Group\Actions\FindGroupForTeam;
use App\Group\Actions\SyncGroupMembers;
use App\Group\Exceptions\CouldNotFindGroup;
use App\Group\Group;
use App\Group\GroupEloquentModel;
use App\Group\GroupRoleEnum;
use App\Group\GroupValidationRules;
use App\User\UserEloquentModel;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

final class UpdateGroupFormRequest extends FormRequest
{
    public ?GroupEloquentModel $group = null;

    /**
     * @throws CouldNotFindGroup
     */
    public function authorize(): Response
    {
        $this->group = FindGroupForTeam::byId((string) $this->route('group'), $this->user()?->current_team_id);

        if ($this->group === null) {
            throw CouldNotFindGroup::notFound();
        }

        $group = Group::fromEloquentModel($this->group);
        Gate::authorize('update', $group);

        return $this->changesMembers()
            ? Gate::authorize('manageMembers', $group)
            : Response::allow();
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

    /**
     * Whether the request changes who is in the group or who leads it.
     */
    private function changesMembers(): bool
    {
        if ($this->group === null || ! $this->has(GroupValidationRules::MEMBER_IDS)) {
            return false;
        }

        $current = $this->group->members()->get()
            ->mapWithKeys(fn (UserEloquentModel $member): array => [$member->id => (string) $member->getRelationValue('pivot')?->getAttribute('role')])
            ->sortKeys()
            ->all();

        $leadId = $this->input(GroupValidationRules::LEAD_ID);
        $requested = collect((array) $this->input(GroupValidationRules::MEMBER_IDS))
            ->map(strval(...))
            ->mapWithKeys(fn (string $id): array => [$id => GroupRoleEnum::Member->value])
            ->when(is_string($leadId) && $leadId !== '', fn ($members) => $members->put($leadId, GroupRoleEnum::Lead->value))
            ->sortKeys()
            ->all();

        return $current !== $requested;
    }
}
