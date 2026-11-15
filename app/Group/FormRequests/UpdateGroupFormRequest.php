<?php

declare(strict_types=1);

namespace App\Group\FormRequests;

use App\Group\Actions\FindGroup;
use App\Group\Actions\FindGroupForTeam;
use App\Group\Actions\SyncGroupMembers;
use App\Group\Actions\UpdateGroup;
use App\Group\Exceptions\CouldNotFindGroup;
use App\Group\Group;
use App\Group\GroupRoleEnum;
use App\Group\GroupValidationRules;
use App\Shared\Types;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

final class UpdateGroupFormRequest extends FormRequest
{
    public Group $group;

    public function __construct()
    {
        parent::__construct();

        $this->group = Group::new();
    }

    /**
     * @throws CouldNotFindGroup
     */
    public function authorize(): Response
    {
        $this->group = FindGroupForTeam::byId((string) $this->route('group'), $this->user()?->current_team_id);

        if ($this->group->isNull()) {
            throw CouldNotFindGroup::notFound();
        }

        Gate::authorize('update', $this->group);

        return $this->changesMembers()
            ? Gate::authorize('manageMembers', $this->group)
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
        UpdateGroup::fromGroup(
            $this->group->setPropertyIfExists(Types::stringKeyed(Arr::except($validated, [GroupValidationRules::LEAD_ID, GroupValidationRules::MEMBER_IDS]))),
        );

        if (array_key_exists(GroupValidationRules::MEMBER_IDS, $validated)) {
            SyncGroupMembers::to($this->group->getId()->toString(), Types::nullableString($validated[GroupValidationRules::LEAD_ID] ?? null), Types::stringList($validated[GroupValidationRules::MEMBER_IDS]));
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Group saved.')]);

        return back();
    }

    /**
     * Whether the request changes who is in the group or who leads it.
     */
    private function changesMembers(): bool
    {
        if (! $this->has(GroupValidationRules::MEMBER_IDS)) {
            return false;
        }

        $current = FindGroup::membersOf($this->group->getId()->toString())
            ->mapWithKeys(fn (array $member): array => [$member['id'] => $member['role']])
            ->sortKeys()
            ->all();

        $leadId = $this->string(GroupValidationRules::LEAD_ID)->toString();
        $requested = collect(Types::stringList((array) $this->input(GroupValidationRules::MEMBER_IDS)))
            ->mapWithKeys(fn (string $id): array => [$id => GroupRoleEnum::Member->value])
            ->when($leadId !== '', fn ($members) => $members->put($leadId, GroupRoleEnum::Lead->value))
            ->sortKeys()
            ->all();

        return $current !== $requested;
    }
}
