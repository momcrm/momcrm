<?php

declare(strict_types=1);

namespace App\Group\FormRequests;

use App\Group\Actions\CreateGroup;
use App\Group\Group;
use App\Group\GroupValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Inertia\Inertia;

final class StoreGroupFormRequest extends FormRequest
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
        return GroupValidationRules::for($this, (string) $this->user()?->current_team_id);
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

        $group = CreateGroup::fromGroup(
            Group::new()
                ->setPropertyIfExists(Arr::except($validated, [GroupValidationRules::LEAD_ID, GroupValidationRules::MEMBER_IDS]))
                ->setTeamId((string) $this->user()?->current_team_id),
            leadId: $validated[GroupValidationRules::LEAD_ID] ?? null,
            memberIds: $validated[GroupValidationRules::MEMBER_IDS] ?? [],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name created.', ['name' => $group->getName()->toString()])]);

        return to_route('groups.show', $group->getId()->toString());
    }
}
