<?php

declare(strict_types=1);

namespace App\AccessRule\FormRequests;

use App\AccessRule\AccessRule;
use App\AccessRule\Actions\DeleteAccessRule;
use App\AccessRule\Actions\FindAccessRules;
use App\AccessRule\Controllers\ListAccessRulesController;
use App\AccessRule\Exceptions\CouldNotFindAccessRule;
use App\Team\Actions\FindTeam;
use App\Team\Exceptions\CouldNotViewTeam;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

final class DeleteAccessRuleFormRequest extends FormRequest
{
    public AccessRule $accessRule;

    public function __construct()
    {
        parent::__construct();

        $this->accessRule = AccessRule::new();
    }

    /**
     * @throws CouldNotViewTeam
     * @throws CouldNotFindAccessRule
     */
    public function authorize(): Response
    {
        $team = FindTeam::byIdForMember(
            id: $this->user()?->current_team_id,
            userId: (string) $this->user()?->id,
        );

        if ($team->isNull()) {
            throw CouldNotViewTeam::notFound();
        }

        $this->accessRule = FindAccessRules::byIdForTeam((string) $this->route('accessRule'), $team->getId()->toString());

        if ($this->accessRule->isNull()) {
            throw CouldNotFindAccessRule::notFound();
        }

        return Gate::authorize('manageAccessRules', $team);
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
        DeleteAccessRule::fromAccessRule($this->accessRule);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name deleted.', ['name' => $this->accessRule->getName()->toString()])]);

        return ListAccessRulesController::redirectTo($this->accessRule->getAudience()->toEnum());
    }
}
