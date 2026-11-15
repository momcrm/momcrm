<?php

declare(strict_types=1);

namespace App\Group\FormRequests;

use App\Group\Actions\FindGroupForTeam;
use App\Group\Exceptions\CouldNotFindGroup;
use App\Group\Group;
use App\Group\GroupEloquentModel;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Deletes a group. Its projects stay, without a group.
 */
final class DeleteGroupFormRequest extends FormRequest
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

        return Gate::authorize('delete', Group::fromEloquentModel($this->group));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }

    public function process(): RedirectResponse
    {
        $name = $this->group?->name;
        $this->group?->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name deleted.', ['name' => $name])]);

        return to_route('members', ['tab' => 'groups']);
    }
}
