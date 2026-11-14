<?php

declare(strict_types=1);

namespace App\TeamUser\FormRequests;

use App\Team\Actions\FindTeam;
use App\Team\Team;
use App\TeamUser\Actions\FindTeamUser;
use App\TeamUser\Properties\Role;
use App\TeamUser\TeamRoleEnum;
use App\TeamUser\TeamUser;
use App\TeamUser\TeamUserEloquentModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Makes a member an admin or a plain member. Only the team owner can, and the
 * owner's own role can't be changed here.
 */
final class UpdateMemberRoleFormRequest extends FormRequest
{
    public Team $team;

    public TeamUser $membership;

    public function __construct()
    {
        parent::__construct();

        $this->team = Team::new();
        $this->membership = TeamUser::new();
    }

    public function authorize(): bool
    {
        $this->team = FindTeam::byIdForMember($this->user()?->current_team_id, (string) $this->user()?->getAuthIdentifier());
        $this->membership = FindTeamUser::byTeamAndUser($this->team->getId()->toString(), (string) $this->route('member'));

        if ($this->team->isNull() || $this->membership->isNull()) {
            throw new NotFoundHttpException('Member not found.');
        }

        return Gate::allows('update', $this->team)
            && $this->membership->getRole()->toNullableEnum() !== TeamRoleEnum::Owner;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            Role::getNameForValidation() => ['required', Rule::in([TeamRoleEnum::Admin->value, TeamRoleEnum::Member->value])],
        ];
    }

    public function process(): RedirectResponse
    {
        TeamUserEloquentModel::query()
            ->findOrFail($this->membership->getId()->toString())
            ->update([Role::getDatabaseTableColumnName() => $this->string(Role::getNameForValidation())->toString()]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role updated.')]);

        return back();
    }
}
