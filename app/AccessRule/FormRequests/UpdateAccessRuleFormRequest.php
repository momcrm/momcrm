<?php

declare(strict_types=1);

namespace App\AccessRule\FormRequests;

use App\AccessRule\AccessRule;
use App\AccessRule\AccessRuleValidationRules;
use App\AccessRule\Actions\FindAccessRules;
use App\AccessRule\Actions\UpdateAccessRule;
use App\AccessRule\Controllers\ListAccessRulesController;
use App\AccessRule\Exceptions\CouldNotFindAccessRule;
use App\AccessRule\Properties\Conditions;
use App\Shared\Types;
use App\Team\Actions\FindTeam;
use App\Team\Exceptions\CouldNotViewTeam;
use Closure;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;
use Inertia\Inertia;

final class UpdateAccessRuleFormRequest extends FormRequest
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
        return AccessRuleValidationRules::for($this, isNew: false);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return AccessRuleValidationRules::messages();
    }

    /**
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [fn (Validator $validator) => AccessRuleValidationRules::after(
            $validator,
            (string) $this->user()?->current_team_id,
            (string) $this->user()?->id,
            $this->accessRule->getAudience()->toEnum(),
        )];
    }

    public function process(): RedirectResponse
    {
        $validated = $this->validated();

        UpdateAccessRule::fromAccessRule(
            $this->accessRule
                ->setPropertyIfExists(Types::stringKeyed(Arr::except($validated, [Conditions::getNameForValidation()])))
                ->setConditions(AccessRuleValidationRules::normalizeConditions($validated[Conditions::getNameForValidation()])),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Access rule saved.')]);

        return ListAccessRulesController::redirectTo($this->accessRule->getAudience()->toEnum());
    }
}
